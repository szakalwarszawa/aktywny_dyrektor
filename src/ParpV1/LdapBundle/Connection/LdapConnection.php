<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Connection;

use Adldap\Configuration\DomainConfiguration;
use Adldap\Schemas\ActiveDirectory;
use Adldap\Adldap;
use Adldap\Exceptions\AdldapException;
use Adldap\Auth\BindException;
use LogicException;
use Adldap\Query\Factory;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\VarDumper\VarDumper;
use Adldap\Models\Attributes\DistinguishedName;

/**
 * Klasa LdapConnection
 * Realizuje połączenie z AD.
 */
class LdapConnection
{
    /**
     * @var DomainConfiguration|null
     */
    private $domainConfiguration = null;

    /**
     * @var Adldap|null
     */
    private $adLdap = null;

    /**
     * @var ArrayCollection
     */
    private $errors;

    /**
     * Tablica podstawowych parametrów.
     * NIE MOŻE zawierać prywatnych danych dotyczących np. danych logowania.
     *
     * @var array
     */
    private $baseParameters = [];

    /**
     * Publiczny konstruktor
     *
     * @param string $adHost
     * @param string $adUser
     * @param string $adPassword
     * @param string $adDomain
     * @param string $baseOu
     * @param string $ouZablokowani - OU do którego są przenoszeni użytkownicy po rozwiązaniu umowy
     * @param string $ouNieobecni - OU do którego są przenoszeni użytkownicy nieobecni (np. długi urlop)
     *
     * @todo rozdzielnie tego zeby przy autentykacji nie logować na dane admina
     */
    public function __construct(
        string $adHost,
        string $adUser,
        string $adPassword,
        string $baseDn,
        string $adDomain,
        string $baseOu,
        string $ouZablokowani,
        string $ouNieobecni
    ) {
        $baseDnFixed = new DistinguishedName();
        foreach (explode(',', $baseDn) as $value) {
            $baseDnFixed->addDc($value);
        }
        $baseDn = $baseDnFixed->get();

        $baseOuFixed = new DistinguishedName();
        foreach (explode(',', $baseOu) as $value) {
            $baseOuFixed->addOu($value);
        }
        $baseOu = $baseOuFixed->get();

        $this->configureDomain([
            'ad_host' => $adHost,
            'ad_user' => $adUser,
            'ad_password' => $adPassword,
            'base_dn' => $baseDn,
        ]);

        $this->baseParameters = [
            'ad_domain' => $adDomain,
            'base_dn' => $baseDn,
            'base_ou' => $baseOu,
            'ou_zablokowani' => $ouZablokowani,
            'ou_nieobecni' => $ouNieobecni
        ];

        $this->errors = new ArrayCollection();
        $this->ldapConnect();
    }

    /**
     * Konfiguracja połączenia z AD.
     *
     * @param array $configuration
     *
     * @return void
     */
    private function configureDomain(array $configuration): void
    {
        $domainConfiguration = new DomainConfiguration([
            'hosts' => [
                $configuration['ad_host'],
            ],
            'base_dn' => $configuration['base_dn'],
            'username' => $configuration['ad_user'],
            'password' => $configuration['ad_password'],
            'schema'   => ActiveDirectory::class
        ]);

        $this->domainConfiguration = $domainConfiguration;
    }

    /**
     * Autentykacja użytkownika względem AD.
     *
     * @param string $username
     * @param string $password
     *
     * @todo
     *
     * @return bool
     */
    public function authLdap(string $username, string $password): bool
    {
      /*  if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('Nazwa użytkownika i hasło nie mogą być puste!');
        }

        if (null === $this->adLdap) {
            throw new AdldapException('Brak połączenia z AD.');
        }

        $username .= '@test.local';

        $provider = $this
            ->adLdap
            ->getDefaultProvider()
        ;

        $search = $provider->search();

        $us = $search->findBy('samaccountname', 'pawel_fedoruk');

        VarDumper::dump($us);
        die;

        try {
            $provider
                ->auth()
                ->bind($username, $password)
            ;

            return true;
        } catch (BindException $exception) {
            return false;
        }*/

        return false;
    }

    /**
     * Połączenie z LDAP.
     *
     * @return LdapConnection
     */
    public function ldapConnect(): LdapConnection
    {
        if (null === $this->domainConfiguration) {
            throw new AdldapException('Brak konfiguracji.');
        }

        $adLdap = new Adldap();
        $adLdap->addProvider($this->domainConfiguration);

        try {
            $adLdap->connect();
        } catch (BindException $exception) {
            $this->addError('danger', 'Wystąpił błąd połączenia z AD.');

            return $this;
        }

        $this->adLdap = $adLdap;

        return $this;
    }

    /**
     * Dodaje błąd do kolekcji.
     *
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    private function addError(string $type, string $message): void
    {
        $this
            ->errors
            ->add([
                'type' => $type,
                'message' => $message
            ])
        ;
    }

    /**
     * Czy jest połączenie z AD.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return null !== $this->adLdap;
    }

    /**
     * Get adLdap
     *
     * @return AdLdap|null
     */
    public function getAdLdap()
    {
        return $this->adLdap;
    }

    /**
     * Get Search Factory obiekt
     *
     * @return Factory
     *
     * @throws LogicException gdy nie jest nawiązane połączenie
     */
    public function getSearchFactory(): Factory
    {
        if (null === $this->adLdap) {
            throw new LogicException('Nie nawiązano połączenia.');
        }

        return $this
            ->adLdap
            ->getDefaultProvider()
            ->search()
        ;
    }

    /**
     * Zwraca podstawowe parametry potrzebne przy np. tworzeniu nowego użytkownika (generowanie stringa AD).
     *
     * @return array
     */
    public function getBaseParameters(): array
    {
        return $this->baseParameters;
    }
}
