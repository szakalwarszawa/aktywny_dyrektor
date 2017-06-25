<?php

/**
 * Description of SamaccountnameGeneratorService
 *
 * @author Kamil Jakacki
 */

namespace Parp\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Parp\MainBundle\Entity\UserUprawnienia;
use Parp\MainBundle\Entity\UserGrupa;
use Parp\MainBundle\Services\RedmineConnectService;

class SamaccountnameGeneratorService
{
    protected $doctrine;
    protected $container;

    public function __construct(EntityManager $OrmEntity, Container $container)
    {
        $this->doctrine = $OrmEntity;
        $this->container = $container;
    }

    protected function generateNextSam($imie, $nazwisko, $try)
    {
        $ret = '';
        $letters = 'abcdefghijklmnopqrstuvwxyz_';
        $letters_array = str_split($letters);
        $find =    array('ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', 'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź', 'Ż');
        $replace = array('a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z', 'a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z');
        $imie = strtolower(str_replace($find, $replace, $imie));
        $nazwiskoCzesci = explode('-', $nazwisko);
        $nazwisko = strtolower(str_replace($find, $replace, $nazwiskoCzesci[0]));
        $ret2 = $imie.'_'.$nazwisko;
        $ret2_array = str_split($ret2);
        $ret = '';
        //wyrzucam niedozwolone znaki
        foreach ($ret2_array as $ch) {
            if (in_array($ch, $letters_array, true)) {
                $ret .= $ch;
            }
        }
        if ($try == 0) {
            $ret = substr($ret, 0, 20);
        } else {
            $ret = substr($ret, 0, (20 - strlen($try))).$try;
        }
        //var_dump($imie, $nazwisko,$ret2, $try, $ret); //die();
        return $ret;
    }

    public function generateSamaccountnamePoZmianieNazwiska($cn)
    {
        $parts = explode(' ', $cn);
        $ret = $this->generateNextSam($parts[2], $parts[0], 0);
        //die(".".$cn.".".$ret);
        return $ret;
    }

    public function generateSamaccountname($imie, $nazwisko, $sprawdzajCzyJuzJest = true)
    {
        $ldap = $this->container->get('ldap_service');
        $ret = $this->generateNextSam($imie, $nazwisko, 0);
        if ($sprawdzajCzyJuzJest) {
            $user = $ldap->getUserFromAD($ret);
            $try = 0;
            while (count($user) > 0 && $try < 1000) {
                $ret = $this->generateNextSam($imie, $nazwisko, ++$try);
                $user = $ldap->getUserFromAD($ret);
            }
        }
        return $ret;
    }

    public function generateFullname($imie, $nazwisko, $stareImie = '', $stareNazwisko = '')
    {
        $ret = $nazwisko.' '.$imie; //$dr->getImie()." ".$dr->getNazwisko();
        if ($stareNazwisko != '') {
            $ret = $nazwisko.' ('.$stareNazwisko.') '.$imie;
        }
        return $ret;
    }

    public function rozbijFullname($name)
    {
        $cz = explode(' ', $name);
        $imie = ucfirst(mb_strtolower(trim($cz[1])));
        $nazwiska = explode('-', trim($cz[0]));
        $nazwisko = [];
        foreach ($nazwiska as $n) {
            $nazwisko[] = ucfirst(mb_strtolower(trim($n)));
        }
        $ret = ['imie' => $imie, 'nazwisko' => implode('-', $nazwisko)];
        return $ret;
    }

    public function generateDN($imie, $nazwisko, $departament)
    {
        //CN=Skubiszewska Aleksandra,OU=BZK,OU=Zespoly,OU=PARP Pracownicy,DC=parp,DC=local
        $tab = explode('.', $this->container->getParameter('ad_domain'));
        $ou = $this->container->getParameter('ad_ou');
        $patch = ',DC=' . $tab[0] . ',DC=' . $tab[1];

        $ret = 'CN='.$nazwisko.' '.$imie.',OU='.$departament.','.$ou.$patch;
        return $ret;
    }

    public function ADnameToRekordName($name)
    {

        return $this->standarizeString(mb_strtoupper(implode(' ', $this->ADnameToRekordNameAsArray($name))));
    }
    public function ADnameToRekordNameAsArray($name)
    {
        $name = str_replace('(', ' (', $name);
        $name = str_replace(')', ') ', $name);
        $name = $this->standarizeString($name);
        $parts = explode(' ', $name);
        $ret = [];
        foreach ($parts as $p) {
            if (mb_strstr($p, '(') !== false) {
                //pomijamy stare nazwisko
            } elseif (false !== strpos($p, '-')) {
                //tu normalnie
                $ret[] = $p;
            } else {
                //tu tez
                $ret[] = $p;
            }
        }
        return $ret;
    }

    public function rekordNameToADname($rekordName)
    {
        $rekordName = $this->standarizeString($rekordName);
        $parts = explode(' ', $rekordName);
        $ret = [];
        foreach ($parts as $p) {
            if (mb_strstr($p, '(') !== false) {
                $p2 = str_replace(['(', ')'], ['', ''], $p);
                $p2 = '('.$this->mbUcfirst(mb_strtolower($p2)).')';
                $ret[] = $p2;
            } elseif (false !== strpos($p, '-')) {
                $p2 = explode('-', $p);
                $ret2 = [];
                foreach ($p2 as $p3) {
                    $ret2[] = $this->mbUcfirst(mb_strtolower($p));
                }
                $ret[] = implode('-', $ret2);
            } else {
                $ret[] = $this->mbUcfirst(mb_strtolower($p));
            }
        }
        return $this->standarizeString(implode(' ', $ret));
    }

    protected function mbUcfirst($string, $encoding = 'UTF8')
    {
        $strlen = mb_strlen($string, $encoding);
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $then = mb_substr($string, 1, $strlen - 1, $encoding);
        return mb_strtoupper($firstChar, $encoding) . $then;
    }

    public function standarizeString($str)
    {
        for ($i = 0; $i < 5; $i++) {
            $str = str_replace('  ', ' ', $str);
        }
        $str = trim($str);
        return $str;
    }

    public function parseStanowisko($title)
    {
        return $title;

        $ps = explode(' ', trim($title));
        $ret = [];
        foreach ($ps as $p) {
            if (in_array(mb_strtolower($p), ['po', 'p.o.'], true)) {
                $ret[] = 'p.o.';
            } else {
                $ret[] = $this->mbUcfirst(mb_strtolower($p));
            }
        }
        return implode(' ', $ret);
    }
}
