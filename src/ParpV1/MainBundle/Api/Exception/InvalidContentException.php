<?php

namespace ParpV1\MainBundle\Api\Exception;

use ParpV1\MainBundle\Api\Exception\ApiException;

/**
 * Wyjątek rzucany jeśli zawartość (obiektu, tablicy, JSON, itp.) nie odpowiada pod kątem
 * wartości i/lub struktury założeniem (np. brak wymaganych wartości w obiekcie odpowiedzi JSON).
 *
 * Ten wyjątek najcześciej powoduje zwrotną odpowiedź z API o statusue 422 Unprocessabel Entity.
 */
class InvalidContentException extends ApiException
{
}
