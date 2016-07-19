<?php

namespace Parp\SoapBundle\Services;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\DependencyInjection\Container;

class LdapImportService
{
    protected $i = 0;
    protected $container;
    
    
    public function __construct(Container $container)
    {
        $this->container = $container;
        
    }
    
    public function importUsersAction($letter)
    {
        //6h tomeout
        set_time_limit(60*60*6);
        $em = $this->container->get('doctrine')->getManager();
        $ldap = $this->container->get('ldap_service');
        // Sięgamy do AD:
        $ADUsers0 = $ldap->getAllFromAD();
        $namesCols = array('memberOf', 'roles');
        $proccessed = array();
        $ADUsers = [];
        foreach($ADUsers0 as $adu){
            if(strtolower(substr($adu['samaccountname'], 0, 1)) == $letter){
                $ADUsers[] = $adu;
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
            
            $grupyRekrusywnie = $ldap->getAllUserGroupsRecursivlyFromAD($adu['samaccountname']);
            
            foreach($u->getADGroups() as $g){
                $u->removeADGroup($g);
                $g->removeADUser($u);
            }
            foreach($grupyRekrusywnie as $grupa){
                $g = $grupa['dn'];
                $gr = $em->getRepository("ParpSoapBundle:ADGroup")->findOneByCn($g);
                if($gr){
                    $gr->addADUser($u);
                    $u->addADGroup($gr);
                }
            }
            $this->saveToLogFile($adu['samaccountname'], "users.log");
            
            $proccessed[$adu['samaccountname']] = $adu['samaccountname'];
            $em->flush();
        }
        
        
    }
    
    
    public function importGroupsAction()
    {
        //6h tomeout
        set_time_limit(60*60*6);
        $pomijajPola = ["objectguid", "objectsid"];
        $em = $this->container->get('doctrine')->getManager();
        $ldap = $this->container->get('ldap_service');
        // Sięgamy do AD:
        $ADgroups = $this->container->get('ldap_service')->getGroupsFromAD(null);
        $proccessed = array();
        foreach($ADgroups as $k1 => $adg){
            if(is_array($adg)){
                $g = $em->getRepository("ParpSoapBundle:ADGroup")->findOneByCn($adg['cn'][0]);
                if(!$g){
                    $g = new \Parp\SoapBundle\Entity\ADGroup();            
                }
                foreach($adg as $k => $valarr){
                    if(!in_array($k, $pomijajPola)){
                        $set = "set".ucfirst($k);
                        
                        if(method_exists($g, $set)){
                            $val = array();
                            if(is_array($valarr)){
                                for($i = 0; $i < $valarr['count']; $i++){
                                    $v2 = $valarr[$i];
                                    //if($k2 != "count"){
                                        $val[] = $v2;
                                    //}
                                }
                            }else{
                                $val[] = $valarr;
                            }
                            $g->{$set}(implode(";", $val));
                        }
                    }
                }
                $em->persist($g);
                $this->saveToLogFile($g->getCn(), "groups.log");
                $proccessed[$g->getCn()] = $g->getCn();
                $em->flush();
            }
        }
    }
    
    protected function saveToLogFile($msg, $file)
    {
        $dir = __DIR__."/../../../../app/logs";
        if(!file_exists($dir)){
            mkdir($dir);
        }
        $d = new \Datetime();
        $msg = ($this->i++)." : ".$d->format("Y-m-d-H-I-s")." - ".$msg;
        file_put_contents($dir."/".$file, $msg."\r\n", FILE_APPEND);
    }
    
    public function importOUsAction()
    {
        //6h tomeout
        set_time_limit(60*60*6);
        
        $em = $this->container->get('doctrine')->getManager();
        $ldap = $this->container->get('ldap_service');
        // Sięgamy do AD:
        $ADous = $this->container->get('ldap_service')->getOUsFromAD(null);
        $proccessed = array();
        //echo "<pre>1 "; print_r($ADous); echo "</pre>";
        foreach($ADous as $k1 => $adou){
            if(is_array($adou)){
                $g = $em->getRepository("ParpSoapBundle:ADOrganizationalUnit")->findOneByDn($adou['dn']);
                if(!$g){
                    $g = new \Parp\SoapBundle\Entity\ADOrganizationalUnit();            
                }
                foreach($adou as $k => $valarr){
                    $set = "set".ucfirst($k);
                    
                    if(method_exists($g, $set)){
                        $val = array();
                        if(is_array($valarr)){
                            for($i = 0; $i < $valarr['count']; $i++){
                                $v2 = $valarr[$i];
                                //if($k2 != "count"){
                                    $val[] = $v2;
                                //}
                            }
                        }else{
                            $val[] = $valarr;
                        }
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
                $proccessed[$g->getDn()] = $g->getDn();
                $this->saveToLogFile($g->getDn(), "ous.log");
            }
        }
        $em->flush();
        
    }

}