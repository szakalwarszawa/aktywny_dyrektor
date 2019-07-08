<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Constants;

use ParpV1\LdapBundle\Constants\Attributes;

/**
 * Klasa określająca jakie atrybuty mogą być zwrócone z AD
 * przy pobieraniu danych użytkownika.
 */
class AllowedToFetchAttributes extends Attributes
{
    /**
     * Zwraca wszystkie atrybuty dozwolone do pobrania.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return [
            self::LOGIN,
            self::IMIE_NAZWISKO,
            self::NAZWISKO,
            self::IMIE,
            self::EMAIL,
            self::STANOWISKO,
            self::DEPARTAMENT_NAZWA,
            self::DEPARTAMENT_SKROT,
            self::SEKCJA_SKROT,
            self::SEKCJA_NAZWA,
            self::PRZELOZONY,
            self::GRUPY_AD,
            self::INICJALY,
            self::WYGASA,
            self::WYLACZONE,
            self::POWOD_WYLACZENIA,
            self::AD_STRING,
            self::CN_AD_STRING,
            self::OPTIONAL_ATTRIBUTE,
        ];
    }
}
