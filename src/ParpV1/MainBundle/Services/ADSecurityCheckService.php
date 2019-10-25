<?php

/**
 * Description of RightsServices
 *
 * @author tomasz_bonczak
 */

namespace ParpV1\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use ParpV1\MainBundle\Entity\UserUprawnienia;
use ParpV1\MainBundle\Entity\UserGrupa;
use Symfony\Component\HttpFoundation\RedirectResponse;
use ParpV1\MainBundle\Exception\SecurityTestException;

class ADSecurityCheckService
{
    protected $container;
    public function __construct(Container $container)
    {
        
        $this->container = $container;
    }
    
    public function checkIfUserCanBeEdited($sam)
    {
        $ps = explode("_", $sam);
        
        $nazwisko = $this->container->getParameter('blokada_na_zapis_tylko_userow_o_tym_nawisku');
        if ($nazwisko == null || $nazwisko == "") {
            return true;
        }
        if (count($ps) > 1) {
            $nazwiska = explode(",", $nazwisko);
            $ret = false;
            foreach ($nazwiska as $n) {
                $ret = $ret || $ps[1] == $n;
            }
            if (!$ret) {
                $msg = "Nie można edytować użytkowników których nazwisko nie jest '" . $nazwisko . "' , edycja użytkownika : '" . $sam . "' nie jest możliwa w środowisku testowym, zmiany nie zostały zapisane.";
                $this->container->get('session')->getFlashBag()->set('notice', $msg);
                //throw new RedirectResponse($this->container->get('router')->generate('main'));
                throw new SecurityTestException("Nie można edytować użytkowników których nazwisko nie jest '" . $nazwisko . "' , edycja użytkownika : '" . $sam . "' nie jest możliwa w środowisku testowym.", 777);
            }
        } else {
        }
    }
}
