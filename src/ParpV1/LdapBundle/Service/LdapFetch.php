<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service;

use ParpV1\LdapBundle\Constants\SearchBy;
use ParpV1\LdapBundle\AdUser\AdUser;
use Adldap\Models\Group;
use ParpV1\LdapBundle\Cache\AdUserCache;
use ParpV1\LdapBundle\Cache\AdGroupCache;
use ParpV1\LdapBundle\Connection\LdapConnection;

/**
 * Klasa odpowiedzialna za pobieranie danych z AD.
 */
class LdapFetch
{
    /**
     * @var LdapConnection
     */
    private $ldapConnection;

    /**
     * Odświeżenie cache przed operacją.
     *
     * @var bool
     */
    private $refreshCache = false;

    /**
     * @var AdUserCache
     */
    private $adUserCache;

    /**
     * @var AdGroupCache
     */
    private $adGroupCache;

    /**
     * Publiczny konsturktor
     *
     * @param LdapConnection $ldapConnection
     */
    public function __construct(LdapConnection $ldapConnection, AdUserCache $adUserCache, AdGroupCache $adGroupCache)
    {
        $this->ldapConnection = $ldapConnection;
        $this->adUserCache = $adUserCache;
        $this->adGroupCache = $adGroupCache;
    }

    /**
     * Pobiera obiekt użytkownika z AD.
     * Najpierw uderza do cache.
     * Jeżeli parametr `refreshCache` jest true - mimo odnalezienia
     * obiektu w cache - odświeża go.
     *
     * @param string $username
     * @param string $byAttribute - atrybut według którego jest szukany
     * @param bool $useCache - czy użyć cache
     *
     * @return null|array|AdUser
     */
    public function fetchAdUser(
        string $username,
        string $byAttribute = SearchBy::LOGIN,
        bool $useCache = true
    ) {
        $adUser = false;
        if ($useCache) {
            $adUser = $this
                ->adUserCache
                ->getItem($username)
            ;
        }

        if (false === $adUser || !$useCache) {
            $searchFactory = $this
                ->ldapConnection
                ->getSearchFactory()
            ;

            $adUser = $searchFactory->findBy($byAttribute, $username);

            if (!$adUser) {
                return null;
            }

            $this
                ->adUserCache
                ->saveItem($username, $adUser)
            ;
        }


        return new AdUser($adUser);
    }

    /**
     * Pobiera obiekt grupy z AD.
     *
     * @param string $groupName
     * @param bool $useCache - czy użyć cache
     *
     * @return Group|bool
     */
    public function fetchGroup(string $groupName, bool $useCache = true)
    {
        $group =  false;
        if ($useCache) {
            $group = $this
                ->adGroupCache
                ->getItem($groupName)
            ;
        }

        if (false === $group || !$useCache) {
            $searchFactory = $this
                ->ldapConnection
                ->getSearchFactory()
            ;

            $group = $searchFactory
                ->groups()
                ->find($groupName)
            ;

            if (!$group) {
                return null;
            }

            $this
                ->adGroupCache
                ->saveItem($groupName, $group)
            ;
        }

        return $group;
    }

    /**
     * Ustawia parametr `refreshCache` na true.
     * Spowoduje to pobieranie świeżego obiektu bezpośrednio z AD
     * i zaktualizowanie go w cache.
     *
     * @return LdapFetch
     */
    public function refreshCache(): LdapFetch
    {
        $this->refreshCache(true);

        return $this;
    }
}
