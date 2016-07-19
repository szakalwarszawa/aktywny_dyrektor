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
    protected $protocol = ""; //"ldap://";
    protected $port = 389;//636;
    protected $debug = 0;
    protected $securityContext;
    protected $AdminUser = "aktywny_dyrektor";
    protected $AdminPass = "F4UCorsair";
    protected $grupyOU = "PARP Grupy";
    protected $ad_host;
    protected $ad_domain;
    protected $container;
    protected $patch;
    protected $useradn ;
    protected $hostId = 3;
    protected $adldap;
    public $output;

    public function __construct(SecurityContextInterface $securityContext, Container $container, EntityManager $OrmEntity)
    {
        error_reporting(0);
        ini_set('error_reporting', E_ALL);
        ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        
        // Attempting fix from http://www.php.net/manual/en/ref.ldap.php#77553
        putenv('LDAPTLS_REQCERT=never');



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
        
        $configuration = array(
            //'user_id_key' => 'samaccountname',
            'account_suffix' => $this->ad_domain,
            //'person_filter' => array('category' => 'objectCategory', 'person' => 'person'),
            'base_dn' => 'DC=' . $tab[0] . ',DC=' . $tab[1],
            'domain_controllers' => array($this->container->getParameter('ad_host'),$this->container->getParameter('ad_host2'),$this->container->getParameter('ad_host3')),
            'admin_username' => $this->AdminUser,
            'admin_password' => $this->AdminPass,
            //'real_primarygroup' => true,
            //'use_ssl' => false,
            //'use_tls' => false,
            //'recursive_groups' => true,
            'ad_port' => '389',
            //'sso' => false,
        );
        $this->adldap = new \Adldap\Adldap($configuration);
        
        
        
        //die('a');
    }
    
    public function switchServer($error = ""){
        $prevHost = $this->ad_host;
        $this->hostId++;
        if($this->hostId > 3){
            $this->hostId = 1;
        }
        
        $this->ad_host = $this->protocol.$this->container->getParameter('ad_host'.($this->hostId > 1 ? $this->hostId : ""));//.":".$this->port;
        if($error != ""){
            $msg = "Nie udało się połączyć z serwerem $prevHost z powodu błędu '$error', przełączam na serwer {$this->ad_host}";
            //print_r("\n".$this->ad_host."\n");
            echo '<info>'.$msg.'</info>';
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
            if($ldapstatus != "Success"){
                $this->switchServer($ldapstatus);
            }
        }while($ldapstatus != "Success" && $i < $maxConnections);
        return $result;
    }
    public function getUserFromADInt($samaccountname = null, $cnname = null, $query = null)
    {
        $ldapconn = ldap_connect($this->ad_host, $this->port);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->AdminUser;
        $ldap_password = $this->AdminPass;

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);
        
        if ($samaccountname) {
            $searchString = "(&(samaccountname=" . $samaccountname . ")(objectClass=person))";
        } elseif ($cnname) {

            $searchString = $cnname;

        } elseif($query) {
            $searchString = "(&(".$query.")(objectClass=person))";
        }else {
            $searchString = "(&(samaccountname=)(objectClass=person))";
        }

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

        $ldapstatus = ldap_error($ldapconn);

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
        $ldapconn = ldap_connect($this->ad_host, $this->port);
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
            $entry['division'] =  $section->getName();//getShortname();
        }
        if ($person->getManager()) {
            $manager = $person->getManager();
            if (!empty($manager)) {
                // znajdz sciezke przelozonego
                $cn = $manager;
                $searchString = "(&(cn=" . $cn . ")(objectClass=person))";

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
            //hack by dalo sie puste inicjaly wprowadzic, 
            //TODO: trzeba zmienic bo jednak beda generowane !!!!
            if($person->getInitials() == "puste" || $person->getInitials() == ""){
                unset($entry['initials']);
                //$entry['initials'] = array();
            }else{
                $entry['initials'] = $person->getInitials();
            }
        }

        $userAD = $this->getUserFromAD($person->getSamaccountname());
        $department = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($person->getDepartment());
        if ($person->getDepartment()) {
            $entry['department'] = $person->getDepartment();
            if (!empty($department)) {
                $entry['description'] = $department->getShortname();
            }
            $departmentOld = $this->doctrine->getRepository('ParpMainBundle:Departament')->findOneByName($userAD[0]['department']);
            $person->setGrupyAD($departmentOld, "-");
            $this->addRemoveMemberOf($person, $userAD, $dn, $userdn, $ldapconn);
            //jesli zmiana departamnentu dodajemy nowe grupy AD
            $person->setGrupyAD($department);
            $this->addRemoveMemberOf($person, $userAD, $dn, $userdn, $ldapconn);
        }
                
        if ($person->getIsDisabled() !== null) {
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

        if(count($entry) > 0){
            
            $res = ldap_modify($ldapconn, $dn, $entry);
            
            
            $error = ldap_error($ldapconn);
            $errno = ldap_errno($ldapconn);

            $ldapstatus = ldap_error($ldapconn);    
            if($ldapstatus != "Success"){
                if($this->debug){
                    die("bbb ".$ldapstatus);    
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
        }elseif($person->getCn()){
            //zmieniamy tylko cn
            $cn = $person->getCn();
            $b = ldap_rename($ldapconn, $person->getDistinguishedName(), "CN=" . $cn, null, TRUE);
            
            $ldapstatus = ldap_error($ldapconn);
        }
        if($person->getMemberOf()){
            
            $this->addRemoveMemberOf($person, $userAD, $dn, $userdn, $ldapconn);
        }
        ldap_unbind($ldapconn);

        //to wyrzucone bo nie zawsze zapisuje (jak nie wypoycha tylko pokazuje to nie ma zapisu) wiec flush jest w command!!!
        //$person->setIsImplemented(1);
        //$this->doctrine->persist($person);
        //$this->doctrine->flush();
        return $ldapstatus;
    }
    function getGrupa($grupa){
        return $this->adldap->group()->find($grupa);
    }
    function addRemoveMemberOf($person, $userAD, $dn, $userdn, $ldapconn){
        if($person->getMemberOf() != ""){
            $grupy = explode(",", $person->getMemberOf());
            foreach($grupy as $grupa){
            
                $znak = substr($grupa, 0, 1);               
                $g = substr($grupa, 1);
                $grupa = $this->getGrupa($g);
                $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                if($znak == "+" && !in_array($g, $userAD[0]['memberOf'])){
                    ldap_mod_add($ldapconn, $addtogroup, array('member' => $dn ));
                }elseif($znak == "-" && in_array($g, $userAD[0]['memberOf'])){                    
                    ldap_mod_del($ldapconn, $addtogroup, array('member' => $dn ));
                }else{
                    echo('Mialem '.($znak == "+" ? "dodawac" : "zdejmowac")." z grupy  ".$g." ale user w niej jest: ".in_array($g, $userAD[0]['memberOf'])."\n");
                }
            }
        }
    }
    function pwd_encryption( $newPassword ) {
    
        $newPassword = "\"" . $newPassword . "\"";
        $len = strlen( $newPassword );
        $newPassw = "";
        for ( $i = 0; $i < $len; $i++ )
        { 
            $newPassw .= "{$newPassword{$i}}\000"; 
        } 
        $userdata["unicodePwd"] = $newPassw; 
        return $userdata; 
    }

    public function createEntity($person)
    {
        $this->container->get('adcheck_service')->checkIfUserCanBeEdited($person->getSamaccountname());
        
        $ldapconn = ldap_connect($this->ad_host, $this->port);
        $ldapdomain = $this->ad_domain;
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
        
        
        $this->addRemoveMemberOf($person, [["memberOf" => []]], $dn, $userdn, $ldapconn);
        
        if($this->debug){
            
            die("koniec bo debug ".$ldapstatus);
        }

        ldap_unbind($ldapconn);
      
        
        //to wyrzucone bo nie zawsze zapisuje (jak nie wypoycha tylko pokazuje to nie ma zapisu) wiec flush jest w command!!!
        //$person->setIsImplemented(1);
        //$this->doctrine->persist($person);
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
        
        $ldapconn = ldap_connect($this->ad_host, $this->port);
        $ldapdomain = $this->ad_domain;
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
                $userdn = $dep->getOuAD().", ".$this->useradn . $this->patch;
                $filter="(objectClass=organizationalunit)"; 
                $justthese = array("dn", "ou"); 
                
                var_dump($userdn, $filter, $justthese);
                
                $sr=ldap_search($ldapconn, $userdn, $filter, $justthese); 
                $info = ldap_get_entries($ldapconn, $sr); 
                
                if($info["count"] > 0){
                    
                    ldap_free_result($sr); 
                }else{ 
                    
                    ldap_free_result($sr);
                    $ldapstatus2 = ldap_error($ldapconn);
                    $res = ldap_add($ldapconn, $dep->getOuAD().", ".$this->useradn . $this->patch, array(
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
         
    }

}