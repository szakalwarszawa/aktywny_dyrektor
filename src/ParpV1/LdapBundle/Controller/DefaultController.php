<?php

namespace ParpV1\LdapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use ParpV1\LdapBundle\Services\LdapConnection;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\LdapBundle\Constraints\SearchBy;
use ParpV1\LdapBundle\Service\LdapFetch;
use ParpV1\MainBundle\Constants\AdUserConstants;

class DefaultController extends Controller
{
    /**
     * @Route("/hellox/{name}")
     */
    public function indexAction($name)
    {
        $ldapFetch = $this->get('ldap_fetch');

        $ldapUser = $ldapFetch
            ->fetchAdUser('konrad_szelepusta', SearchBy::LOGIN, LdapFetch::FULL_USER_OBJECT)
        ;

        $ldapUser->updateAttribute(AdUserConstants::SEKCJA_SKROT, 'BI.SORO');

            VarDumper::dump($ldapUser);
        die;
    }
}
