<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\Constants;

use ParpV1\LdapBundle\Constants\Attributes;

/**
 * Klasa określająca jakie atrybuty mogą zostać wypchane z wartością NULL.
 */
class NullableAttributes extends Attributes
{
    /**
     * Zwraca wszystkie atrybuty które mogą być NULLem.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return [
            self::WYGASA,
            self::POWOD_WYLACZENIA,
            self::DODATKOWY_PODPIS,
        ];
    }
}
