<?php

namespace ParpV1\SoapBundle\Services;

use ParpV1\SoapBundle\Entity\ADGroup;
use ParpV1\SoapBundle\Entity\ADOrganizationalUnit;
use ParpV1\SoapBundle\Entity\ADUser;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class LdapImportService
 * @package ParpV1\SoapBundle\Services
 */
class LdapImportService
{
    protected $i = 0;
    protected $container;

    /**
     * LdapImportService constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $letter
     */
    public function importUsersAction($letter)
    {
        set_time_limit(60*60*6);
        $manager = $this->container->get('doctrine')->getManager();
        $ldap = $this->container->get('ldap_service');
        // Sięgamy do AD:
        $ADUsers0 = $ldap->getAllFromAD(true);
        $namesCols = array('memberOf', 'roles');
        $proccessed = array();
        $ADUsers = [];
        foreach ($ADUsers0 as $ADUser) {
            if (strtolower(substr($ADUser['samaccountname'], 0, 1)) === $letter) {
                $ADUsers[] = $ADUser;
            }
        }
        foreach ($ADUsers as $ADUser) {
            $ADUser = $manager->getRepository("ParpSoapBundle:ADUser")->findOneBy([
                'samaccountname' => $ADUser['samaccountname'],
            ]);

            if (null === $ADUser) {
                $ADUser = new ADUser();
            }

            foreach ($ADUser as $key => $item) {
                $set = "set".ucfirst($key);
                if (in_array($key, $namesCols)) {
                    $set .= 'Names';
                    $item = implode(";", $item);
                }
                $ADUser->{$set}($item);
                $manager->persist($ADUser);
            }
            
            $grupyRekrusywnie = $ldap->getAllUserGroupsRecursivlyFromAD($ADUser['samaccountname']);
            
            foreach ($ADUser->getADGroups() as $ADGroup) {
                $ADUser->removeADGroup($ADGroup);
                $ADGroup->removeADUser($ADUser);
            }

            foreach ($grupyRekrusywnie as $grupa) {
                $ADGroup = $grupa['dn'];

                /** @var ADGroup $group */
                $group = $manager->getRepository("ParpSoapBundle:ADGroup")->findOneBy(['cn' => $ADGroup]);
                if (null !== $group) {
                    $group->addADUser($ADUser);
                    $ADUser->addADGroup($group);
                }
            }

            $this->saveToLogFile($ADUser['samaccountname'], "users.log");
            
            $proccessed[$ADUser['samaccountname']] = $ADUser['samaccountname'];
            $manager->flush();
        }
    }

    public function importGroupsAction()
    {
        set_time_limit(60*60*6);
        $pomijajPola = ["objectguid", "objectsid"];
        $manager = $this->container->get('doctrine')->getManager();
        // Sięgamy do AD:
        $ADgroups = $this->container->get('ldap_service')->getGroupsFromAD(null);
        $proccessed = array();
        foreach ($ADgroups as $k1 => $adg) {
            if (is_array($adg)) {
//                $g = $u = null; //$em->getRepository("ParpSoapBundle:ADGroup")->findOneByCn($adg['cn'][0]);
                $ADGroup = $manager->getRepository("ParpSoapBundle:ADGroup")->findOneBy(['cn' => $adg['cn'][0]]);
                if (null === $ADGroup) {
                    $ADGroup = new ADGroup();
                }

                foreach ($adg as $k => $valarr) {
                    if (!in_array($k, $pomijajPola)) {
                        $set = "set".ucfirst($k);
                        
                        if (method_exists($ADGroup, $set)) {
                            $val = array();
                            if (is_array($valarr)) {
                                for ($i = 0; $i < $valarr['count']; $i++) {
                                    $v2 = $valarr[$i];
                                    //if($k2 != "count"){
                                        $val[] = $v2;
                                    //}
                                }
                            } else {
                                $val[] = $valarr;
                            }
                            $ADGroup->{$set}(implode(";", $val));
                        }
                    }
                }
                $manager->persist($ADGroup);
                $this->saveToLogFile($ADGroup->getCn(), "groups.log");
                $proccessed[$ADGroup->getCn()] = $ADGroup->getCn();
                $manager->flush();
            }
        }
    }

    /**
     * @param $msg
     * @param $file
     */
    protected function saveToLogFile($msg, $file)
    {
        $dir = __DIR__."/../../../../app/logs";
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $datetime = new \Datetime();
        $msg = ($this->i++)." : ".$datetime->format("Y-m-d-H-I-s")." - ".$msg;
        file_put_contents($dir."/".$file, $msg."\r\n", FILE_APPEND);
    }
    
    public function importOUsAction()
    {
        set_time_limit(60*60*6);
        
        $manager = $this->container->get('doctrine')->getManager();
        $ldap = $this->container->get('ldap_service');
        // Sięgamy do AD:
        $ADous = $this->container->get('ldap_service')->getOUsFromAD(null);
        $proccessed = array();
        //echo "<pre>1 "; print_r($ADous); echo "</pre>";
        foreach ($ADous as $k1 => $adou) {
            if (is_array($adou)) {
//                $g = $ADUser = null;
// //$em->getRepository("ParpSoapBundle:ADOrganizationalUnit")->findOneByDn($adou['dn']);
                $ADOrganizationalUnit = $manager
                    ->getRepository("ParpSoapBundle:ADOrganizationalUnit")
                    ->findOneBy(['dn' => $adou['dn']]);
                if (null === $ADOrganizationalUnit) {
                    $ADOrganizationalUnit = new ADOrganizationalUnit();
                }
                foreach ($adou as $k => $valarr) {
                    $set = "set".ucfirst($k);
                    
                    if (method_exists($ADOrganizationalUnit, $set)) {
                        $val = array();
                        if (is_array($valarr)) {
                            for ($i = 0; $i < $valarr['count']; $i++) {
                                $v2 = $valarr[$i];
                                //if($k2 != "count"){
                                    $val[] = $v2;
                                //}
                            }
                        } else {
                            $val[] = $valarr;
                        }
                        $ADOrganizationalUnit->{$set}(implode(";", $val));
                    }
                }
                $manager->persist($ADOrganizationalUnit);

                $membersi = $ldap->getUsersFromOU($ADOrganizationalUnit->getName());
                if (null !== $membersi) {
                    $members = array();
                    foreach ($membersi as $member) {
                        $members[$member['samaccountname']] = $member['samaccountname'];
                    }
                    $ADOrganizationalUnit->setMember(implode(";", $members));
                    
                    foreach ($ADOrganizationalUnit->getADUsers() as $ADUser) {
                        $ADOrganizationalUnit->removeADUser($ADUser);
                    }
                    foreach ($members as $member) {
                        $ADUser = $manager
                            ->getRepository("ParpSoapBundle:ADUser")
                            ->findOneBy(['samaccountname' => $member])
                        ;

                        if (null !== $ADUser) {
                            $ADOrganizationalUnit->addADUser($ADUser);
                            $ADUser->addADOU($ADOrganizationalUnit);
                        }
                    }
                }
                $proccessed[$ADOrganizationalUnit->getDn()] = $ADOrganizationalUnit->getDn();
                $this->saveToLogFile($ADOrganizationalUnit->getDn(), "ous.log");
            }
        }
        $manager->flush();
    }
}
