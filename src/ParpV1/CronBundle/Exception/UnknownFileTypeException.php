<?php
namespace ParpV1\CronBundle\Exception;

/**
 * Klasa wyjątku UnknownFileTypeException
 */
class UnknownFileTypeException extends \Exception
{
    /**
     * Zmiana domyślnej wiadomości wyjątku.
     *
     * @var string
     */
    protected $message = 'Plik z takim rozszerzeniem nie jest obsługiwany.';
}
