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
    
    protected function generateNextSam($dr, $try){
        $ret = "";
        $find =    array("ą", "ć", "ę", "ł", "ń", "ó", "ś", "ź", "ż", "Ą", "Ć", "Ę", "Ł", "Ń", "Ó", "Ś", "Ź", "Ż");
        $replace = array("a", "c", "e", "l", "n", "o", "s", "z", "z", "a", "c", "e", "l", "n", "o", "s", "z", "z");
        $imie = strtolower(str_replace($find, $replace, $dr->getImie())); 
        $nazwiskoCzesci = explode('-', $dr->getNazwisko());
        $nazwisko = strtolower(str_replace($find, $replace, $nazwiskoCzesci[0]));
        $ret = $imie."_".$nazwisko;
        if($try == 0){
            $ret = substr($ret, 0, 20);
        }else{
            $ret = substr($ret, 0, (20 - strlen($try))).$try;
        }
        return $ret;        
    }
    
    
    public function generateSamaccountname($dr){
        $ldap = $this->container->get('ldap_service');
        $ret = $this->generateNextSam($dr, 0);
        $user = $ldap->getUserFromAD($ret);
        $try = 0;
        while(count($user) > 0 && $try < 1000){            
            $ret = $this->generateNextSam($dr, ++$try);
            $user = $ldap->getUserFromAD($ret);
        }
        return $ret;
    }   
    
    public function generateFullname($dr){
        $ret = $dr->getImie()." ".$dr->getNazwisko();
        return $ret;
    } 
    
}