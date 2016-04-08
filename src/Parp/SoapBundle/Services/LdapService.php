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
        $ldap_username = $this->securityContext->getToken()->getUsername();
        $ldap_password = $this->securityContext->getToken()->getUser()->getPassword();

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $letters = "abcdefghijklmnopqrstuvwxyz";
        $letters_array = str_split($letters);

        $tmpResults = array();
        foreach ($letters_array as $letter) {
            $search = ldap_search($ldapconn, $userdn, "(&(samaccountname=" . $letter . "*)(objectClass=person))", array(
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
                "accountexpires",
            ));
            $results = ldap_get_entries($ldapconn, $search);
//            var_dump($results);
//            die();
            $tmpResults = array_merge($tmpResults, $results);
        }
        ldap_bind($ldapconn);

        $result = array();
        $index = 0;
//$tmp = array();
        foreach ($tmpResults as $tmpResult) {

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
//        foreach(array_unique($tmp) as $unTmp){
//            echo "insert into section (`name`) values ('".$unTmp."');<br />";
//        }
//die();
        //$memcached->set('ldap-all-', $result, time() + 600);

        return $result;
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
        $ldap_username = $this->securityContext->getToken()->getUsername();
        $ldap_password = $this->securityContext->getToken()->getUser()->getPassword();

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

        ldap_unbind($ldapconn);

        $result = array();

        $i = 0;
        foreach ($tmpResults as $tmpResult) {
            if ($tmpResult["samaccountname"]) {
                //print_r($tmpResult); die();
                $date = new \DateTime();
                $time = floor($tmpResult["accountexpires"][0])/10000000 - 11644473600;
                $date->setTimestamp($time);
                $result[$i]["samaccountname"] =  $tmpResult["samaccountname"][0];
                $result[$i]["accountExpires"] = $date->format("Y") < 3000 ? $date->format("Y-m-d") : "";
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

    public function getUsersFromOU($OU)
    {
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
        $ldap_username = $this->securityContext->getToken()->getUsername();
        $ldap_password = $this->securityContext->getToken()->getUser()->getPassword();

        $ldapbind = ldap_bind($ldapconn, $ldap_username . $ldapdomain, $ldap_password);

        $userdn = "OU=" . $OU . ",".$this->useradn . $this->patch;

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

        return $result;
    }

}
