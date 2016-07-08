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
            if($adu['samaccountname'] == 'marcin_lipinski'){
                echo "<pre>"; print_r($adu); echo "</pre>"; die();
            }
        }
        foreach($ADUsers as $adu){
            $u = $em->getRepository("ParpSoapBundle:ADUser")->findOneBySamaccountname($adu['samaccountname']);
            if(!$u){
                $u = new \Parp\SoapBundle\Entity\ADUser();            
            }
            foreach($adu as $k => $v){
                $set = "set".ucfirst($k);
                if(in_array($k, $namesCols)){
                    $set .= "Names";
                    $v = implode(";", $v);
                }
                $u->{$set}($v);
                $em->persist($u);
            }
            
            
            foreach($u->getADGroups() as $g){
                $u->removeADGroup($g);
                $g->removeADUser($u);
            }
            foreach($adu['memberOf'] as $g){
                $gr = $em->getRepository("ParpSoapBundle:ADGroup")->findOneByCn($g);
                if($gr){
                    $gr->addADUser($u);
                    $u->addADGroup($gr);
                }
            }
            
            $proccessed[$adu['samaccountname']] = $adu['samaccountname'];
        }
        $em->flush();
        
        
        
        die("importUsersAction");
    }
    
    
    /**
     * @Route("/groups", name="groups")
     */
    public function importGroupsAction()
    {
        $pomijajPola = ["objectguid", "objectsid"];
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        // Sięgamy do AD:
        $ADgroups = $this->get('ldap_service')->getGroupsFromAD(null);
        $proccessed = array();
        //echo "<pre>"; print_r($ADgroups['count']); echo "</pre>"; die();
        foreach($ADgroups as $k1 => $adg){
                //echo "<pre>"; print_r($k1); echo "</pre>";
                //echo "<pre>"; print_r($k1 !== intval($k1)); echo "</pre>";
            if(is_array($adg)){
                $g = $em->getRepository("ParpSoapBundle:ADGroup")->findOneByCn($adg['cn'][0]);
                if(!$g){
                    $g = new \Parp\SoapBundle\Entity\ADGroup();            
                }
                //echo "<pre>"; print_r($k1); echo "</pre>";
                //echo "<pre>"; print_r((int)$k1); echo "</pre>";
                foreach($adg as $k => $valarr){
                    if(!in_array($k, $pomijajPola)){
                        $set = "set".ucfirst($k);
                        
                        if(method_exists($g, $set)){
                            $val = array();
                            if(is_array($valarr)){
                                for($i = 0; $i < $valarr['count']; $i++){
                                    $v2 = $valarr[$i];
                                    //echo "<pre>0  "; print_r($k2); echo "</pre>";
                                    //if($k2 != "count"){
                                        $val[] = $v2;
                                    //}
                                }
                            }else{
                                $val[] = $valarr;
                            }
                            //echo "<pre>1"; print_r($set); echo "</pre>";
                            //echo "<pre>2"; print_r($valarr); echo "</pre>";
                            //echo "<pre>3"; print_r($val); echo "</pre>";
                            //echo "<pre>4"; print_r(implode(";", $val)); echo "</pre>";
                            $g->{$set}(implode(";", $val));
                        }
                    }
                }
                $em->persist($g);
                
                $proccessed[$g->getCn()] = $g->getCn();
                $em->flush();
            }
        }
        
        
        
        die("importGroupsAction");
    }
    
    
    /**
     * @Route("/ous", name="ous")
     */
    public function importOUsAction()
    {
        
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        // Sięgamy do AD:
        $ADous = $this->get('ldap_service')->getOUsFromAD(null);
        $proccessed = array();
        echo "<pre>1 "; print_r($ADous); echo "</pre>";
        foreach($ADous as $k1 => $adou){
                //echo "<pre>"; print_r($k1); echo "</pre>";
                //echo "<pre>"; print_r($k1 !== intval($k1)); echo "</pre>";
            if(is_array($adou)){
                $g = $em->getRepository("ParpSoapBundle:ADOrganizationalUnit")->findOneByDn($adou['dn']);
                if(!$g){
                    $g = new \Parp\SoapBundle\Entity\ADOrganizationalUnit();            
                }
                //echo "<pre>"; print_r($k1); echo "</pre>";
                //echo "<pre>"; print_r((int)$k1); echo "</pre>";
                foreach($adou as $k => $valarr){
                    $set = "set".ucfirst($k);
                    
                    if(method_exists($g, $set)){
                        $val = array();
                        if(is_array($valarr)){
                            for($i = 0; $i < $valarr['count']; $i++){
                                $v2 = $valarr[$i];
                                //echo "<pre>0  "; print_r($k2); echo "</pre>";
                                //if($k2 != "count"){
                                    $val[] = $v2;
                                //}
                            }
                        }else{
                            $val[] = $valarr;
                        }
                        echo "<pre>1"; print_r($set); echo "</pre>";
                        echo "<pre>2"; print_r($valarr); echo "</pre>";
                        echo "<pre>3"; print_r($val); echo "</pre>";
                        echo "<pre>4"; print_r(implode(";", $val)); echo "</pre>";
                        $g->{$set}(implode(";", $val));
                    }
                }
                $em->persist($g);
                //membersi
                $membersi = $ldap->getUsersFromOU($g->getName());
                if($membersi){
                    $members = array();
                    foreach($membersi as $m){
                        $members[$m['samaccountname']] = $m['samaccountname'];
                    }
                    $g->setMember(implode(";",$members));
                    
                    foreach($g->getADUsers() as $u){
                        $g->removeADUser($u);
                    }
                    foreach($members as $m){
                        $u = $em->getRepository("ParpSoapBundle:ADUser")->findOneBySamaccountname($m);
                        if($u){
                            $g->addADUser($u);
                            $u->addADOU($g);
                        }
                    }
                }
                //echo "<pre>1"; print_r($members); echo "</pre>";             
                $proccessed[$g->getDn()] = $g->getDn();
            }
        }
        $em->flush();
        
        
        die("importOUsAction");
        
    }
}