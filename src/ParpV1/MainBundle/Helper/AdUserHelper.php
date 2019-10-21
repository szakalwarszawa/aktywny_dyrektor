<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Helper;

use InvalidArgumentException;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\MainBundle\Tool\AdStringTool;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\Position;
use Doctrine\Common\Collections\ArrayCollection;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\Section;
use DateTime;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\MainBundle\Constants\TakNieInterface;

/**
 * Metody zamieniające ciągi tekstowe pobrane z AD na obiekty z bazy danych.
*/
class AdUserHelper
{
    /**
     * @var array
     */
    private static $adUser;

    /**
     * @var EntityManager|null
     */
    private static $entityManager = null;

    /**
     * @var ArrayCollection
     */
    private static $errors;

    /**
     * Publiczny konstruktor
     *
     * @param array $adUserArray - tablica pochodząca z seriwsu
     *      LDAP SERVICE, metody getUserFromAD()
     */
    public function __construct(array $adUserArray, EntityManager $entityManager = null, bool $throwException = true)
    {
        if (empty($adUserArray) && $throwException) {
            throw new InvalidArgumentException('Brak danych użytkownika.');
        }

        if (empty($adUserArray) && !$throwException) {
            return false;
        }

        self::$entityManager = $entityManager;
        self::$adUser = current($adUserArray);
        self::$errors = new ArrayCollection();
    }

    /**
     * Zwraca użytkownika z AD.
     *
     * @return array
     */
    public static function getAdUser(): array
    {
        return self::$adUser;
    }

    /**
     * Zwraca imię i nazwisko.
     *
     * @return string
     */
    public static function getImieNazwisko(): string
    {
        return self::$adUser[AdUserConstants::IMIE_NAZWISKO];
    }

    /**
     * Zwraca login użytkownika
     *
     * @return string
     */
    public static function getLoginUzytkownika(): string
    {
        return self::$adUser[AdUserConstants::LOGIN];
    }

    /**
     * Zwraca email
     *
     * @return string
     */
    public static function getEmail(): string
    {
        return self::$adUser[AdUserConstants::EMAIL];
    }

    /**
     * Zwraca distinguishedname
     */
    public static function getAdString(): string
    {
        return self::$adUser[AdUserConstants::AD_STRING];
    }

    /**
     * Zwraca stanowisko
     *
     * @param bool $returnObject
     *
     * @return Position|string
     */
    public static function getStanowisko(bool $returnObject = false)
    {
        $value = self::$adUser[AdUserConstants::STANOWISKO];
        if ($returnObject && self::$entityManager) {
            $position = self::$entityManager
                ->getRepository(Position::class)
                ->findOneBy([
                    'name' => $value
                ]);

            if (null === $position) {
                $message = 'Obecne stanowisko według AD: ' . $value . ' - nie istnieje w bazie danych!';
                self::addError(AdUserConstants::STANOWISKO, $message);
            }

            $value = $position;
        }

        return $value;
    }


    /**
     * Zwraca dodatkowy podpis w stopce maila
     *
     * @return string|null
     */
    public static function getDodatkowyPodpis(): ?string
    {
        if (isset(self::$adUser[AdUserConstants::DODATKOWY_PODPIS])) {
            return self::$adUser[AdUserConstants::DODATKOWY_PODPIS];
        }

        return null;
    }

    /**
     * Zwraca datę wygaśnięcia konta
     *
     * @param bool $returnTimestamp
     *
     * @return DateTime|int|null
     */
    public static function getKiedyWygasa(bool $returnTimestamp = false)
    {
        $value = self::$adUser[AdUserConstants::WYGASA];

        if (empty($value)) {
            return null;
        }

        if ($returnTimestamp) {
            return (new DateTime($value))
                ->getTimestamp();
        }

        return new DateTime($value);
    }

    /**
     * Zwraca czy konto jest wyłączone w AD.
     * Pierwszy warunek wynika z kompatybilności z adldap2.
     *
     * @return bool
     */
    public static function getCzyWylaczone(): bool
    {
        if (isset(self::$adUser[AdUserConstants::USER_ACCOUNT_CONTROL])) {
            $value = explode(',', self::$adUser[AdUserConstants::USER_ACCOUNT_CONTROL]);
            if (in_array('ACCOUNTDISABLE', $value)) {
                return TakNieInterface::TAK;
            }

            return TakNieInterface::NIE;
        }

        $value = self::$adUser[AdUserConstants::WYLACZONE];

        return 1 === $value? true : false;
    }

    /**
     * Zwraca sekcję
     *
     * @param bool $returnShort
     * @param bool $returnObject
     *
     * @return Section|string
     */
    public static function getSekcja(bool $returnShort = false, bool $returnObject = false)
    {
        if ($returnObject) {
            $value = self::$adUser[AdUserConstants::SEKCJA_NAZWA];
            $departament = self::getDepartamentNazwa(false, true);

            if (null === $departament) {
                return null;
            }

            $blankSections = [
                'ND',
                'BRAK',
                'N/D',
                'n/d'
            ];

            if (in_array($value, $blankSections) && self::$entityManager && $returnObject) {
                $section = self::$entityManager
                    ->getRepository(Section::class)
                    ->findOneBy([
                        'name' => $value
                    ])
                ;

                return $section;
            }

            if ($returnObject && self::$entityManager) {
                $section = self::$entityManager
                    ->getRepository(Section::class)
                    ->findByNameAndDepartmentShort($value, $departament->getShortname())
                ;

                if (null === $section) {
                    $message = 'Obecna sekcja według AD: ' . $value . ' - nie istnieje w bazie danych ' .
                    'lub nie jest przypisana do tego departamentu (' . $departament->getShortname() . ').';
                    self::addError(AdUserConstants::SEKCJA_NAZWA, $message);
                }

                $value = $section;
            }

            return $value;
        }

        if ($returnShort) {
            return self::$adUser[AdUserConstants::SEKCJA_SKROT];
        }

        return self::$adUser[AdUserConstants::SEKCJA_NAZWA];
    }

    /**
     * Zwraca pełną nazwę departamentu lub skrót.
     *
     * @param bool $returnShort
     * @param bool $returnObject
     *
     * @return Departament|string
     */
    public static function getDepartamentNazwa(bool $returnShort = false, bool $returnObject = false)
    {
        if ($returnObject) {
            $value = self::$adUser[AdUserConstants::DEPARTAMENT_NAZWA];
            if ($returnObject && self::$entityManager) {
                $departament = self::$entityManager
                    ->getRepository(Departament::class)
                    ->findOneBy([
                        'name' => $value,
                        'nowaStruktura' => 1
                    ]);

                if (null === $departament) {
                    $message = 'Obecny departament według AD: ' . $value . ' - nie istnieje w bazie danych!';
                    self::addError(AdUserConstants::DEPARTAMENT_NAZWA, $message);
                }

                $value = $departament;
            }

            return $value;
        }

        if ($returnShort) {
            return self::$adUser[AdUserConstants::DEPARTAMENT_SKROT];
        }

        return self::$adUser[AdUserConstants::DEPARTAMENT_NAZWA];
    }

    /**
     * Zwraca przełożonego.
     *
     * @param bool $forceParse - może to być postać stringa ActiveDirectory
     *      jeżeli true, wartość zostanie przeparsowana do postaci imię nazwisko.
     *
     * @return string
     */
    public static function getPrzelozony(bool $forceParse = true): string
    {
        $value = self::$adUser[AdUserConstants::PRZELOZONY];
        if ($forceParse) {
            $value = AdStringTool::getValue($value, AdStringTool::CN);
        }

        return $value;
    }

    /**
     * Zwraca grupy w AD.
     *
     * @return array
     */
    public static function getGrupyAd(): array
    {
        return self::$adUser[AdUserConstants::GRUPY_AD];
    }

    /**
     * Dodaje błąd.
     * Najczęściej dotyczy nie wyszukania obiektu w bazie danych.
     *
     * @param string $element
     * @param string $message
     *
     * @return void
     */
    public static function addError(string $element, string $message): void
    {
        self::$errors->add([
            'element' => $element,
            'message' => $message
        ]);
    }

    /**
     * Zwraca kolekcję błędów.
     *
     * @return ArrayCollection|null
     */
    public static function getErrors()
    {
        if (empty(self::$errors)) {
            return null;
        }

        return self::$errors;
    }

    /**
     * Zwraca pojedyńczy (jeżeli istnieje) błąd na podstawie klucza.
     * Musi zostać wywołane po renederowaniu formularza.
     *
     * @param string $element
     *
     * @return array|null
     */
    public static function getErrorMessage(string $element)
    {
        if (empty(self::$errors)) {
            return null;
        }

        foreach (self::$errors as $error) {
            if ($error['element'] === $element) {
                return $error['message'];
            }
        }

        return null;
    }
}
