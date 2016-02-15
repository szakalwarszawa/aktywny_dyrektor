<?php

namespace Parp\SoapBundle\Services;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;
//use Memcached;
use Memcached;

class LdapAdminService
{

    protected $securityContext;
    protected $AdminUser = "aktywny_dyrektor";
    protected $AdminPass = "Gq32hr9cAL";
    protected $ad_host;
    protected $ad_domain;
    protected $container;
    protected $patch;
    protected $useradn ;

    public function __construct(SecurityContextInterface $securityContext, Container $container, EntityManager $OrmEntity)
    {

        $this->doctrine = $OrmEntity;
        $this->securityContext = $securityContext;
        $this->container = $container;
        $this->ad_host = $this->container->getParameter('ad_host');
        $this->ad_domain = '@' . $this->container->getParameter('ad_domain');
        $this->AdminUser = $this->container->getParameter('ad_user');
        $this->AdminPass = $this->container->getParameter('ad_password');

        $tab = explode(".", $this->container->getParameter('ad_domain'));
        $env = $this->container->get('kernel')->getEnvironment();
        
        $this->useradn = $this->container->getParameter('ad_ou');
        if ($env === 'prod') {
            $this->patch = ',DC=' . $tab[0] . ',DC=' . $tab[1];
        } else {
            //$this->patch = ',OU=Test ,DC=' . $tab[0] . ',DC=' . $tab[1];
            $this->patch = ',DC=' . $tab[0] . ',DC=' . $tab[1];
        }
        //die('a');
    }

    public function getUserFromAD($samaccountname = null, $cnname = null)
    {
        //$ldapconn = ldap_connect('srv-adc01.parp.local');
        //$ldapdomain = "@parp.local";
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;
//        $memcached = new \Memcached;
//        $memcached->addServer('localhost', 11211);
//        $fromMemcached = $memcached->get('ldap-detail-'.$samaccountname);
//        if($fromMemcached){
//            return $fromMemcached;
//
//        }

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        if ($samaccountname) {
            $searchString = "(&(samaccountname=" . $samaccountname . ")(objectClass=person))";
        } elseif ($cnname) {
//            $cnname = substr($cnname,3,stripos($cnname,',')-3);
            $searchString = $cnname;
//            echo $searchString;
        } else {
            $searchString = "(&(samaccountname=)(objectClass=person))";
        }
echo "$userdn";
        $search = ldap_search($ldapconn, $userdn, $searchString, array(
            "name",
            "initials",
            "title",
            "info",
            "department",
            "description",
            "division",
            "lastlogon",
            "samaccountname",
            "manager",
            "thumbnailphoto",
            "accountExpires",
            "useraccountcontrol",
            "distinguishedName",
            "cn",
        ));
        $tmpResults = ldap_get_entries($ldapconn, $search);

        ldap_unbind($ldapconn);

        $result = array();

        $i = 0;
        foreach ($tmpResults as $tmpResult) {
            if ($tmpResult["samaccountname"]) {
                $result[$i]["samaccountname"] = $tmpResult["samaccountname"][0];
                $result[$i]["name"] = isset($tmpResult["name"][0]) ? $tmpResult["name"][0] : "";
                $result[$i]["initials"] = isset($tmpResult["initials"][0]) ? $tmpResult["initials"][0] : "";
                $result[$i]["title"] = isset($tmpResult["title"][0]) ? $tmpResult["title"][0] : "";
                $result[$i]["info"] = isset($tmpResult["info"][0]) ? $tmpResult["info"][0] : "";
                $result[$i]["department"] = isset($tmpResult["department"][0]) ? $tmpResult["department"][0] : "";
                $result[$i]["description"] = isset($tmpResult["description"][0]) ? $tmpResult["description"][0] : "";
                $result[$i]["division"] = isset($tmpResult["division"][0]) ? $tmpResult["division"][0] : "";
                $result[$i]["manager"] = isset($tmpResult["manager"][0]) ? $tmpResult["manager"][0] : "";
                $result[$i]["thumbnailphoto"] = isset($tmpResult["thumbnailphoto"][0]) ? $tmpResult["thumbnailphoto"][0] : "";
                $result[$i]["distinguishedname"] = $tmpResult["distinguishedname"][0];
                $result[$i]["cn"] = $tmpResult["cn"][0];
                $i++;
            }
        }

//        $memcached->set('ldap-detail-'.$samaccountname,$result);
        return $result;
    }

    public function saveEntity($ldapUser, $person)
    {

        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn)
            throw new Exception('Brak połączenia z serwerem domeny!');
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;

        $ldapbind = ldap_bind($ldapconn, $this->AdminUser . $ldapdomain, $this->AdminPass);
             
        $dn = $ldapUser;
        $entry = array();

        if ($person->getCn()) {
            $entry['cn'] = $person->getCn();
        }
        if ($person->getAccountExpires()) {
            $entry['accountExpires'] = $this->UnixtoLDAP($person->getAccountExpires()->getTimestamp());
        }
        if ($person->getInfo()) {
            $entry['info'] = $person->getInfo();
            // obsłuz miane atrybuty division
            $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneByName($person->getInfo());
            $entry['division'] =  $section->getShortname();
        }
        if ($person->getManager()) {
            $manager = $person->getManager();
            if (!empty($manager)) {
                // znajdz sciezke przelozonego
                $cn = $manager;
                $searchString = "(&(cn=" . $cn . ")(objectClass=person))";
                //$searchString = "(&(samaccountname=" . $samaccountname . ")(objectClass=person))";

                $search = ldap_search($ldapconn, $userdn, $searchString, array(
                    "name",
                    "initials",
                    "title",
                    "info",
                    "department",
                    "description",
                    "division",
                    "lastlogon",
                    "samaccountname",
                    "manager",
                    "thumbnailphoto",
                    "accountExpires",
                    "useraccountcontrol",
                    "distinguishedName",
                ));
                $tmpResults = ldap_get_entries($ldapconn, $search);
                $entry['manager'] = $tmpResults[0]['distinguishedname'][0];
            }
        }
        if ($person->getTitle()) {
            $entry['title'] = $person->getTitle();
        }
        if ($person->getInitials()) {
            $entry['initials'] = $person->getInitials();
        }

        if ($person->getDepartment()) {
            $entry['department'] = $person->getDepartment();
            $department = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($person->getDepartment());
            if (!empty($department)) {
                $entry['description'] = $department->getShortname();
            }
        }
        print_r($entry);
       echo ".".$dn.".";
        ldap_modify($ldapconn, $dn, $entry);


        //zmiana kontenera - obsługujemy nie modyfikacja
        // zmiana departamentu musi byc ostnia operacją ponieważ zmienimi rownież
        // kontener pracownika. Jezeli zmodyfikujemy go wczecniej to pozowatłe operacje mogą 
        // nie znaleśc obiektu w ad (zmieniamy przeciez distinguishedName!).
        if ($person->getDepartment()) {
            // zmien ds pracownika
            $userAD = $this->getUserFromAD($person->getSamaccountname());
            $parent = 'OU=' . $entry['description'] . ',' . $userdn;

            $b = ldap_rename($ldapconn, $person->getDistinguishedName(), "CN=" . $userAD[0]['name'], $parent, TRUE);
            //var_dump($b);
        }
        ldap_unbind($ldapconn);

        //$person->setIsImplemented(1);
        $this->doctrine->persist($person);
        $this->doctrine->flush();
    }

    public function createEntity($person)
    {

        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = $this->ad_domain;
        //$userdn = "OU=Test";
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $accountExpires = $person->getAccountExpires();
        $dn = $person->getDistinguishedName();

        $entry = array();
        $entry['cn'] = $person->getCn();
        if (!empty($accountExpires)) {
            //$entry['accountExpires'] = $this->UnixtoLDAP($accountExpires->getTimestamp());
        } else {
           // $entry['accountExpires'] = 0;
        }

        $manager = $person->getManager();
        if (!empty($manager)) {
            // znajdz sciezke przelozonego
            $cn = $manager;
            $searchString = "(&(cn=" . $cn . ")(objectClass=person))";
            //$searchString = "(&(samaccountname=" . $samaccountname . ")(objectClass=person))";

            $search = ldap_search($ldapconn, $userdn, $searchString, array(
                "name",
                "initials",
                "title",
                "info",
                "department",
                "description",
                "division",
                "lastlogon",
                "samaccountname",
                "manager",
                "thumbnailphoto",
                "accountExpires",
                "useraccountcontrol",
                "distinguishedName",
            ));
            $tmpResults = ldap_get_entries($ldapconn, $search);
            $entry['manager'] = $tmpResults[0]['distinguishedname'][0];
        }
        $tab = explode(' ', $entry['cn']);
        $entry['sn'] = count($tab) > 1 ? $tab[1] : "";
        $entry['givenName'] = $tab[0];
        $entry['name'] = $entry['cn'];
        $entry['userPrincipalName'] = $person->getSamaccountname() . $this->ad_domain;
        $entry['department'] = $person->getDepartment();
        $entry['division'] = $person->getDivision();
        $entry['title'] = $person->getTitle();
        $entry['distinguishedname'] = $person->getDistinguishedname();
        $entry['initials'] = $person->getInitials();
        $entry['samaccountname'] = $person->getSamaccountname();
        $entry['objectClass']['0'] = "top";
        $entry['objectClass']['1'] = "person";
        $entry['objectClass']['2'] = "organizationalPerson";
        $entry['objectClass']['3'] = "user";
        $entry['displayName'] = $entry['cn'];
        $entry['company'] = 'Polska Agencja Rozwoju Przedsiębiorczości';
        // if (empty($accountExpires)) {
        $entry["useraccountcontrol"] = 544; // włączenie konta i wymuszenie zmiany hasla
        $entry["info"] = $person->getInfo();
        $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneByName($person->getInfo());
        $entry['division'] = $section->getShortname();

        $description = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($person->getDepartment());
        if (!empty($description)) {
            $entry['description'] = $description->getShortname();
        }
        print_r($dn);
        print_r($entry);
        ldap_add($ldapconn, $dn, $entry);
        
         /*
          $dn = "CN=Tomasz Bolek,OU=BI,OU=Test,DC=boniek,DC=test";
          $newuser["objectClass"]['0'] = "top";
          $newuser["objectClass"]['1'] = "person";
          $newuser["objectClass"]['2'] = "organizationalPerson";
          $newuser["objectClass"]['3'] = "user";
          $newuser["cn"] = "Tomasz Bolek";
          //$newuser["uid"] = "nuser";
          //$newuser["sn"] = "Bolek";
          // add data to directory
          $r = ldap_add($ldapconn, $dn, $newuser);
        */

        ldap_unbind($ldapconn);
      
        
        //$person->setIsImplemented(1);
        $this->doctrine->persist($person);
        $this->doctrine->flush();
    }

    protected function LDAPtoUnix($ldap_ts)
    {
        return ($ldap_ts / 10000000) - 11644473600;
    }

    protected function UnixtoLDAP($unix_ts)
    {
        return sprintf("%.0f", ($unix_ts + 11644473600) * 10000000);
    }

}
