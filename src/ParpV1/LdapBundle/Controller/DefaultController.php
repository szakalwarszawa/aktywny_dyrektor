<?php

namespace ParpV1\LdapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use ParpV1\LdapBundle\Services\LdapConnection;
use Symfony\Component\VarDumper\VarDumper;

class DefaultController extends Controller
{
    /**
     * @Route("/hellox/{name}")
     */
    public function indexAction($name)
    {
        $ldapConnection = $this->get('ldap_connection');

        $auth = $ldapConnection
            ->authLdap('','');


            VarDumper::dump($auth);
        die;
        return array('name' => $name);
    }
}
