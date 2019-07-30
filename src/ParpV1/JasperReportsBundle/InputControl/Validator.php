<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\InputControl;

use ParpV1\JasperReportsBundle\Exception\InvalidOptionKeyOrValueException;

/**
 * Klasa Validator
 * Klucz InputControl musi być pojedyńczym wyrazem
 * Wartość InputControl musi być pojedyńczym wyrazem
 */
class Validator
{
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
            return self::$invalidKey . ' => ' . self::$invalidValue;
        }

        return '';
    }

    /**
     * `Przelatuje` całą tablicę (klucze i wartości) i sprawdza wartości względem ::keyOrValue
     * Zwraca prawdę jeżeli tablica prawidłowa.
     *
     * @param array $data
     *
     * @return bool
     */
    public static function validateArray(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (!self::key($key) || !self::value($value)) {
                self::$invalidKey = $key;
                self::$invalidValue = $value;

                return false;
            }
        }

        return true;
    }

    /**
     * Sprawdza czy podany ciąg tekstowy jest pojedyńczym wyrazem.
     *
     * @param string $value
     *
     * @throws InvalidArgumentException gdy self::$throwException true i walidacja nie powiodła się
     *
     * @return bool
     */
    public static function keyOrValue(string $value): bool
    {
        $pattern = '/^[A-Za-z_]+$/m';
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
        return self::keyOrValue($value);
    }

    /**
     * Alias keyOrValue
     *
     * @see keyOrValue
     */
    public static function key(string $value): bool
    {
        return self::keyOrValue($value);
    }
}
