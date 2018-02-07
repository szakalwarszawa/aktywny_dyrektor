<?php

namespace ParpV1\MainBundle\Api\Exception;

/**
 * Podstawowy wyjątek dla operacji powiązanych z API.
 *
 * Ten wyjątek zazwyczaj nie powinien być rzucany bezpośrednia.
 * Jego celem jest identyfikacja wyjątków dziedziczących jako powiązanych z API.
 */
class ApiException extends \Exception
{
}
