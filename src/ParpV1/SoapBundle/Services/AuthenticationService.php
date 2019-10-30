<?php

namespace ParpV1\SoapBundle\Services;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class AuthenticationService
{
    public function __construct()
    {
    }

    /**
     * @param $login
     * @param $password
     * @param 0 $version
     * @param null $method
     * @return string
     *
     * Opis:
     * Funkcja obsługująca logowanie się do systemu. Jest wywoływana jako webservice. Jako parametry potrzebuje
     * loginu, hasła. Opcjonalnymi parametrami jest wersja [$version] (domyślnie zero) oraz metoda [$method]
     *
     */
    public function login($login, $password, $version = 0, $method = null)
    {
        $loginDetails = array();

        $loginDetails["authenticated"] = true;
        if ($loginDetails["authenticated"]) {
            $loginDetails["username"] = $login;
            $loginDetails["password"] = $password;
            $loginDetails["roles"][] = "ROLE_ADMIN";
            $loginDetails["roles"][] = "ROLE_USER";
            $loginDetails["roles"][] = "ROLE_PARP";
        }

        return $loginDetails;
    }

    public function changePassword($login, $oldPassword, $newPassword, $version = 0, $method = null)
    {
    }
}
