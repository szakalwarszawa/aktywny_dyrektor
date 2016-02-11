<?php

namespace Parp\AuthBundle\Security;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class ParpUserProvider implements UserProviderInterface
{
    public function loadUserByUsername($username)
    {
        $ldapconn = ldap_connect('srv-adc01.parp.local');
        $ldapdomain = "@parp.local";
         if($_POST){
            $username = $_POST['_username'];
            $password = $_POST['_password'];
        }

//      var_dump($_POST);
//      die();
//      $userdn = "OU=PARP Pracownicy,DC=parp,DC=local";
        
//      DEBUG ONLY:
//      $password = null;
//      $salt = null;
//      $roles = array('ROLE_USER');
//      ldap_unbind($ldapconn);

//      return new ParpUser($username, $password, $salt, $roles);
        
        if ($ldapconn) {
            try {
                $ldapbind = @ldap_bind($ldapconn, $username.$ldapdomain, $password);
            } catch (Exception $e) {
                throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
            }

            if ($ldapbind) {
                $salt = null;
                $roles = array('ROLE_USER');
                ldap_unbind($ldapconn);

                return new ParpUser($username, $password, $salt, $roles);
            } else {
                throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
            }

        }
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
        $ldapconn = ldap_connect('srv-adc01.parp.local');
        $ldapdomain = "@parp.local";
        $userdn = "OU=PARP Pracownicy,DC=parp,DC=local";
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);

        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        if ($ldapconn &&
            $ldappass &&
            $ldapuser) 
        {

                try {
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
