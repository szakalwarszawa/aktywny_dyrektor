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
    
    protected function generateNextSam($imie, $nazwisko, $try){
        $ret = "";
        $find =    array("ą", "ć", "ę", "ł", "ń", "ó", "ś", "ź", "ż", "Ą", "Ć", "Ę", "Ł", "Ń", "Ó", "Ś", "Ź", "Ż");
        $replace = array("a", "c", "e", "l", "n", "o", "s", "z", "z", "a", "c", "e", "l", "n", "o", "s", "z", "z");
        $imie = strtolower(str_replace($find, $replace, $imie)); 
        $nazwiskoCzesci = explode('-', $nazwisko);
        $nazwisko = strtolower(str_replace($find, $replace, $nazwiskoCzesci[0]));
        $ret = $imie."_".$nazwisko;
        if($try == 0){
            $ret = substr($ret, 0, 20);
        }else{
            $ret = substr($ret, 0, (20 - strlen($try))).$try;
        }
        return $ret;        
    }
    
    
    public function generateSamaccountname($imie, $nazwisko){
        $ldap = $this->container->get('ldap_service');
        $ret = $this->generateNextSam($imie, $nazwisko, 0);
        $user = $ldap->getUserFromAD($ret);
        $try = 0;
        while(count($user) > 0 && $try < 1000){            
            $ret = $this->generateNextSam($imie, $nazwisko, ++$try);
            $user = $ldap->getUserFromAD($ret);
        }
        return $ret;
    }   
    
    public function generateFullname($imie, $nazwisko){
        $ret = $nazwisko." ".$imie; //$dr->getImie()." ".$dr->getNazwisko();
        return $ret;
    } 
    
    public function rozbijFullname($name){
        $cz = explode(" ", $name);
        $imie = ucfirst(strtolower(trim($cz[1])));
        $nazwiska = explode("-", trim($cz[0]));
        $nazwisko = [];
        foreach($nazwiska as $n){
            $nazwisko[] = ucfirst(strtolower(trim($n)));
        }
        $ret = ['imie' => $imie, 'nazwisko' => implode("-", $nazwisko)];
        return $ret;
    }
    
    public function generateDN($imie, $nazwisko, $departament){        
        //CN=Skubiszewska Aleksandra,OU=BZK,OU=Zespoly,OU=PARP Pracownicy,DC=parp,DC=local
        $tab = explode(".", $this->container->getParameter('ad_domain'));        
        $ou = $this->container->getParameter('ad_ou');
        $patch = ',DC=' . $tab[0] . ',DC=' . $tab[1];
        
        $ret = "CN=".$nazwisko." ".$imie.",OU=".$departament.",".$ou.$patch;
        return $ret;
    }
}