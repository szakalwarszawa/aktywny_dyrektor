<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Tool;

use InvalidArgumentException;

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
     * Domena AD z parametrów.
     * np. `test.local`
     *
     * @var string|null
     */
    private static $baseAdDomain = null;

    /**
     * OU z parametrów.
     *
     * @var string|null
     */
    private static $baseAdOu = null;

    /**
     * Publiczny konstruktor
     *
     * @param string $baseAdDomain
     * @param stirng $baseAdOu
     */
    public function __construct(string $baseAdDomain = null, string $baseAdOu = null)
    {
        self::$baseAdDomain = $baseAdDomain;
        self::$baseAdOu = $baseAdOu;
    }

    /**
     * Buduje string AD dla podanej nazwy użytkownika z ze skrótem departamentu.
     * Zwraca postać typu "CN=Janusz Tracz,OU=BI,OU=Zespoly_2016,OU=PARP Pracownicy,DC=test,DC=local"
     *
     * @param string $commonName - np. Janusz Tracz
     * @param string $departmentShort - np. BI
     *
     * @return string
     */
    public static function createBaseUserString(string $commonName, string $departmentShort): string
    {
        $domainComponent = explode('.', self::$baseAdDomain);
        if (2 !== count($domainComponent)) {
            throw new InvalidArgumentException('Nieprawidłowo ustawiony parametr OU.');
        }

        $stringParts = [
            self::CN . $commonName,
            self::OU . $departmentShort,
            self::$baseAdOu,
            self::DC . current($domainComponent),
            self::DC . end($domainComponent)
        ];

        return implode(',', $stringParts);
    }

    /**
     * Podmienia element w stringu Active Directory na podany.
     *
     * @param string $adString
     * @param string $valueKey - self::CN np.
     * @param string $replaceTo - wartość która zostanie wstawiona w miejsce $valueKey
     * @param int $ouIndex - AD string zawiera kilka wartości OU, przy zamianie
     *      trzeba podać który ma być zamieniony.
     *
     * @return string
     */
    public static function replaceValue(string $adString, string $valueKey, string $replaceTo, int $ouIndex = 0): string
    {
        $adStringParts = self::ldapExplodeUtf($adString);
        unset($adStringParts['count']);

        $loopIndex = 0;
        if ($valueKey !== self::CN) {
            foreach ($adStringParts as $key => $value) {
                if (false !== strpos($value, $valueKey)) {
                    if ($ouIndex === $loopIndex) {
                        $tempArray = explode('=', $value);
                        $tempArray[count($tempArray)-1] = $replaceTo;
                        $adStringParts[$key] = implode('=', $tempArray);
                    }
                    $loopIndex++;
                }
            }

            if ($loopIndex <= $ouIndex) {
                throw new InvalidArgumentException(
                    'Podany AD string nie posiada tylu (min: 0, max: ' . ($loopIndex-1) . ') kluczy ' . $valueKey
                );
            }

            return implode(',', $adStringParts);
        }

        foreach ($adStringParts as $key => $value) {
            $containsKey = strpos($value, $valueKey);
            if (false !== $containsKey) {
                $tempArray = explode('=', $value);
                $tempArray[count($tempArray)-1] = $replaceTo;
                $adStringParts[$key] = implode('=', $tempArray);
            }
        }

        return implode(',', $adStringParts);
    }

    /**
     * Pobiera tylko określoną ze stringa Active Directory.
     *
     * @param string $adString
     * @param string $valueKey
     *
     * @return array|string
     */
    public static function getValue(string $adString, string $valueKey)
    {
        if (false === strpos($adString, '=')) {
            return $adString;
        }

        $adString = self::replaceLowerCase($adString);
        $adStringParts = self::ldapExplodeUtf($adString);
        $returnArray = in_array($valueKey, [self::OU, self::DC]);
        $stringContainer = [];
        foreach ($adStringParts as $key => $value) {
            if (is_string($value)) {
                $containsKey = strpos($value, $valueKey);
                if (false !== $containsKey) {
                    if ($returnArray) {
                        $stringContainer[] = substr($value, strlen($valueKey));
                        continue;
                    }

                    return substr($value, strlen($valueKey));
                }
            }
        }

        if ($returnArray) {
            return $stringContainer;
        }

        return $adString;
    }

    /**
     * Zamienia atrybuty CN, OU, DC na wielkie litery (jeżeli jest wpisane małymi).
     *
     * @param string $adString
     *
     * @return string
     */
    private static function replaceLowerCase(string $adString): string
    {
        $adString = str_replace(strtolower(self::DC), self::DC, $adString);
        $adString = str_replace(strtolower(self::OU), self::OU, $adString);
        $adString = str_replace(strtolower(self::CN), self::CN, $adString);

        return $adString;
    }

    /**
     * Zwraca imię i nazwisko użytkownika ze stringa distinguishedname.
     *
     * @param string $adString
     *
     * @return array
     */
    public static function getUserFirstLastName(string $adString): array
    {
        $nameSplit = explode(' ', self::getValue($adString, self::CN));
        $userData = [
            'full_name' => implode(' ', $nameSplit),
            'first_name' => array_pop($nameSplit),
            'last_name' => implode(' ', $nameSplit)
        ];

        return $userData;
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
            $adStringParts[$key] = preg_replace_callback(
                "/\\\([0-9A-Fa-f]{2})/",
                function ($matches) {
                    return chr(hexdec($matches[1]));
                },
                $value
            );
        }

        return $adStringParts;
    }

    /**
     * Z pełnego stringa AD obcina część CN= aby została tylko gałąź w AD.
     *
     * @param string $adString
     *
     * @return string
     */
    public static function getParentRootFromDn(string $adString): string
    {
        $stringSplit = explode(',', $adString);
        array_shift($stringSplit);

        return implode(',', $stringSplit);
    }
}
