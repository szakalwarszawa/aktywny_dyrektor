<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\InputControl;

use InvalidArgumentException;

/**
 * Klasa Validate
 * Klucz InputControl musi być pojedyńczym wyrazem
 * Wartość InputControl musi być pojedyńczym wyrazem
 */
class Validate
{
    /**
     * @var bool
     */
    private static $throwException;

    /**
     * Konstruktor
     */
    public function __construct(bool $throwException = false)
    {
        self::$throwException = $throwException;
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
        $pattern = '/^[A-Za-z]+$/m';
        $result = 1 === preg_match($pattern, $value);

        if (self::$throwException && !$result) {
            $exceptionMessageFormat = 'Nieprawidłowa wartość (%s)';
            throw new InvalidArgumentException(sprintf($exceptionMessageFormat, $value));
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
