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

    protected $debug = 0;
    protected $securityContext;
    protected $AdminUser = "aktywny_dyrektor";
    protected $AdminPass = "F4UCorsair";
    protected $ad_host;
    protected $ad_domain;
    protected $container;
    protected $patch;
    protected $useradn ;
    protected $hostId = 3;
    public $output;

    public function __construct(SecurityContextInterface $securityContext, Container $container, EntityManager $OrmEntity)
    {
        error_reporting(0);
        //ini_set('error_reporting', E_ALL);
        $this->doctrine = $OrmEntity;
        $this->securityContext = $securityContext;
        $this->container = $container;
        //$this->ad_host = $this->container->getParameter('ad_host'.($this->hostId ? $this->hostId : ""));
        $this->switchServer();
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
    
    public function switchServer($error = ""){
        $prevHost = $this->ad_host;
        $this->hostId++;
        if($this->hostId > 3){
            $this->hostId = 1;
        }
        $this->ad_host = $this->container->getParameter('ad_host'.($this->hostId > 1 ? $this->hostId : ""));
        if($error != ""){
            $msg = "Nie udało się połączyć z serwerem $prevHost z powodu błędu '$error', przełączam na serwer {$this->ad_host}";
            //print_r("\n".$this->ad_host."\n");
            $this->output->writeln('<info>'.$msg.'</info>', false);
        }
    }
    public function getUserFromAD($samaccountname = null, $cnname = null, $query = null)
    {
        $maxConnections = $this->container->getParameter('maximum_ldap_reconnects');
        $ldapstatus = "";
        $i = 0;
        $result = null;
        do{
            $i++;
            try{
                $result = $this->getUserFromADInt($samaccountname, $cnname, $query);
                $ldapstatus = "Success";
            }catch(\Exception $e){
                $ldapstatus = ($e->getMessage());
            }
            //print_r("\n $ldapstatus \n");
            if($ldapstatus != "Success"){
                $this->switchServer($ldapstatus);
            }
        }while($ldapstatus != "Success" && $i < $maxConnections);
        return $result;
    }
    public function getUserFromADInt($samaccountname = null, $cnname = null, $query = null)
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
        } elseif($query) {
            $searchString = "(&(".$query.")(objectClass=person))";
        }else {
            $searchString = "(&(samaccountname=)(objectClass=person))";
        }
//echo "$searchString";
        $search = ldap_search($ldapconn, $userdn, $searchString, array(
            "name",
            "mail",
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
            'memberOf'
        ));
        $tmpResults = ldap_get_entries($ldapconn, $search);
        //print_r($userdn); die();
        $ldapstatus = ldap_error($ldapconn);
        //print_r($ldapstatus); die();
        if($ldapstatus != "Success"){
            $e = new \Exception($ldapstatus);
            throw $e;
        }
        ldap_unbind($ldapconn);
        $result = array();

        $i = 0;
        foreach ($tmpResults as $tmpResult) {
            if ($tmpResult["samaccountname"]) {
                $result[$i]["isDisabled"] =  $tmpResult["useraccountcontrol"][0] == "546";
                $result[$i]["samaccountname"] = $tmpResult["samaccountname"][0];
                $result[$i]["name"] = isset($tmpResult["name"][0]) ? $tmpResult["name"][0] : "";
                $result[$i]["email"] = isset($tmpResult["mail"][0]) ? $tmpResult["mail"][0] : "";
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
                $result[$i]["memberOf"] = $this->parseMemberOf($tmpResult);
                $i++;
            }
        }
        
//        $memcached->set('ldap-detail-'.$samaccountname,$result);
        return $result;
    }

    protected function parseMemberOf($res){
        $ret = array();
        $gr = isset($res["memberof"]) ? $res["memberof"]: array();
        foreach($gr as $k => $g){
            if($k !== "count"){
                $p = explode(",", $g);
                $p2 = str_replace("CN=", "", $p[0]);
                $ret[] = $p2;
            }
        }
        return $ret;
    }
    public function saveEntity($ldapUser, $person)
    {
        $this->container->get('adcheck_service')->checkIfUserCanBeEdited($person->getSamaccountname());
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

        
        if ($person->getAccountExpires()) {
            $d = $this->UnixtoLDAP($person->getAccountExpires()->getTimestamp());
            if($person->getAccountExpires()->format("Y") == "3000"){
               $d = "9223372036854775807";
            }
            $entry['accountExpires'] = $d;
        }
        if ($person->getInfo()) {
            $entry['info'] = $person->getInfo();
            // obsłuz miane atrybuty division
            $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneByName($person->getInfo());
            //print_r($person->getInfo());
            //print_r($section);
            $entry['division'] =  $section->getName();//getShortname();
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
        $entry['initials'] = array();
        if ($person->getInitials()) {
            //hack by dalo sie puste inicjaly wprowadzic
            if($person->getInitials() == "puste" || $person->getInitials() == ""){
                $entry['initials'] = array();
            }else{
                $entry['initials'] = $person->getInitials();
            }
            //echo(".".$person->getInitials().".");
        }

        $department = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($person->getDepartment());
        if ($person->getDepartment()) {
            $entry['department'] = $person->getDepartment();
            if (!empty($department)) {
                $entry['description'] = $department->getShortname();
            }
        }
        
        $userAD = $this->getUserFromAD($person->getSamaccountname());
        
        if($person->getMemberOf() != ""){
            $znak = substr($person->getMemberOf(), 0, 1);               
            $g = substr($person->getMemberOf(), 1);
            //print_r($userAD[0]['memberOf']);
            if($znak == "+" && !in_array($g, $userAD[0]['memberOf'])){ 
                $addtogroup = "CN=".$g.",OU=BA,".$userdn;            
                ldap_mod_add($ldapconn, $addtogroup, array('member' => $dn));
            }elseif($znak == "-" && in_array($g, $userAD[0]['memberOf'])){
                $addtogroup = "CN=".$g.",OU=BA,".$userdn; 
                ldap_mod_del($ldapconn, $addtogroup, array('member' => $dn));
            }else{
                echo('Mialem '.($znak == "+" ? "dodawac" : "zdejmowac")." z grupy  ".$g." ale user w niej jest: ".in_array($g, $userAD[0]['memberOf'])."\n");
            }
        }
        if ($person->getIsDisabled() !== null) {
            //$ac = 544;//$tmpResults[0];
            //print_r($ac);
            //$enable =($ac & ~2);
            //print_r($enable);die();
            $entry['useraccountcontrol'][0] = $person->getIsDisabled() ? 546 : 544;
            $sn = "Konto aktywowane";
            if (!empty($department)) {
                $sn = $department->getShortname();
            }
            
            if($person->getIsDisabled()){
                $entry['description'] = "Konto wyłączone bo: ".$person->getDisableDescription();
            }else{
                $entry['description'] = $sn;                
            }
        }
        //print_r($entry);
/*

        print_r($entry);
        die();
        
        print_r($userdn);
        print_r($entry);
        print_r($dn);

        die();
*/
        if(count($entry) > 0){
            if($this->debug){
                echo "<pre>";print_r($dn);
                print_r($entry);echo "</pre>";
                //die();
            }
            
            ldap_modify($ldapconn, $dn, $entry);
            
            $ldapstatus = ldap_error($ldapconn);    
            if($ldapstatus != "Success"){
                if($this->debug){
                    die($ldapstatus);    
                }
                return $ldapstatus;
            }
        }


        //zmiana kontenera - obsługujemy nie modyfikacja
        // zmiana departamentu musi byc ostnia operacją ponieważ zmienimi rownież
        // kontener pracownika. Jezeli zmodyfikujemy go wczecniej to pozowatłe operacje mogą 
        // nie znaleśc obiektu w ad (zmieniamy przeciez distinguishedName!).
        if ($person->getDepartment()) {
            // zmien ds pracownika
            $userAD = $this->getUserFromAD($person->getSamaccountname());
            $parent = 'OU=' . $entry['description'] . ',' . $userdn;
                        
            $cn = $userAD[0]['name'];
            //na koncu razem z kontenerem zmieniamy cn bo wtedy nic nie znajdzie w ad
            if ($person->getCn()) {
                $cn = $person->getCn();
            }
            $b = ldap_rename($ldapconn, $person->getDistinguishedName(), "CN=" . $cn, $parent, TRUE);
            
            $ldapstatus = ldap_error($ldapconn);
            //var_dump($b);
        }elseif($person->getCn()){
            //zmieniamy tylko cn
            $cn = $person->getCn();
            $b = ldap_rename($ldapconn, $person->getDistinguishedName(), "CN=" . $cn, null, TRUE);
            
            $ldapstatus = ldap_error($ldapconn);
        }
        
        ldap_unbind($ldapconn);

        //$person->setIsImplemented(1);
        $this->doctrine->persist($person);
        //$this->doctrine->flush();
        return $ldapstatus;
    }

    public function createEntity($person)
    {
        $this->container->get('adcheck_service')->checkIfUserCanBeEdited($person->getSamaccountname());
        
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
            $entry['accountExpires'] = $this->UnixtoLDAP($accountExpires->getTimestamp());
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
        $entry["info"] = "aaa";//$person->getInfo();
        $section = $this->doctrine->getRepository('ParpMainBundle:Section')->findOneByName($person->getInfo());
        if($section)
            $entry['division'] = $section->getName();//$section->getShortname();
        else{
            $entry['division'] = 'SD';
        }
        $description = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($person->getDepartment());
        if (!empty($description)) {
            $entry['description'] = $description->getShortname();
        }
        $newuser_plaintext_password = "F4UCorsair";
        //$entry['userPassword'] = '{MD5}' . base64_encode(pack('H*',md5($newuser_plaintext_password)));
        if($this->debug){
            echo "<pre>";print_r($dn);
            print_r($entry);echo "</pre>";
        }
        ldap_add($ldapconn, $dn, $entry);
        $ldapstatus = ldap_error($ldapconn);
        if($this->debug){
            
            die($ldapstatus);
        }
        //print_r("\r\n".$errno."\r\n");
        //die();
        
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
        //$this->doctrine->flush();
        return $ldapstatus;
    }

    protected function LDAPtoUnix($ldap_ts)
    {
        return ($ldap_ts / 10000000) - 11644473600;
    }

    protected function UnixtoLDAP($unix_ts)
    {
        return sprintf("%.0f", ($unix_ts + 11644473600) * 10000000);
    }
    
    public function syncDepartamentsOUs(){
        
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
        
        
        $em = $this->container->get('doctrine')->getManager();
        $deps = $em->getRepository('ParpMainBundle:Departament')->findAll();
        foreach($deps as $dep){
            if($dep->getShortname()){
                $userdn = "OU=".$dep->getShortname().", ".$this->useradn . $this->patch;
                $filter="(objectClass=organizationalunit)"; 
                $justthese = array("dn", "ou"); 
                $sr=ldap_search($ldapconn, $userdn, $filter, $justthese); 
                $info = ldap_get_entries($ldapconn, $sr); 
                
                if($info["count"] > 0){
                    
                    ldap_free_result($sr); 
                }else{ 
                    
                    ldap_free_result($sr);
                    $ldapstatus2 = ldap_error($ldapconn);
                    $res = ldap_add($ldapconn, "OU=".$dep->getShortname().", ".$this->useradn . $this->patch, array(
                        'ou' => $dep->getShortname(),
                        'objectClass' => 'organizationalUnit',
                        'l' => 'location'
                    )); 
                    $ldapstatus = ldap_error($ldapconn);
                    //var_dump("Nie ma OU", $userdn."<br>", $info["count"]."<br>".$ldapstatus2."<br>".$ldapstatus."<br>".$res."<br>"."<br>");  
                    
                }
                
            }
        }
        ldap_unbind($ldapconn);  
        
        //echo "Zrobilem swoje ";
         ///////////////
         
    }

}
