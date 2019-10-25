<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\Helper;

use ParpV1\LdapBundle\Constants\Attributes;
use ReflectionClass;

/**
 * Zawiera metody do generowania getterów/setterów na podstawie atrybutów AD.
 */
class AttributeGetterSetterHelper
{
    /**
     * Przedrostek nazwy funkcji
     *
     * @var string
     */
    const GET = 'get';

    /**
     * Przedrostek nazwy funkcji
     *
     * @var string
     */
    const SET = 'set';


    /**
     * Zwraca getter na podstawie atrybutu.
     *
     * @param string $attribute
     *
     * @return string
     */
    public static function get(string $attribute)
    {
        self::validateAttribute($attribute);

        return self::generate(self::GET, $attribute);
    }

    /**
     * Zwraca setter na podstawie atrybutu.
     *
     * @param string $attribute
     *
     * @return string
     */
    public static function set(string $attribute)
    {
        self::validateAttribute($attribute);

        return self::generate(self::SET, $attribute);
    }

    /**
     * Generuje gotowy string np. `getSamaccountName`'
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    private static function generate(string $key, string $value): string
    {
        return $key . ucfirst($value);
    }

    /**
     * Sprawdza czy istnieje taki atrybut w stałych.
     *
     * @param string $attribute
     *
     * @return void
     *
     * @throws \Exception gdy atrybut nie istnieje.
     */
    public static function validateAttribute(string $attribute): void
    {
        $allAttributes = (new ReflectionClass(Attributes::class))
            ->getConstants()
        ;

        if (!in_array($attribute, $allAttributes)) {
            throw new \Exception('Nie ma takiego atrybutu.');
        }
    }
}
