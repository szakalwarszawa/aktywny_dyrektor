<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Constants;

/**
 * Klasa AkcjeWnioskuConstants
 *
 * Powody wpisywane przy anulowaniu wniosku.
 * Przedrostki w stalych pokrywają się z klasa AdUserConstants
 */
class PowodAnulowaniaWnioskuConstants
{
    /**
     * @var string
     */
    const SUFFIX = '_TITLE';

    /**
     * @var string
     */
    const DEFAULT_TITLE = 'Zresetowano do uprawnień początkowych.';

    /**
     * @var string
     */
    const DEPARTAMENT_NAZWA_TITLE = 'Zmiana D/B';

    /**
     * @var string
     */
    const SEKCJA_NAZWA_TITLE = 'Zmiana sekcji';

    /**
     * @var string
     */
    const STANOWISKO_TITLE = 'Zmiana stanowiska';

    /**
     * @var string
     */
    const WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY_TITLE = 'Rozwiązanie umowy o pracę';

    /**
     * @var string
     */
    const WYLACZENIE_KONTA_NIEOBECNOSC_TITLE = 'Długotrwała nieobecność';

    /**
     * @var string
     */
    const PRZELOZONY_TITLE = 'Zmiana przełożonego';
}
