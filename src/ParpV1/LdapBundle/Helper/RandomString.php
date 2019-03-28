<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Helper;

/**
 * Zawiera metody pomocnicze przy generowaniu losowych stringów.
 */
class RandomString
{
    /**
     * Generuje ciąg tekstowy o określonej długości.
     *
     * @param int $length
     *
     * @return string
     */
    public static function generate(int $length = 10)
    {
        $random = bin2hex(openssl_random_pseudo_bytes($length));

        return substr($random, 0, $length);
    }
}

