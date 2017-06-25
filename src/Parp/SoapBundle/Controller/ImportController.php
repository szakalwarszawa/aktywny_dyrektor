<?php

namespace Parp\SoapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Import controller.
 *
 * @Route("/adimport")
 */
class ImportController extends Controller
{
    /**
     * @Route("/users/{letter}", name="users")
     */
    public function importUsersAction($letter)
    {
        $this->get('ldap_import')->importUsersAction($letter);
        //die("importUsersAction");
    }
    
    
    /**
     * @Route("/groups", name="groups")
     */
    public function importGroupsAction()
    {
        $this->get('ldap_import')->importGroupsAction();
        //die("importGroupsAction");
    }
    
    /**
     * @Route("/ous", name="ous")
     */
    public function importOUsAction()
    {
        $this->get('ldap_import')->importOUsAction();
        //die("importOUsAction");
    }
}
