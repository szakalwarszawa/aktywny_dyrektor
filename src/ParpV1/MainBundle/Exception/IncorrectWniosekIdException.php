<?php
namespace ParpV1\MainBundle\Exception;

/**
 * Klasa wyjątku IncorrectWniosekIdException
 */
class IncorrectWniosekIdException extends \Exception
{
    /**
     * Zmiana domyślnej wiadomości wyjątku.
     *
     * @var string
     */
    protected $message = 'Nie jest to właściwy ID wniosku.';
}
