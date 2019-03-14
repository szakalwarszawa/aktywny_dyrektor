<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Tool;

/**
 * Klasa narzędzia AdStringTool.
 * Zawiera metody pomocnicze przy operacjach na wartościach z AD.
 */
class AdStringTool
{
    /**
     * @var string
     */
    const CN = 'CN=';

    /**
     * @var string
     */
    const OU = 'OU=';

    /**
     * @var string
     */
    const DC = 'DC=';

    /**
     * Pobiera tylko określoną ze stringa Active Directory.
     *
     * @param string $adString
     *
     * @return array|string
     */
    public static function getValue(string $adString, string $valueKey)
    {
        if (false === strpos($adString, '=')) {
            return $adString;
        }

        $adStringParts = self::ldapExplodeUtf($adString);
        $returnArray = in_array($valueKey, [self::OU, self::DC]);
        $stringContainer = [];
        foreach ($adStringParts as $key => $value) {
            if (is_string($value)) {
                $containsCn = strpos($value, $valueKey);
                if (false !== $containsCn) {
                    if ($returnArray) {
                        $stringContainer[] = substr($value, strlen($valueKey));
                        continue;
                    }

                    return substr($value, strlen($valueKey));
                }
            }
        }

        if ($returnArray){
            return $stringContainer;
        }

        return $adString;
    }

    /**
     * Rozbija AD String to tablicy.
     * Wykonuje dodatkową operację do ldap_explode_dn
     * który ucina UTF-8. Ta metoda odzyskuje utracone znaki.
     *
     * @param string
     *
     * @return array
     */
    public static function ldapExplodeUtf(string $adString): array
    {
        $adStringParts = ldap_explode_dn($adString, 0);

        foreach ($adStringParts as $key => $value) {
            $adStringParts[ $key ] = preg_replace_callback(
                "/\\\([0-9A-Fa-f]{2})/",
                function ($matches) {
                    return chr(hexdec($matches[1]));
                },
                $value
            );
        }

        return $adStringParts;
    }
}
