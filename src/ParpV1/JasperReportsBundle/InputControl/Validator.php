<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\InputControl;

use ParpV1\JasperReportsBundle\Exception\InvalidOptionKeyOrValueException;

/**
 * Klasa Validator
 * Klucz InputControl musi być pojedyńczym wyrazem ponieważ służy
 *  jako identyfiaktor w formularzu.
 * Wartość InputControl może zawierać spację i kilka znaków specjalnych.
 */
class Validator
{
    /**
     * @var string
     */
    const KEY = 'key';

    /**
     * @var string
     */
    const VALUE = 'value';

    /**
     * @var bool
     */
    private static $throwException;

    /**
     * @var string|null
     */
    public static $invalidKey = null;

    /**
     * @var string|null
     */
    public static $invalidValue = null;

    /**
     * Konstruktor
     */
    public function __construct(bool $throwException = false)
    {
        self::$throwException = $throwException;
    }

    /**
     * Zwraca nieprawidłową wartość jako ciąg tekstowy.
     *
     * @return string
     */
    public function invalidAsString(): string
    {
        if (null !== self::$invalidKey) {
            return self::$invalidKey . ' -> ' . self::$invalidValue;
        }

        return '';
    }

    /**
     * `Przelatuje` całą tablicę (klucze lub wartości) i sprawdza wartości względem ::keyOrValue
     * Zwraca prawdę jeżeli tablica prawidłowa.
     *
     * @param array $data
     * @param string $keyOrValue - self::KEY || self::VALUE
     *
     * @return bool
     */
    public static function validateArray(array $data, string $keyOrValue): bool
    {
        foreach ($data as $key => $value) {
            if (self::KEY === $keyOrValue) {
                if (!self::key($key)) {
                    self::$invalidKey = $key;

                    return false;
                }
                if (!self::value($value)) {
                    self::$invalidValue = $value;

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Sprawdza czy podany ciąg tekstowy klucza lub wartości InputControl jest prawidłowy.
     * Dozwolone znaki wartości: A-Z, a-z, 0-9, ,./-" spacja
     * Dozwolone znaki wartości klucza: A-Z, a-z, _
     *
     * @param string $value
     * @param string $keyOrValue - self::KEY || self::VALUE
     *
     * @throws InvalidOptionKeyOrValueException gdy self::$throwException true i walidacja nie powiodła się
     *
     * @return bool
     */
    public static function keyOrValue(string $value, string $keyOrValue): bool
    {
        $valuePattern = '/^[A-Za-z0-9_",.\/ -]+$/m';
        $keyPattern = '/^[A-Za-z_]+$/m';

        switch ($keyOrValue) {
            case self::KEY:
                $pattern = $keyPattern;
                break;
            case self::VALUE:
                $pattern = $valuePattern;
                break;
        }
        $result = 1 === preg_match($pattern, $value);

        if (self::$throwException && !$result) {
            $exceptionMessageFormat = 'Nieprawidłowa wartość (%s)';
            throw new InvalidOptionKeyOrValueException(sprintf($exceptionMessageFormat, $value));
        }

        return $result;
    }

    /**
     * Alias keyOrValue
     *
     * @see keyOrValue
     */
    public static function value(string $value): bool
    {
        return self::keyOrValue($value, self::VALUE);
    }

    /**
     * Alias keyOrValue
     *
     * @see keyOrValue
     */
    public static function key(string $value): bool
    {
        return self::keyOrValue($value, self::KEY);
    }
}
