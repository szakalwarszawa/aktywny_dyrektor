<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser;

use Adldap\Models\User;

/**
 * Klasa zawierająca operacje na grupach użytkownika.
 */
class UserAttributeManageService
{
    /**
     * Nadaje grupy użytkownikowi.
     *
     * @param array $groups
     * @param bool $setNullFirst przed nadaniem grup zeruje wszystkie grupy użytkownika.
     *
     * @return void
     */
    public function addGroups(array $groups, bool $setNullFirst = false)
    {

    }
}
