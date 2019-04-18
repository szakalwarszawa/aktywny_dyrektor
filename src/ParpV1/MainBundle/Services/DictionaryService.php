<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Services;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Klasa pobierająca pliki słownikowe .yml z lokalizacji
 * Służy do odseparowania długich ciągów tekstowych od kodu PHP
 * żeby łatwo można je było zmienić.
 */
class DictionaryService
{
    /**
     * Tablica słownika.
     *
     * @var array
     */
    private static $dictionary = [];

    /**
     * Wartość zwracana w przypadku braku elementu w słowniku.
     *
     * @var string
     */
    private static $undefinedString = '<BRAK ELEMENTU SŁOWNIKA>';

    /**
     * Publiczny konstruktor.
     *
     * @param string|null $directory
     */
    public function __construct(string $directory = null)
    {
        if (null !== $directory) {
            self::getDictionaryFromDirectory($directory);
        }
    }

    /**
     * Zwraca wszystkie wartości słowników z podanej lokalizacji.
     * Pliki słownikowe muszą kończyć się 'dictionary.yml'.
     *
     * @param string $directory
     *
     * @return array
     */
    public static function getDictionaryFromDirectory(string $directory): array
    {
        $finder = new Finder();

        $dictionaryList = [];
        $dictionaryFiles = $finder
            ->name('*dictionary.yml')
            ->in($directory)
        ;

        foreach ($dictionaryFiles as $file) {
            $dictionaryList[] = Yaml::parseFile($file);
        }

        $flattenArray = iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($dictionaryList)));
        self::$dictionary = $flattenArray;

        return $flattenArray;
    }

    /**
     * Zwraca pojedyńczą wartość ze słownika.
     *
     * @param string $key
     *
     * @return string
     */
    public static function get(string $key): string
    {
        if (array_key_exists($key, self::$dictionary)) {
            return self::$dictionary[$key];
        }

        return self::$undefinedString;
    }
}
