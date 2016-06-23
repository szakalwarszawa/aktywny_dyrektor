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
     * @Route("/users", name="users")
     */
    public function importUsersAction()
    {
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        // Sięgamy do AD:
        $ADUsers = $ldap->getAllFromAD();
        $namesCols = array('memberOf', 'roles');
        $proccessed = array();
        foreach($ADUsers as $adu){
            $u = $em->getRepository("ParpSoapBundle:ADUser")->findOneBySamaccountname($adu['samaccountname']);
            if(!$u){
                $u = new \Parp\SoapBundle\Entity\ADUser();            
            }
            foreach($adu as $k => $v){
                $set = "set".ucfirst($k);
                if(in_array($k, $namesCols)){
                    $set .= "Names";
                    $v = implode(",", $v);
                }
                $u->{$set}($v);
                $em->persist($u);
            }
            
            $proccessed[$adu['samaccountname']] = $adu['samaccountname'];
        }
        $em->flush($u);
        
        
        
        die("importUsersAction");
    }
}