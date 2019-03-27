<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\MessageCollector\Constants;

/**
 * Typy kolekcjonowanych wiadomości.
 */
class Types
{
    /**
     * Typ błąd - anulowanie akcji.
     *
     * @var string
     */
    const ERROR = 'error';

    /**
     * Typ ostrzeżenie - kontynuowanie akcji.
     *
     * @var string
     */
    const WARNING = 'warning';

    /**
     * Typ informacyjny - kontynuowanie akcji.
     *
     * @var string
     */
    const INFO = 'info';

    /**
     * ZWraca typy wiadomości
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::ERROR,
            self::WARNING,
            self::INFO,
        ];
    }
}
