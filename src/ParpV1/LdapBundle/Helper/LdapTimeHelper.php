<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Helper;

/**
 * Zawiera metody pomocnicze przy operacjach na czasie.
 */
class LdapTimeHelper
{
    /**
     * Zamienia czas unixowy na ldapowy.
     *
     * @param int $unixTimestamp
     *
     * @return string
     */
    public static function unixToLdap(int $unixTimestamp): string
    {
        return sprintf('%.0f', ($unixTimestamp + 11644473600) * 10000000);
    }
}
