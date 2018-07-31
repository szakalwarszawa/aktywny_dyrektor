<?php

namespace ParpV1\AuthBundle\Security;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ParpUserProvider implements UserProviderInterface
{
    protected $ad_host = '';
    protected $ad_domain = '';
    protected $ad_ou = '';
    protected $ad_dc1 = '';
    protected $ad_dc2 = '';
    protected $idSrodowiska;
    protected $kernel;

    public function __construct()
    {
        global $kernel;

        /** @var \AppKernel $kernel */
        if ('AppCache' === get_class($kernel)) {
            $this->kernel = $kernel->getKernel();
        }

        $tab = explode('.', $kernel->getContainer()->getParameter('ad_domain'));
        $this->ad_host = $kernel->getContainer()->getParameter('ad_host');
        $this->ad_domain = $kernel->getContainer()->getParameter('ad_domain');
        $this->ad_ou = $kernel->getContainer()->getParameter('ad_ou');
        $this->ad_dc1 = $tab[0];
        $this->ad_dc2 = $tab[1];
        $this->idSrodowiska = $kernel->getContainer()->getParameter('id_srodowiska');
        $password = $kernel->getContainer()->getParameter('haslo_srodowiska_testowego');
    }

    public function loadUserByUsername($username)
    {
        global $kernel;

        $idSrodowiska = $this->idSrodowiska;
        $ldapconn = null;

        if ('test' !== $idSrodowiska) {
            $ldapconn = ldap_connect($this->ad_host);
        }

        $ldapdomain = '@'.$this->ad_domain;//parp.local";
        if ($_POST) {
            $username = $_POST['_username'];
            $password = $_POST['_password'];

            if ('test' === $idSrodowiska) {
                $salt = null;
                $roles = array(
                    'ROLE_USER',
                    'PARP_MANAGER',
                    'PARP_BZK_1',
                    'PARP_BZK_2',
                    'PARP_AZ_UPRAWNIENIA_BEZ_WNIOSKOW',
                    'PARP_ADMIN',
                    'PARP_ADMIN_ZASOBOW',
                    'PARP_ADMIN_TECHNICZNY_ZASOBOW'
                );

                return new ParpUser($username, $password, $salt, $roles);
            }

            if ($ldapconn) {
                try {
                    if ('test' === $idSrodowiska) {
                        $ldapbind = true;
                    } else {
                        $ldapbind = ldap_bind($ldapconn, $username.$ldapdomain, $password);
                    }
                } catch (Exception $e) {
                    throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
                }

                if ($ldapbind) {
                    $salt = null;
                    $rolesEntities =
                        $kernel->getContainer()
                            ->get('doctrine')
                            ->getRepository('ParpMainBundle:AclUserRole')
                            ->findBy([
                                'samaccountname' => $username
                            ])
                        ;

                    $roles = [];
                    /** @var array $rolesEntities */
                    foreach ($rolesEntities as $r) {
                        $roles[] = $r->getRole()->getName();
                    }
                    ldap_unbind($ldapconn);

                    return new ParpUser($username, $password, $salt, $roles);
                }
                throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
            }

            throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
        }

        throw new MethodNotAllowedException(['POST']);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof ParpUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        if ($this->auth($user->getUsername(), $user->getPassword())) {
            return new ParpUser($user->getUsername(), $user->getPassword(), $user->getSalt(), $user->getRoles());
        }

        throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $user->getUsername()));
    }

    public function auth($ldapuser, $ldappass)
    {
        $idSrodowiska = $this->idSrodowiska;
        $ldapconn = ldap_connect($this->ad_host);
        $ldapdomain = '@'.$this->ad_domain;
        $userdn = $this->ad_ou.',DC='.$this->ad_dc1.',DC='.$this->ad_dc2;
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);

        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        if ($ldapconn &&
            $ldappass &&
            $ldapuser
        ) {
            try {
                if ('test' === $idSrodowiska) {
                    $ldapbind = true;
                } else {
                    $ldapbind = ldap_bind($ldapconn, $ldapuser.$ldapdomain, $ldappass) or die('Nieprawidłowe dane!');
                }
            } catch (Exception $e) {
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

        return $class === 'ParpV1\AuthBundle\Security\ParpUser';
    }
}
