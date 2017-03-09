<?php

class Test
{    
    protected $host = "ldap://10.10.16.21";
    protected $port = 389;//636;
    protected $debug = 1;
    protected $AdminUser = "aktywny_dyrektor";
    protected $AdminPass = "abcd@123";
    protected $ad_host = "";
    protected $ad_domain = "@parp.local";
    protected $patch = "DC=parp,DC=local";
    protected $useradn = "OU=Zespoly, OU=PARP Pracownicy";

    
    function __construct(){
        $ldapconn = ldap_connect($this->host, $this->port);
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
             
        
        $newuser_plaintext_password = "Dupa456!";
        
        
        $encoded_newPassword = "{SHA}" . base64_encode( pack( "H*", sha1( $newuser_plaintext_password ) ) );
        $entry['userPassword'] = '{MD5}' . base64_encode(pack('H*',md5($newuser_plaintext_password)));
        $entry["userPassword"] = "$encoded_newPassword";
        unset($entry['initials']);
        //die('aaa'); 
        $entry = $this->pwd_encryption($newuser_plaintext_password);
        
        
        echo "<pre>";
        print_r($entry);echo "</pre>";
        //die();
        
        
        $res = ldap_modify($ldapconn, $dn, $entry);
        
        
        $error = ldap_error($ldapconn);
        $errno = ldap_errno($ldapconn);
        
        var_dump($res, $error, $errno); die("test zaminy hasla koniec");
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
}

new Test();