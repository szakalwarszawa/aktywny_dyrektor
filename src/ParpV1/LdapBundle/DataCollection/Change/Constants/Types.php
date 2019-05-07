<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\DataCollection\Change\Constants;

/**
 * Typy kolekcjonowanych zmian.
 */
class Types
{
    /**
     * Typ zmiany na użytkowniku w AD.
     *
     * @var string
     */
    const AD_USER = 'ad_user';

    /**
     * Zwraca typy zmian
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::AD_USER,
        ];
    }
}
