<?php

namespace ParpV1\MainBundle\Constants;

/**
 * Interfejs definiuje stałe określające wartości logiczne.
 * Zastosowanie liczb pozwala zabezpieczyć się przez zmianą typu logicznego danej
 * na typ słownikowy (np. dodanie opcji "nie wiem", "nie dotyczy", itp.).
 *
 * @see LSI1420
 */
interface TakNieInterface
{
    const TAK = true;
    const NIE = false;

    const T = 1;
    const N = 0;
    const D = 3;
}
