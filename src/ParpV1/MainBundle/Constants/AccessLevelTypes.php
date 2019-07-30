<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Constants;

/**
 * Klasa AccessLevelTypes
 * Rodzaj uprawnień tj. Grupa uprawnień lub pojedyncze uprawnienia.
 */
class AccessLevelTypes
{
    /**
     * @var int
     */
    const GROUP = 0;

    /**
     * @var int
     */
    const SINGLE = 1;

    /**
     * Zwraca wszystkie dostępne typy.
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::GROUP,
            self::SINGLE
        ];
    }

    /**
     * Zwraca dane do elementu wyboru formularza.
     *
     * @return array
     */
    public static function mapToForm(): array
    {
        return [
            'Grupy uprawnień' => self::GROUP,
            'Pojedyncze uprawnienia' => self::SINGLE
        ];
    }
}
