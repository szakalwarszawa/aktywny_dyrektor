<?php

namespace ParpV1\AuthBundle\Security;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
    }

    public function loadUserByUsername($username)
    {
        global $kernel;

        $idSrodowiska = $this->idSrodowiska;
        $ldapconn = null;

        if ('test' !== $idSrodowiska) {
            $ldapconn = ldap_connect($this->ad_host);
        }

        $ldapdomain = '@' . $this->ad_domain;//parp.local";
        if ($_POST) {
            $username = $_POST['_username'];
            $password = $_POST['_password'];

            if ('test' === $idSrodowiska) {
                $wybraneRole = isset($_POST['_roles']) ? $_POST['_roles'] : array();
                $password = $kernel->getContainer()->getParameter('haslo_srodowiska_testowego');
                $salt = null;
                $roles = empty($wybraneRole) ? $this->defaultRoles($username) : $this->checkDevRolesFromPost($wybraneRole);
                return new ParpUser($username, $password, $salt, $roles);
            }

            if ($ldapconn) {
                try {
                    if ('test' === $idSrodowiska) {
                        $ldapbind = true;
                    } else {
                        $ldapbind = ldap_bind($ldapconn, $username . $ldapdomain, $password);
                    }
                } catch (Exception $e) {
                    throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
                }

                if ($ldapbind) {
                    $salt = null;
                    $roles = $this->defaultRoles($username);
                    ldap_unbind($ldapconn);

                    return new ParpUser($username, $password, $salt, $roles);
                }
                throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
            }

            throw new UsernameNotFoundException(sprintf('Użytkownik "%s" nie istnieje.', $username));
        }

        throw new MethodNotAllowedException(['POST']);
    }

    /**
     * Sprwadza czy podane role są dopuszczone.
     *
     * @param array $postRoles
     *
     * @return array
     */
    private function checkDevRolesFromPost(array $postRoles)
    {
        global $kernel;

        $userLoginService = $kernel
            ->getContainer()
            ->get('parp.user_login_service');

        $availableRoles = $userLoginService->getAkdRolesNames();

        foreach ($postRoles as $role) {
            if (!in_array($role, $availableRoles)) {
                throw new AccessDeniedException('Wystąpił błąd w sprawdzaniu ról.');
            }
        }

        return $postRoles;
    }

    /**
     * Zwraca role przypisane do użytkownika.
     *
     * @param string $username
     *
     * @return array
     */
    private function defaultRoles($username)
    {
        global $kernel;

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

        // Dodatkowa rola dynamiczna #91551
        $roleDyrektor =
            $kernel->getContainer()
            ->get('doctrine')
            ->getRepository('ParpMainBundle:Departament')
            ->findBy([
                'dyrektor' => $username,
                'nowaStruktura' => true,
            ]);

        if ($roleDyrektor) {
            $roles[] = 'PARP_D_DYREKTOR';
        }

        return $roles;
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
        $ldapdomain = '@' . $this->ad_domain;
        $userdn = $this->ad_ou . ',DC=' . $this->ad_dc1 . ',DC=' . $this->ad_dc2;
        ldap_set_option($ldapconn, LDAP_OPT_SIZELIMIT, 2000);

        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        if (
            $ldapconn &&
            $ldappass &&
            $ldapuser
        ) {
            try {
                if ('test' === $idSrodowiska) {
                    $ldapbind = true;
                } else {
                    $errorlevel = error_reporting();
                    error_reporting($errorlevel & ~E_WARNING);
                    $ldapbind = ldap_bind($ldapconn, $ldapuser . $ldapdomain, $ldappass);
                    error_reporting($errorlevel);
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
