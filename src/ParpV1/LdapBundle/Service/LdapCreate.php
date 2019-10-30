<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\Service;

use ParpV1\LdapBundle\Connection\LdapConnection;

/**
 * Klasa odpowiedzialna za tworzenie nowych danych w AD.
 */
class LdapCreate
{
    /**
     * @var LdapConnection
     */
    private $ldapConnection;

    /**
     * Publiczny konsturktor
     *
     * @param LdapConnection $ldapConnection
     */
    public function __construct(LdapConnection $ldapConnection)
    {
        $this->ldapConnection = $ldapConnection;
    }

    /**
     * Tworzy nowy model użytkownika.
     *
     * @return
     */
    public function createAdUserModel()
    {
        $newUserModel = $this
            ->ldapConnection
            ->getAdLdap()
            ->getDefaultProvider()
            ->make()
            ->user()
        ;

        return $newUserModel;
    }
}
