<?php

namespace ParpV1\MainBundle\Api\Exception;

use ParpV1\MainBundle\Api\Exception\ApiException;

/**
 * Wyjątek rzucany jeśli np. przygotowana treść odpowiedzi nie odpowiada pod kątem
 * wartości i/lub struktury założeniem (np. brak wymaganych wartości).
 */
class InvalidContentExcpetion extends ApiException
{
}
