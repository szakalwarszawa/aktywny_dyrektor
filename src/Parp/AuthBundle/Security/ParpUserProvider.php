<?php

namespace Parp\AuthBundle\Security;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class ParpUserProvider implements UserProviderInterface
{
    var $ad_host = "";
    var $ad_domain = "";
    var $ad_ou = "";
    var $ad_dc1 = "";
    var $ad_dc2 = "";
    
    
    public function __construct()
    {
        global $kernel;
        
        if ('AppCache' == get_class($kernel)) {
            $kernel = $kernel->getKernel();
        }
        

        $tab = explode(".", $kernel->getContainer()->getParameter('ad_domain'));
        $this->ad_host = $kernel->getContainer()->getParameter('ad_host');
        $this->ad_domain = $kernel->getContainer()->getParameter('ad_domain');
        $this->ad_ou = $kernel->getContainer()->getParameter('ad_ou');
        $this->ad_dc1 = $tab[0];
        $this->ad_dc2 = $tab[1];
        //die( ".". $this->ad_host.".".$this->ad_domain.".".$this->ad_ou.".");
        //die('a');
    }
    
    public function loadUserByUsername($username)
    {
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = "@".$this->ad_domain;//parp.local";
         if($_POST){
            $username = $_POST['_username'];
            $password = $_POST['_password'];
        }

            //die( ".11.".$username.$ldapdomain);
      //var_dump($username);
      //die();
        
//      DEBUG ONLY:
//      $password = null;
//      $salt = null;
//      $roles = array('ROLE_USER');
//      ldap_unbind($ldapconn);

//      return new ParpUser($username, $password, $salt, $roles);
        
        if ($ldapconn) {
            try {
                    //die( ".".$username.".".$ldapdomain.".".$password.".");
                $ldapbind = ldap_bind($ldapconn, $username.$ldapdomain, $password);
            } catch (Exception $e) {
                //die('.1'); 
                throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
            }

            if ($ldapbind) {
                //echo ".12.";
                $salt = null;
                $roles = array('ROLE_USER');
                ldap_unbind($ldapconn);

                return new ParpUser($username, $password, $salt, $roles);
            } else {
                //die('.2'.$this->ad_host);
                throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
            }

        }else{
            die('ldapconn not valid');   
        }
                //die('.3');
        throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof ParpUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }
        if($this->auth($user->getUsername(),$user->getPassword()))
            return new ParpUser($user->getUsername(), $user->getPassword(), $user->getSalt(), $user->getRoles());
        else
            throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $user->getUsername()));
    }

    public function auth($ldapuser,$ldappass){
        //die( ".auth.");
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = "@".$this->ad_domain;
        $userdn = $this->ad_ou.",DC=parp,DC=local";
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);

        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        if ($ldapconn &&
            $ldappass &&
            $ldapuser) 
        {

                try {
                    
                    //die( ".".$ldapuser.".".$ldapdomain.".".$ldappass.".");
                    
                    $ldapbind = ldap_bind($ldapconn, $ldapuser.$ldapdomain, $ldappass) or die('Nieprawidłowe dane!');
                } catch(Exception $e) {
                    throw new Exception('Brak komunikacji z serwerem!');
                }


            if ($ldapbind) {
                ldap_unbind($ldapconn);
                    return true;
            }
        }
        return false;
    }
    
    public function supportsClass($class)
    {
  
        return $class === 'Parp\AuthBundle\Security\ParpUser';
    }
}

?>
