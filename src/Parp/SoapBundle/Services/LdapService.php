<?php

namespace Parp\SoapBundle\Services;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\DependencyInjection\Container;
//use Memcached;
use Memcached;

class LdapService
{

    protected $securityContext;
    protected $ad_host;
    protected $ad_domain;
    protected $container;
    protected $patch;
    protected $useradn ;
    protected $_ouWithGroups = "PARP Grupy";
    
    protected $ADattributes = array(
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
        "accountexpires",
        "useraccountcontrol",
        "distinguishedName",
        "cn",
        'memberOf',
    );

    public function __construct(SecurityContextInterface $securityContext, Container $container)
    {
        $this->securityContext = $securityContext;
        $this->container = $container;
        $this->ad_host = $this->container->getParameter('ad_host');
        $this->ad_domain = '@' . $this->container->getParameter('ad_domain');

        $tab = explode(".", $this->container->getParameter('ad_domain'));
        
        $env = $this->container->get('kernel')->getEnvironment();
        
        $this->useradn = $this->container->getParameter('ad_ou');
        if ($env === 'prod') {
            $this->patch = ',DC=' . $tab[0] . ',DC=' . $tab[1];
        } else {
            //$this->patch = ',OU=Test ,DC=' . $tab[0] . ',DC=' . $tab[1];
            $this->patch = ' ,DC=' . $tab[0] . ',DC=' . $tab[1];
        }
        //die($this->patch);
    }

    public function getAllFromAD()
    {
        $userdn = $this->useradn . $this->patch;
//        ldap_set_option()
        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn)
            throw new Exception('Brak połączenia z serwerem domeny!');
        $ldapdomain = $this->ad_domain;

        //if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        //    $memcached = new \Memcache;
        //} else {
        //    $memcached = new \Memcached;
        //}

        //$memcached->addServer('localhost', 11211);
        //$fromMemcached = $memcached->get('ldap-all-');
        //if ($fromMemcached) {
        //   return $fromMemcached;
        //}
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $letters = "abcdefghijklmnopqrstuvwxyz";
        $letters_array = str_split($letters);

        $tmpResults = array();
        foreach ($letters_array as $letter) {
            $search = ldap_search($ldapconn, $userdn, "(&(samaccountname=" . $letter . "*)(objectClass=person))", $this->ADattributes);
            $results = ldap_get_entries($ldapconn, $search);
            $tmpResults = array_merge($tmpResults, $results);
        }
        ldap_bind($ldapconn);

        
        $result = $this->parseResults($tmpResults);

        //echo "<pre>";
            //var_dump($result);
            //die();
//        foreach(array_unique($tmp) as $unTmp){
//            echo "insert into section (`name`) values ('".$unTmp."');<br />";
//        }
//die();
        //$memcached->set('ldap-all-', $result, time() + 600);

        return $result;
    }
    
    public function getMembersOfGroupFromAD($group=FALSE,$inclusive=FALSE)
    {
        
         $group = $group ? ldap_escape($group) : $group;
        $userdn = $this->useradn . $this->patch;
        $ldap_dn_grupy = "OU=".$this->_ouWithGroups.$this->patch;
        //die($ldap_dn);
//        ldap_set_option()
        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn)
            throw new Exception('Brak połączenia z serwerem domeny!');
        $ldapdomain = $this->ad_domain;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        // Begin building query
     	if($group) $query = "(&"; else $query = "";
     
     	$query .= "(&(objectClass=person))";
     
        // Filter by memberOf, if group is set
        if(is_array($group)) {
        	// Looking for a members amongst multiple groups
        		if($inclusive) {
        			// Inclusive - get users that are in any of the groups
        			// Add OR operator
        			$query .= "(|";
        		} else {
    				// Exclusive - only get users that are in all of the groups
    				// Add AND operator
    				$query .= "(&";
        		}
     
        		// Append each group
        		foreach($group as $g) $query .= "(memberOf=CN=$g,$ldap_dn_grupy)";
     
        		$query .= ")";
        } elseif($group) {
        	// Just looking for membership of one group
        	$query .= "(memberOf=CN=$group,$ldap_dn_grupy)";
        }
     
        // Close query
        if($group) $query .= ")"; else $query .= "";


        try{
            $search = ldap_search($ldapconn, $userdn, $query, $this->ADattributes);
        }catch(\Exception $e){
            die("Blad wyszukiwania w AD, szukano <br>'".$query."' <br><br>".$e->getMessage()." ");
        }

        
        
/*
        
*/
        
        $results = ldap_get_entries($ldapconn, $search);
        //print_r($query);
        //print_r($results); //die();
        ldap_bind($ldapconn);

        
        $result = $this->parseResults($results);

//        foreach(array_unique($tmp) as $unTmp){
//            echo "insert into section (`name`) values ('".$unTmp."');<br />";
//        }
//die();
        //$memcached->set('ldap-all-', $result, time() + 600);
        return $result;
    }
    
    
    public function getOUsFromAD($ou){
        
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = $this->ad_domain;
        //$userdn = "OU=Test";
        $userdn = $this->useradn . $this->patch;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);
        
        
        //$userdn = "OU=".$dep->getShortname().", ".$this->useradn . $this->patch;
        $userdn = $this->useradn . $this->patch;
        $filter="(objectClass=organizationalunit)"; 
        $justthese = array(
            "objectclass", 
            "ou", 
            "distinguishedname", 
            "instancetype", 
            "whencreated", 
            "whenchanged", 
            "usncreated", 
            "usnchanged", 
            "name", 
            "objectguid", 
            "objectcategory", 
            "dscorepropagationdata", 
            "dn", 
        ); 
        $sr=ldap_search($ldapconn, $userdn, $filter); 
        $info = ldap_get_entries($ldapconn, $sr); 
        
        //echo "<pre>"; print_r($info); echo "</pre>";
            
        ldap_free_result($sr); 
        ldap_unbind($ldapconn);  
        
        //echo "Zrobilem swoje ";
         ///////////////
        return $info;
    }
    public function getGroupsFromAD($group){
        // Begin building query
     	$query = "(&"; 
     	$query .= "(&(objectClass=group))";
        $group2 = ldap_escape($group);
        if($group){
            $query .= "(CN=$group2$wilcardSearch)";            
        }else{
            $query .= "(CN=*)";   
        }
        
     
        // Close query
        $query .= ")";
        
        $ret = $this->paginated_search($query);
        return $ret;
/*
        $letters = "abcdefghijklmnopqrstuvwxyz1234567890-_";
        $letters_array = str_split($letters);

        $tmpResults = array();
        if($group){
            
        }else{
            //jesli bierzemy wszystko to w iteracji po kloei literkami alfabetu bo wystepuja blad:            
            //Warning: ldap_search(): Partial search results returned: Sizelimit exceeded
            foreach ($letters_array as $letter) {
                foreach ($letters_array as $letter2) {
                    foreach ($letters_array as $letter3) {
                        echo ".szukam literki $letter$letter2$letter3 ...";
                        $results = $this->getGroupsFromADint($letter.$letter2.$letter3, "*");
                        $tmpResults = array_merge($tmpResults, $results);
                    }
                }
            }
        }
        
        return $tmpResults;
*/
    }
    private function paginated_search($filter, $pageSize = 500)
    {
        $userdn = $this->useradn . $this->patch;
        $ldap_dn_grupy = "OU=".$this->_ouWithGroups.$this->patch;
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = $this->ad_domain;
        
        if (!$ldapconn)
            throw new Exception('Brak połączenia z serwerem domeny!');
                    
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 20000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password'); 
        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);        
        $cookie = '';
        $result = [];
        $result['count'] = 0;
        do {
            ldap_control_paged_result($ldapconn, $pageSize, true, $cookie);
            //var_dump($ldapconn, $ldap_dn_grupy, $filter);
            $sr = ldap_search($ldapconn, $ldap_dn_grupy, $filter);
            $entries = ldap_get_entries($ldapconn, $sr);
            $entries['count'] += $result['count'];
    
            $result = array_merge($result, $entries);
    
            ldap_control_paged_result_response($ldapconn, $sr, $cookie);
    
        } while ($cookie !== null && $cookie != '');
    
        return $result;
    }
    public function getGroupsFromADint($group, $wilcardSearch = "")
    {
        $userdn = $this->useradn . $this->patch;
        $ldap_dn_grupy = "OU=".$this->_ouWithGroups.$this->patch;
        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn)
            throw new Exception('Brak połączenia z serwerem domeny!');
        $ldapdomain = $this->ad_domain;

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 20000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        // Begin building query
     	$query = "(&"; 
     	$query .= "(&(objectClass=group))";
        $group2 = ldap_escape($group);
        if($group){
            $query .= "(CN=$group2$wilcardSearch)";            
        }
        
     
        // Close query
        $query .= ")";
        try{
            $search = ldap_search($ldapconn, $ldap_dn_grupy, $query);
        }catch(\Exception $e){
            die("Blad wyszukiwania w AD, szukano <br>'".$query."' <br><br>".$e->getMessage()." ");
        }
        
        $results = ldap_get_entries($ldapconn, $search);
        ldap_bind($ldapconn);
        return $results;
    }
    
    
    
    public function getAllUserGroupsRecursivlyFromAD($samaccountname)
    {
        $user = $this->getUserFromAD($samaccountname);
        //echo "<pre>"; print_r($user); die();
        
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');
        $ldapdomain = $this->ad_domain;
        $userDN = ldap_escape($user[0]['distinguishedname']);
        $searchDN = "OU=".$this->_ouWithGroups.$this->patch; //"DC=parp,DC=local";
        $ldapconn = ldap_connect($this->ad_host);
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);
        $groupToFind = "SG-ZZP-Public-RO";//"INT_BI";
        $filter = "(memberof:1.2.840.113556.1.4.1941:=".$groupToFind.")";
        //$filter = "(samaccountname=".$groupToFind.")";
        $filter = "(&(objectclass=*)(member:1.2.840.113556.1.4.1941:=".$userDN."))";
        $search = ldap_search($ldapconn, $searchDN, $filter, array("dn"), 1);
        $items = ldap_get_entries($ldapconn, $search);
        //echo "<pre>"; print_r($items);print_r($userDN); die();
        return $items;
    }
    
    public function  checkGroupExistsFromAD($group)
    {
        $result = $this->getGroupsFromAD($group);

        
        return ($result['count']) > 0;
    }

    public function getAccountControl($userAccountControl)
    {

        $binary = decbin($userAccountControl);

        $binArray = array_reverse(str_split($binary));
        $accountControl = "";

        if (isset($binArray["0"]))
            if ($binArray["0"] == 1)
                $accountControl.="SCRIPT,";
        if (isset($binArray["1"]))
            if ($binArray["1"] == 1)
                $accountControl.="ACCOUNTDISABLE,";
        if (isset($binArray["2"]))
            if ($binArray["2"] == 1)
                $accountControl.="HOMEDIR_REQUIRED,";
        if (isset($binArray["3"]))
            if ($binArray["3"] == 1)
                $accountControl.="LOCKOUT,";
        if (isset($binArray["4"]))
            if ($binArray["4"] == 1)
                $accountControl.="PASSWD_NOTREQD,";
        if (isset($binArray["5"]))
            if ($binArray["5"] == 1)
                $accountControl.="PASSWD_CANT_CHANGE,";
        if (isset($binArray["6"]))
            if ($binArray["6"] == 1)
                $accountControl.="ENCRYPTED_TEXT_PWD_ALLOWED,";
        if (isset($binArray["7"]))
            if ($binArray["7"] == 1)
                $accountControl.="TEMP_DUPLICATE_ACCOUNT,";
        if (isset($binArray["8"]))
            if ($binArray["8"] == 1)
                $accountControl.="NORMAL_ACCOUNT,";
        if (isset($binArray["9"]))
            if ($binArray["9"] == 1)
                $accountControl.="INTERDOMAIN_TRUST_ACCOUNT,";
        if (isset($binArray["10"]))
            if ($binArray["10"] == 1)
                $accountControl.="WORKSTATION_TRUST_ACCOUNT,";
        if (isset($binArray["11"]))
            if ($binArray["11"] == 1)
                $accountControl.="SERVER_TRUST_ACCOUNT,";
        if (isset($binArray["12"]))
            if ($binArray["12"] == 1)
                $accountControl.="DONT_EXPIRE_PASSWORD,";
        if (isset($binArray["13"]))
            if ($binArray["13"] == 1)
                $accountControl.="MNS_LOGON_ACCOUNT,";
        if (isset($binArray["14"]))
            if ($binArray["14"] == 1)
                $accountControl.="SMARTCARD_REQUIRED,";
        if (isset($binArray["15"]))
            if ($binArray["15"] == 1)
                $accountControl.="TRUSTED_FOR_DELEGATION,";
        if (isset($binArray["16"]))
            if ($binArray["16"] == 1)
                $accountControl.="NOT_DELEGATED,";
        if (isset($binArray["17"]))
            if ($binArray["17"] == 1)
                $accountControl.="USE_DES_KEY_ONLY,";
        if (isset($binArray["18"]))
            if ($binArray["18"] == 1)
                $accountControl.="DONT_REQ_PREAUTH,";
        if (isset($binArray["19"]))
            if ($binArray["19"] == 1)
                $accountControl.="PASSWORD_EXPIRED,";
        if (isset($binArray["20"]))
            if ($binArray["20"] == 1)
                $accountControl.="TRUSTED_TO_AUTH_FOR_DELEGATION,";
        if (isset($binArray["21"]))
            if ($binArray["21"] == 1)
                $accountControl.="PARTIAL_SECRETS_ACCOUNT,";

        return $accountControl;
    }

    public function getUserFromAD($samaccountname = null, $cnname = null, $query = null)
    {
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = $this->ad_domain;
        $userdn = $this->useradn . $this->patch;

        //if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        //    $memcached = new \Memcache;
        //} else {
        //    $memcached = new \Memcached;
        //}
        //$memcached->addServer('localhost', 11211);
//        $fromMemcached = $memcached->get('ldap-detail-'.$samaccountname);
//        if($fromMemcached){
//            return $fromMemcached;
//
//        }

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        //$ldap_username = $this->securityContext->getToken()->getUsername();
        //$ldap_password = $this->securityContext->getToken()->getUser()->getPassword();
        

        
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');
        //echo "$ldap_username $ldap_password";        

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        if ($samaccountname) {
            $searchString = "(&(samaccountname=" . $samaccountname . ")(objectClass=person))";
        } elseif ($cnname) {
//            $cnname = substr($cnname,3,stripos($cnname,',')-3);
            //(cn=Joe User)
            $searchString = '(&(name=*' . $cnname . '*)(objectClass=person))';
            //$xx = ldap_escape("\(Michalik\)");
            //$searchString = '(&(sn=*Rucińska '.$xx.'*)(givenName=Marta*)(objectClass=person))';
//            echo $searchString;
        } elseif($query) {
            $searchString = "(&(".$query.")(objectClass=person))";
        }else {
            $searchString = "(&(samaccountname=)(objectClass=person))";
        }

//echo "!!!".$searchString."!!!";

        $search = ldap_search($ldapconn, $userdn, $searchString, $this->ADattributes);
        $tmpResults = ldap_get_entries($ldapconn, $search);
        ldap_unbind($ldapconn);

        $result = $this->parseResults($tmpResults);

//        $memcached->set('ldap-detail-'.$samaccountname,$result);
        return $result;
    }
    
    protected function parseResults($tmpResults){
        $result = array();
        //print_r($userdn); die();

        $i = 0;
        foreach ($tmpResults as $tmpResult) {
            if ($tmpResult["samaccountname"]) {
                //$st = $this->getAccountControl($tmpResult["samaccountname"]);
                $date = new \DateTime();
                $time = $this->LDAPtoUnix($tmpResult["accountexpires"][0]);
                $date->setTimestamp($time);
                //print_r($time); 
                //print_r($date); 
                $result[$i]["isDisabled"] =  $tmpResult["useraccountcontrol"][0] == "546" ? 1 : 0;
                $result[$i]["samaccountname"] =  $tmpResult["samaccountname"][0];
                $result[$i]["accountExpires"] = $date->format("Y") < 3000 ? $date->format("Y-m-d") : "";
                if (isset($tmpResult["accountexpires"][0])) {
                    if ($tmpResult["accountexpires"][0] == 9223372036854775807 || $tmpResult["accountexpires"][0] == 0) {
                        $result[$i]["accountexpires"] = "";
                    } else {
                        $result[$i]["accountexpires"] = date("Y-m-d H:i:s", $tmpResult["accountexpires"][0] / 10000000 - 11644473600);
                    }
                }
                $result[$i]["name"] = isset($tmpResult["name"][0]) ? $tmpResult["name"][0] : "";
                $result[$i]["email"] = isset($tmpResult["mail"][0]) ? $tmpResult["mail"][0] : "";
                $result[$i]["initials"] = isset($tmpResult["initials"][0]) ? $tmpResult["initials"][0] : "";
                $result[$i]["title"] = isset($tmpResult["title"][0]) ? $tmpResult["title"][0] : "";
                $result[$i]["info"] = isset($tmpResult["info"][0]) ? $tmpResult["info"][0] : "";
                $result[$i]["department"] = isset($tmpResult["department"][0]) ? $tmpResult["department"][0] : "";
                $result[$i]["description"] = isset($tmpResult["description"][0]) ? $tmpResult["description"][0] : "";
                $result[$i]["division"] = isset($tmpResult["division"][0]) ? $tmpResult["division"][0] : "";
                //$result[$i]["disableDescription"] = str_replace("Konto wyłączone bo: ", "", $tmpResult["description"][0]);
                $result[$i]["lastlogon"] = isset($tmpResult["lastlogon"]) ? date("Y-m-d H:i:s", $tmpResult["lastlogon"][0] / 10000000 - 11644473600) : "";
                //$result[$i]["division"] = isset($tmpResult["division"][0]) ? $tmpResult["division"][0] : "";
                $result[$i]["manager"] = isset($tmpResult["manager"][0]) ? $tmpResult["manager"][0] : "";
                $result[$i]["thumbnailphoto"] = isset($tmpResult["thumbnailphoto"][0]) ? $tmpResult["thumbnailphoto"][0] : "";
                $result[$i]["useraccountcontrol"] = isset($tmpResult["useraccountcontrol"][0]) ? $this->getAccountControl($tmpResult["useraccountcontrol"][0]) : "";
                $result[$i]["distinguishedname"] = $tmpResult["distinguishedname"][0];
                $result[$i]["cn"] = $tmpResult["cn"][0];
                $result[$i]["memberOf"] = $this->parseMemberOf($tmpResult);
                
                $roles = $this->container->get('doctrine')->getRepository('ParpMainBundle:AclUserRole')->findBySamaccountname($tmpResult["samaccountname"][0]);
                $rs = array();
                foreach($roles as $r){
                    $rs[] = $r->getRole()->getName();
                }
                
                $result[$i]['roles'] = $rs;//TODO: wczytywac role !!!
                //print_r($result); die();
                $i++;
            }
        }
        
        /*
            
        $result = array();
        $index = 0;
//$tmp = array();
        foreach ($results as $tmpResult) {
            $result[$index]["isDisabled"] =  $tmpResult["useraccountcontrol"][0] == "546";

            $result[$index]["samaccountname"] = isset($tmpResult["samaccountname"][0]) ? $tmpResult["samaccountname"][0] : "";
            $result[$index]["name"] = isset($tmpResult["name"][0]) ? $tmpResult["name"][0] : "";
            $result[$index]["initials"] = isset($tmpResult["initials"][0]) ? $tmpResult["initials"][0] : "";
            if (isset($tmpResult["accountexpires"][0])) {
                if ($tmpResult["accountexpires"][0] == 9223372036854775807 || $tmpResult["accountexpires"][0] == 0) {
                    $result[$index]["accountexpires"] = "";
                } else {
                    $result[$index]["accountexpires"] = date("Y-m-d H:i:s", $tmpResult["accountexpires"][0] / 10000000 - 11644473600);
                }
            }

            $result[$index]["title"] = isset($tmpResult["title"][0]) ? $tmpResult["title"][0] : "";
            $result[$index]["info"] = isset($tmpResult["info"][0]) ? $tmpResult["info"][0] : "";
            $result[$index]["department"] = isset($tmpResult["department"][0]) ? $tmpResult["department"][0] : "";
            $result[$index]["description"] = isset($tmpResult["description"][0]) ? $tmpResult["description"][0] : "";
            $result[$index]["division"] = isset($tmpResult["division"][0]) ? $tmpResult["division"][0] : "";
            $result[$index]["lastlogon"] = isset($tmpResult["lastlogon"]) ? date("Y-m-d H:i:s", $tmpResult["lastlogon"][0] / 10000000 - 11644473600) : "";
            $result[$index]["manager"] = isset($tmpResult["manager"][0]) ? $tmpResult["manager"][0] : "";
            $result[$index]["thumbnailphoto"] = isset($tmpResult["thumbnailphoto"][0]) ? $tmpResult["thumbnailphoto"][0] : "";
            $result[$index]["useraccountcontrol"] = isset($tmpResult["useraccountcontrol"][0]) ? $this->getAccountControl($tmpResult["useraccountcontrol"][0]) : "";
//            $tmp[]=$result[$index]["info"];
            if (isset($tmpResult["samaccountname"]))
                $index++;
        }
            
            */
        
        
        /*
            
            
            
        $result = array();
        $index = 0;
//$tmp = array();
        foreach ($tmpResults as $tmpResult) {
            $result[$index]["isDisabled"] =  $tmpResult["useraccountcontrol"][0] == "546";

            $result[$index]["samaccountname"] = isset($tmpResult["samaccountname"][0]) ? $tmpResult["samaccountname"][0] : "";
            $result[$index]["name"] = isset($tmpResult["name"][0]) ? $tmpResult["name"][0] : "";
            $result[$index]["initials"] = isset($tmpResult["initials"][0]) ? $tmpResult["initials"][0] : "";
            if (isset($tmpResult["accountexpires"][0])) {
                if ($tmpResult["accountexpires"][0] == 9223372036854775807 || $tmpResult["accountexpires"][0] == 0) {
                    $result[$index]["accountexpires"] = "";
                } else {
                    $result[$index]["accountexpires"] = date("Y-m-d H:i:s", $tmpResult["accountexpires"][0] / 10000000 - 11644473600);
                }
            }

            $result[$index]["title"] = isset($tmpResult["title"][0]) ? $tmpResult["title"][0] : "";
            $result[$index]["info"] = isset($tmpResult["info"][0]) ? $tmpResult["info"][0] : "";
            $result[$index]["department"] = isset($tmpResult["department"][0]) ? $tmpResult["department"][0] : "";
            $result[$index]["description"] = isset($tmpResult["description"][0]) ? $tmpResult["description"][0] : "";
            $result[$index]["division"] = isset($tmpResult["division"][0]) ? $tmpResult["division"][0] : "";
            $result[$index]["lastlogon"] = isset($tmpResult["lastlogon"]) ? date("Y-m-d H:i:s", $tmpResult["lastlogon"][0] / 10000000 - 11644473600) : "";
            $result[$index]["manager"] = isset($tmpResult["manager"][0]) ? $tmpResult["manager"][0] : "";
            $result[$index]["thumbnailphoto"] = isset($tmpResult["thumbnailphoto"][0]) ? $tmpResult["thumbnailphoto"][0] : "";
            $result[$index]["useraccountcontrol"] = isset($tmpResult["useraccountcontrol"][0]) ? $this->getAccountControl($tmpResult["useraccountcontrol"][0]) : "";
//            $tmp[]=$result[$index]["info"];
            if (isset($tmpResult["samaccountname"]))
                $index++;
        }
            
            */
        
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

    public function getUsersFromOU($OU)
    {
        //echo ".$OU";
        $ldapconn = ldap_connect($this->ad_host);
        if (!$ldapconn)
            throw new Exception('Brak połączenia z serwerem domeny!');
        $ldapdomain = $this->ad_domain;

        //if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        //    $memcached = new \Memcache;
        //} else {
        //    $memcached = new \Memcached;
        //}
        /* $memcached->addServer('localhost', 11211);
          $fromMemcached = $memcached->get('ldap-users-from-ou-');
          if ($fromMemcached) {
          return $fromMemcached;
          } */

        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
        
        $ldap_username = $this->container->getParameter('ad_user');
        $ldap_password = $this->container->getParameter('ad_password');

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $userdn = "OU=" . $OU . ",".$this->useradn . $this->patch;
        $result = null;
        try{
            $search = ldap_search($ldapconn, $userdn, "(&(samaccountname=*)(objectClass=person))", array(
                "samaccountname",
                "name",
                "initials",
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
                    $i++;
                }
            }
        }catch(\Exception $e){}

        return $result;
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
