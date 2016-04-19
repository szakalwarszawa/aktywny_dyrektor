<?php

/**
 * Description of RightsServices
 *
 * @author tomasz_bonczak
 */

namespace Parp\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Parp\MainBundle\Entity\UserUprawnienia;
use Parp\MainBundle\Entity\UserGrupa;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ADSecurityCheckService
{
    protected $container;
    public function __construct(Container $container){
        
        $this->container = $container;
    }
    
    public function checkIfUserCanBeEdited($sam){
        $ps = explode("_", $sam);
        
        $nazwisko = $this->container->getParameter('blokada_na_zapis_tylko_userow_o_tym_nawisku');
        
        $ret = $ps[1] == $nazwisko;
        if(!$ret){
            $msg = "Nie można edytować użytkowników których nazwisko nie jest '".$nazwisko."' , edycja użytkownika : '".$sam."' nie jest możliwa w środowisku testowym, zmiany nie zostały zapisane.";
            $this->container->get('session')->getFlashBag()->set('notice', $msg);
            //throw new RedirectResponse($this->container->get('router')->generate('main'));
            throw new \Exception("Nie można edytować użytkowników których nazwisko nie jest '".$nazwisko."' , edycja użytkownika : '".$sam."' nie jest możliwa w środowisku testowym.");
        }
    }
}