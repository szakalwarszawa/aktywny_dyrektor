<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Constants;

/**
 * Klasa AdUserConstants
 *
 * Klucze pochodzące z tablicy użytkownika pobranej
 * w metodzie LdapService->getUserFromAD();
 * To MUSI pokrywać się z tablicą zwracaną z AD!
 */
class AdUserConstants
{
    /**
     * @var string
     */
    const LOGIN = 'samaccountname';

    /**
     * @var string
     */
    const IMIE_NAZWISKO = 'name';

    /**
     * @var string
     */
    const EMAIL = 'email';

    /**
     * @var string
     */
    const STANOWISKO = 'title';

    /**
     * @var string
     */
    const DEPARTAMENT_NAZWA = 'department';

    /**
     * @var string
     */
    const DEPARTAMENT_SKROT = 'description';

    /**
     * @var string
     */
    const SEKCJA_SKROT = 'division';

    /**
     * @var string
     */
    const SEKCJA_NAZWA = 'info';

    /**
     * @var string
     */
    const PRZELOZONY = 'manager';

    /**
     * @var string
     */
    const GRUPY_AD = 'memberOf';

    /**
     * @var string
     */
    const INICJALY = 'initials';

    /**
     * @var string
     */
    const WYGASA = 'accountExpires';

    /**
     * @var string
     */
    const WYLACZONE = 'isDisabled';

    /**
     * @var string
     */
    const POWOD_WYLACZENIA = 'disableDescription';

    /**
     * @var string
     */
    const WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY = 'wyl_konta_rozwiazanie_umowy';

    /**
     * @var string
     */
    const WYLACZENIE_KONTA_NIEOBECNOSC = 'wyl_konta_nieobecnosc';

    /**
     * W przypadku zmiany wielu pól na formularzu wnioski również będą czyszczone.
     *
     * @var string
     */
    const FORCE_CLEAN = 'force_clean';

    /**
     * Zwraca wyzwalacze przy których będą resetowane uprawnienia do podstawowych.
     *
     * @return array
     */
    public static function getResetTriggers(): array
    {
        return [
            self::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY,
            self::SEKCJA_NAZWA,
            self::DEPARTAMENT_NAZWA,
            self::STANOWISKO,
            self::SEKCJA_NAZWA
        ];
    }

    /**
     * Zwraca elementy formularza EdycjaUzytkownikaService które mogą być zmieniane.
     *
     * @return array
     */
    public static function getElementsAllowedToChange(): array
    {
        return [
            self::POWOD_WYLACZENIA,
            self::PRZELOZONY,
            self::SEKCJA_NAZWA,
            self::WYGASA,
            self::WYLACZONE
        ];
    }
}
