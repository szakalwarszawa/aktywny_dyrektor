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
     * @param int|null $unixTimestamp
     *
     * @return null|string
     */
    public static function unixToLdap($unixTimestamp)
    {
        if (null === $unixTimestamp) {
            return null;
        }

        return sprintf('%.0f', ($unixTimestamp + 11644473600) * 10000000);
    }

    /**
     * Zamienia czas ldapowy na unixowy timestamp.
     *
     * @param int|null $ldapTimestamp
     *
     * @return null|int
     */
    public static function ldapToUnix($ldapTimestamp)
    {
        if (null === $ldapTimestamp) {
            return null;
        }

        return (int) ($ldapTimestamp / 10000000) - 11644473600;
    }
}
