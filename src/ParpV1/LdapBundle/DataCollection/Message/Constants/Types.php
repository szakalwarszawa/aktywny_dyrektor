<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\DataCollection\Message\Constants;

/**
 * Typy kolekcjonowanych wiadomości.
 * Nazwy typów pokrywają się z bootstrapowymi klasami alertów.
 */
class Types
{
    /**
     * Typ błąd - anulowanie akcji.
     *
     * @var string
     */
    const ERROR = 'danger';

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
     * Typ sukces - kontynuowanie akcji.
     *
     * @var string
     */
    const SUCCESS = 'success';

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
            self::SUCCESS,
        ];
    }
}
