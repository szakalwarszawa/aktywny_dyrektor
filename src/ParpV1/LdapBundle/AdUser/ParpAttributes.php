<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\AdUser;

use Adldap\Models\User;
use ParpV1\MainBundle\Services\DictionaryService;

/**
 * Klasa ParpAttributes - atrybuty użytkownika związane z PARP.
 */
class ParpAttributes
{
    /**
     * Dodaje do użytkownika PARP`owe atrybuty np. adres firmy.
     * NIE dokonuje zapisu w AD.
     *
     * @param User $adUser
     *
     * @return User
     */
    public static function addParpAttributes(User $adUser): User
    {
        $dictionary = new DictionaryService();
        $parpAttributes = $dictionary->getDictionaryFromDirectory(__DIR__ . '//Dictionary//ConstantParpAttributes//');

        foreach ($parpAttributes as $attributeKey => $attributeValue) {
            $adUser->setAttribute($attributeKey, $attributeValue);
        }

        return $adUser;
    }
}
