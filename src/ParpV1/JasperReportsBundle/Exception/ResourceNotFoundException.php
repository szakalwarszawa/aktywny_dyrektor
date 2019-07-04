<?php
namespace ParpV1\JasperReportsBundle\Exception;

use Exception;

/**
 * Wyjątek rzucany gdy nie odnaleziono zasobu.
 */
class ResourceNotFoundException extends Exception
{
    /**
     * @var string
     */
    public $message = 'Nie odnaleziono zasobu';
}
