<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Constants;

use ParpV1\LdapBundle\Constants\Attributes;

/**
 * Klasa AdUserConstants
 *
 * Klucze pochodzące z tablicy użytkownika pobranej
 * w metodzie LdapService->getUserFromAD();
 * To MUSI pokrywać się z tablicą zwracaną z AD!
 */
class AdUserConstants extends Attributes
{
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
     * Zmiana stanowiska pomiędzy grupami uprawnień.
     *
     * @var string
     */
    const STANOWISKO_GRUPA = 'stanowisko_grupa_resetu_uprawnien';

    /**
     * Zwraca wyzwalacze przy których będą resetowane uprawnienia do podstawowych.
     *
     * @todo Stanowisko jest wyłączone ponieważ trzeba obsłużyć w jakim przypadku ma byc resetowane
     *      np. zmiana stanowiska z młodszego specjalisty -> dyrektora i odwrotnie
     *      do tego służy nowy checkbox w słowniku
     *
     * @return array
     */
    public static function getResetTriggers(): array
    {
        return [
            self::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY,
            self::DEPARTAMENT_NAZWA,
            self::SEKCJA_NAZWA,
            // self::STANOWISKO,
            self::STANOWISKO_GRUPA,
            self::SEKCJA_NAZWA
        ];
    }

    /**
     * Zwraca elementy SKRÓCONEGO formularza EdycjaUzytkownikaService które mogą być zmieniane.
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
            self::WYLACZONE,
            self::DODATKOWY_PODPIS
        ];
    }

    /**
     * Zwraca elementy formularza które nie mogą być edytowane w żadnym wypadku.
     *
     * @return array
     */
    public static function getElementsLockedForAll(): array
    {
        return [
            self::LOGIN,
            self::CN_AD_STRING,
            self::IMIE_NAZWISKO,
        ];
    }
}
