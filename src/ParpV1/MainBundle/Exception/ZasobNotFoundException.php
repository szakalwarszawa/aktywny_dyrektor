<?php

namespace ParpV1\MainBundle\Exception;

class ZasobNotFoundException extends \Exception
{
    /**
     * Zmiana domyślnej wiadomości wyjątku.
     *
     * @var string
     */
    protected $message = 'Nie odnaleziono zasobu';
}
