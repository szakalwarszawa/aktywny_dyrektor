<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Services;

use ParpV1\MainBundle\Form\EdycjaUzytkownikaFormType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use LogicException;
use ReflectionClass;
use UnexpectedValueException;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\SoapBundle\Services\LdapService;
use ParpV1\MainBundle\Helper\AdUserHelper;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Constants\TakNieInterface;
use ParpV1\MainBundle\Constants\PowodAnulowaniaWnioskuConstants;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Entity\OdebranieZasobowEntry;
use ParpV1\MainBundle\Tool\AdStringTool;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\MainBundle\Entity\Section;
use ParpV1\MainBundle\Entity\Departament;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use ParpV1\LdapBundle\Helper\LdapTimeHelper;

class EdycjaUzytkownikaService
{
    /**
     * @var FormInterface|null
     */
    private $form = null;

    /**
     * @var LdapService
     */
    private $ldapService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var ArrayCollection
     */
    private $errors;

    /**
     * @var array
     */
    private $adParameters;

    /**
     * Czas opóźnienia 'zmiana obowiązuje od'
     *
     * @var string
     */
    private $adPushDelay = '30 minutes';

    /**
     * Publiczny konstruktor
     *
     * @param Form $form
     */
    public function __construct(
        LdapService $ldapService,
        EntityManager $entityManager,
        UserService $userService,
        string $baseAdDomain,
        string $baseAdOu,
        string $adPushDelay
    ) {
        $this->ldapService = $ldapService;
        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->errors = new ArrayCollection();
        $this->adParameters = [
            'ad_domain' => $baseAdDomain,
            'ad_ou' => $baseAdOu
        ];
        $this->adPushDelay = $adPushDelay;
    }

    /**
     * Set form
     *
     * @param FormInterface $form
     *
     * @return EdycjaUzytkownikaService
     *
     * @throws UnexpectedTypeException gdy nieobsługiwany typ formularza
     */
    public function setForm(FormInterface $form): EdycjaUzytkownikaService
    {
        if (null !== $form) {
            $paramFormType = $form
                ->getConfig()
                ->getType()
                ->getInnerType()
            ;
            if (!$paramFormType instanceof EdycjaUzytkownikaFormType) {
                throw new UnexpectedTypeException($paramFormType, EdycjaUzytkownikaFormType::class);
            }

            $this->form = $form;
        }

        return $this;
    }

    public function saveNewEntry()
    {
        $form = $this->form;

        if (null === $form) {
            throw new LogicException('Nie zdefiniowano formularza.');
        }

        $formData = $form->getData();
        $validSectionDepartment = $this->isValidSectionDepartment(
            $formData[AdUserConstants::SEKCJA_NAZWA],
            $formData[AdUserConstants::DEPARTAMENT_NAZWA]
        );

        if (!$validSectionDepartment) {
            $this
                ->errors
                ->add([
                    'message' => 'Wybrana sekcja nie istnieje w podanym departamencie.'
                ])
            ;

            return $this;
        }

        $entry = new Entry(
            $this
                ->userService
                ->getCurrentUser()
                ->getUsername()
        );
        $adStringTool = new AdStringTool($this->adParameters['ad_domain'], $this->adParameters['ad_ou']);
        $entry
            ->setCn($formData[AdUserConstants::IMIE_NAZWISKO])
            ->setAccountExpires($formData[AdUserConstants::WYGASA])
            ->setDepartment($formData[AdUserConstants::DEPARTAMENT_NAZWA])
            ->setInfo($formData[AdUserConstants::SEKCJA_NAZWA])
            ->setDivision($formData[AdUserConstants::SEKCJA_NAZWA]->getShortname())
            ->setTitle($formData[AdUserConstants::STANOWISKO])
            ->setSamaccountname($formData[AdUserConstants::LOGIN])
            ->setOpis('Nowe konto')
            ->setAccountExpires($formData[AdUserConstants::WYGASA])
            ->setManager($formData[AdUserConstants::PRZELOZONY])
            ->setFromWhen($formData['zmianaOd'])
            ->setDistinguishedName(
                $adStringTool::createBaseUserString(
                    $formData[AdUserConstants::IMIE_NAZWISKO],
                    $formData[AdUserConstants::DEPARTAMENT_NAZWA]->getShortname()
                )
            )
            ->setActivateDeactivated(true)
        ;

        $this
            ->entityManager
            ->persist($entry)
        ;

        return $this;
    }

    /**
     * Tworzy nowe entry na podstawie danych z formularza (lub danych z kluczami formularza).
     *
     * @param array $formData
     * @param AdUserHelper $adUserHelper
     * @param string $description
     *
     * @return bool
     */
    private function createEntry(array $formData)
    {
        $entry = new Entry(
            $this
                ->userService
                ->getCurrentUser()
                ->getUsername()
        );
        $entry
            ->setCn($formData[AdUserConstants::IMIE_NAZWISKO])
            ->setAccountExpires($formData[AdUserConstants::WYGASA])
            ->setDepartment($formData[AdUserConstants::DEPARTAMENT_NAZWA])
            ->setInfo($formData[AdUserConstants::SEKCJA_NAZWA])
            ->setTitle($formData[AdUserConstants::STANOWISKO])
            ->setSamaccountname($formData[AdUserConstants::LOGIN])
            ->setOpis('Nowe konto')
            ->setAccountExpires($formData[AdUserConstants::WYGASA])
            ->setManager($formData[AdUserConstants::PRZELOZONY])
            ->setFromWhen($formData['zmianaOd'])
        ;

        return $entry;
    }

    /**
     * Sprawdza czy wybrana sekcja i departament są poprawne.
     * Czy nie została wybrana sekcja spoza departamentu.
     *
     * @param Section $section
     * @param Departament $departament
     *
     * @return bool
     */
    private function isValidSectionDepartment(Section $section, Departament $department): bool
    {
        return $section->getDepartament() === $department;
    }

    /**
     * Sprawdza czy są zmiany formularz <=> AD.
     * Jeżeli są to tworzy entry do wypchnięcia.
     * Jeżeli został zmieniony atrybut przez który trzeba będzie anulować wnioski - robi to.
     *
     * @return bool
     *
     * @throws UnexpectedValueException gdy zmieniono pole niepodlegające zmianie (blokada względem roli)
     * @throws UnexpectedValueException gdy zmieniono pole niepodlegające zmianie (edycja zablokowanego na stałe pola)
     */
    public function saveEditEntry(): bool
    {
        $form = $this->form;

        if (null === $form) {
            throw new LogicException('Nie zdefiniowano formularza.');
        }

        $formData = $form->getData();
        $adUserHelper = $this->getAdUserHelper($formData[AdUserConstants::LOGIN], false);
        $changedElements = $this->compareDataCreateEntry($adUserHelper);

        if (empty($changedElements)) {
            return false;
        }

        foreach ($changedElements as $key => $element) {
            if (!$this->userService->getCurrentUser()->hasRole('PARP_ADMIN_REJESTRU_ZASOBOW')) {
                if (!in_array($element, AdUserConstants::getElementsAllowedToChange()) && is_int($key)) {
                    throw new UnexpectedValueException(
                        'Zmieniono pole niepodlegające zmianie w AkD! Twoje role na to nie pozwalają.'
                    );
                }
            }

            if (in_array($element, AdUserConstants::getElementsLockedForAll()) && is_int($key)) {
                throw new UnexpectedValueException(
                    'Zmieniono pole niepodlegające zmianie w AkD!'
                );
            }
        }

        $createOdebranieZasobowEntry = false;
        $reason = null;
        if (!empty(array_intersect($changedElements, AdUserConstants::getResetTriggers()))) {
            $createOdebranieZasobowEntry = true;
            $reason = $this->specifyCancellationReason($changedElements, $formData);
        }

        $changeDate = function (DateTime $date) {
            $currentDate = new DateTime('today');
            if (0 === ($date->diff($currentDate))->days) {
                $newDate = (new DateTime())
                    ->modify('+' . $this->adPushDelay)
                ;

                return $newDate;
            }

            return $date;
        };

        $entry = new Entry();
        $entry
            ->setCn($formData[AdUserConstants::IMIE_NAZWISKO])
            ->setAccountExpires($formData[AdUserConstants::WYGASA])
            ->setDepartment($formData[AdUserConstants::DEPARTAMENT_NAZWA])
            ->setInfo($formData[AdUserConstants::SEKCJA_NAZWA])
            ->setTitle($formData[AdUserConstants::STANOWISKO])
            ->setSamaccountname($formData[AdUserConstants::LOGIN])
            ->setIsDisabled($formData[AdUserConstants::WYLACZONE])
            ->setOpis(isset($reason)? $reason : null)
            ->setAccountExpires($formData[AdUserConstants::WYGASA])
            ->setManager(
                AdStringTool::replaceValue(
                    $adUserHelper::getPrzelozony(false),
                    AdStringTool::CN,
                    $formData[AdUserConstants::PRZELOZONY]
                )
            )
            ->setDisableDescription($formData[AdUserConstants::POWOD_WYLACZENIA])
            ->setFromWhen($changeDate($formData['zmianaOd']))
        ;

        if ($createOdebranieZasobowEntry) {
            $entry = $this->grantInitialRights($entry, $formData, $formData[AdUserConstants::LOGIN]);

            $odebranieZasobowEntry = new OdebranieZasobowEntry();
            $odebranieZasobowEntry
                ->setPowodOdebrania($reason)
                ->setUzytkownik($formData[AdUserConstants::LOGIN])
            ;

            $this
                ->entityManager
                ->persist($odebranieZasobowEntry)
            ;

            $entry->setOdebranieZasobowEntry($odebranieZasobowEntry);
        }

        if (AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY === $formData[AdUserConstants::POWOD_WYLACZENIA]) {
            $entry->setMemberOf(null);
        }

        $this
            ->entityManager
            ->persist($entry)
        ;

        return true;
    }

    /**
     * Na podstawie nowych danych z formularza nadaje uprawnienia
     * początkowe do wypchnięcia w entry.
     *
     * @param Entry $entry
     * @param array $newData - do nadania uprawnień początkowych
     *      potrzebny jest w zasadzie tylko departament (obj), sekcja (obj)
     *      z kluczami z klasy stałych AdUserConstants
     * @param string $username
     */
    public function grantInitialRights(Entry $entry, array $newData, string $username): Entry
    {
        $adUserData = $this
            ->ldapService
            ->getUserFromAD($username)
        ;
        $initialRights = $this
            ->ldapService
            ->getGrupyUsera(
                current($adUserData),
                $newData[AdUserConstants::DEPARTAMENT_NAZWA],
                $newData[AdUserConstants::SEKCJA_NAZWA]
            );

        $entry->addGrupyAD($initialRights, '+');

        return $entry;
    }

    /**
     * Porównuje zmiany na formularzu edycji użytkownikami z Active Directory.
     *
     * @param AdUserHelper|null $adUserHelper
     *
     * @throws LogicException gdy nie zdefiniowano formularza
     * @throws UnexpectedValueException gdy przekazano niepoprawny formularz
     * @throws UnexpectedValueException gdy nie pobrano danych z AD
     *
     * @todo myląca nazwa
     * bo to porownuje Z AD
     *
     *
     * @return
     */
    private function compareDataCreateEntry(AdUserHelper $adUserHelper = null)
    {
        $form = $this->form;

        if (null === $form) {
            throw new LogicException('Nie zdefiniowano formularza.');
        }

        $formDataFiltered = $this->removeNonAdElements($form->getData());

        if (!isset($formDataFiltered[AdUserConstants::LOGIN])) {
            throw new UnexpectedValueException('Formularz niepoprawny');
        }
        if (null === $adUserHelper) {
            $adUserHelper = $this->getAdUserHelper($formDataFiltered[AdUserConstants::LOGIN]);
        }

        $changedElements = $this->compareData($formDataFiltered, $adUserHelper);

        return $changedElements;
    }

    /**
     * Zwraca obiekt AdUserHelper na podstawie loginu użytkownika.
     *
     * @param string $login
     * @param bool $throwNotFoundException
     *
     * @return AdUserHelper
     */
    public function getAdUserHelper(string $login, bool $throwNotFoundException = true): AdUserHelper
    {
        $adUserData = $this
            ->ldapService
            ->getUserFromAD($login)
        ;

        return new AdUserHelper($adUserData, $this->entityManager, $throwNotFoundException);
    }

    /**
     * Na podstawie zdarzenie określenie powodu anulowania wniosków.
     * Wyłączenie konta ma najwyższy priorytet, więc jeżeli np. zmieniono sekcję i wyłączono konto
     * to zwróconym powodem będzie wyłącznie konta.
     * Jeżeli jest więcej niż jedna zmiana to zwracany jest domyślny powód.
     *
     * @param array $changedElements
     * @param array $formData
     *
     * @return string
     *
     * @throws LogicException gdy nie odnaleziono powodu anulacji w stałych.
     */
    private function specifyCancellationReason(array $changedElements, array $formData): string
    {
        $powodyAnulowaniaWnioskuConstants = new ReflectionClass(PowodAnulowaniaWnioskuConstants::class);
        $triggerConstKeys = (new ReflectionClass(AdUserConstants::class))
            ->getConstants();

        $specifyReason = function ($trigger) use ($powodyAnulowaniaWnioskuConstants, $triggerConstKeys) {
            $triggerConst = null;
            foreach ($triggerConstKeys as $key => $value) {
                if ($value === $trigger) {
                    $triggerConst = $key;
                }
            }

            if (null === $triggerConst) {
                throw new LogicException('Nie odnaleziono powodu anulacji dla tego zdarzenia.');
            }

            return $powodyAnulowaniaWnioskuConstants->getConstant($triggerConst . PowodAnulowaniaWnioskuConstants::SUFFIX);
        };

        $applicationCancellationReason = null;
        if (in_array(AdUserConstants::WYLACZONE, $changedElements)
            && TakNieInterface::TAK === $formData[AdUserConstants::WYLACZONE]) {
            if (!isset($changedElements['DISABLE'])) {
                throw new LogicException('Nie określono powodu wyłączenia.');
            }
            $applicationCancellationReason = $specifyReason($changedElements['DISABLE']);
        }

        if (1 < count($changedElements) && null === $applicationCancellationReason) {
            $applicationCancellationReason = PowodAnulowaniaWnioskuConstants::DEFAULT_TITLE;
        }

        if (null === $applicationCancellationReason) {
            $applicationCancellationReason = $specifyReason(current($changedElements));
        }

        return $applicationCancellationReason;
    }

    /**
     * Porównuje pola z formularza do aktualnych danych z AD.
     * Określa co się zmieniło.
     *
     * @param array $formData
     * @param AdUserHelper $adUserHelper
     *
     * @todo powinno być użyte ChangeCompareService -> compareByArray
     *      tylko, że
     *
     * @return array
     */
    public function compareData(array $formData, AdUserHelper $adUserHelper): array
    {
        $changes = [];
        if ($formData[AdUserConstants::LOGIN] !== $adUserHelper::getLoginUzytkownika()) {
            $changes[] = AdUserConstants::LOGIN;
        }

        if ($formData[AdUserConstants::IMIE_NAZWISKO] !== $adUserHelper::getImieNazwisko()) {
            $changes[] = AdUserConstants::IMIE_NAZWISKO;
        }

        if ($formData[AdUserConstants::STANOWISKO] !== $adUserHelper::getStanowisko(true)) {
            $changes[] = AdUserConstants::STANOWISKO;
        }

        if ($formData[AdUserConstants::WYGASA] !== $adUserHelper::getKiedyWygasa()) {
            if (null === $formData[AdUserConstants::WYGASA]) {
                if (0 !== $adUserHelper::getKiedyWygasa()) {
                    $changes [] = AdUserConstants::WYGASA;
                }
            }
            if (null !== $formData[AdUserConstants::WYGASA]) {
                $ldapTime = LdapTimeHelper::unixToLdap($formData[AdUserConstants::WYGASA]->getTimestamp());
                if ($ldapTime !== LdapTimeHelper::unixToLdap($adUserHelper::getKiedyWygasa(true))) {
                    $changes [] = AdUserConstants::WYGASA;
                }
            }
        }

        if ($formData[AdUserConstants::DEPARTAMENT_NAZWA] !== $adUserHelper::getDepartamentNazwa(false, true)) {
            $changes[] = AdUserConstants::DEPARTAMENT_NAZWA;
        }

        if ($formData[AdUserConstants::SEKCJA_NAZWA] !== $adUserHelper::getSekcja(false, true)) {
            $changes[] = AdUserConstants::SEKCJA_NAZWA;
        }

        if ($formData[AdUserConstants::WYLACZONE] !== $adUserHelper::getCzyWylaczone()) {
            $changes[] = AdUserConstants::WYLACZONE;
            $allowedReasons = [
                AdUserConstants::WYLACZENIE_KONTA_NIEOBECNOSC,
                AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY
            ];
            if (!empty($formData[AdUserConstants::WYLACZONE]) &&
                !in_array($formData[AdUserConstants::POWOD_WYLACZENIA], $allowedReasons)) {
                if ($formData[AdUserConstants::POWOD_WYLACZENIA] !== TakNieInterface::NIE) {
                    throw new LogicException('Konto wyłączane bez wybrania powodu lub nieobsługiwany powód.');
                }
            }

            switch ($formData[AdUserConstants::POWOD_WYLACZENIA]) {
                case AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY:
                    $changes['DISABLE'] = AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY;
                    break;
                case AdUserConstants::WYLACZENIE_KONTA_NIEOBECNOSC:
                    $changes['DISABLE'] = AdUserConstants::WYLACZENIE_KONTA_NIEOBECNOSC;
                    break;
            }
        }

        if ($formData[AdUserConstants::PRZELOZONY] !== $adUserHelper::getPrzelozony()) {
            $changes[] = AdUserConstants::PRZELOZONY;
        }

        return $changes;
    }

    /**
     * Usuwa klucze w tablicy które nie pokrywają się z tablicą pobraną z AD.
     *
     * @param array $elements
     *
     * @return array $elements
     */
    private function removeNonAdElements(array $elements): array
    {
        $adArrayKeys = (new ReflectionClass(AdUserConstants::class))
            ->getConstants();

        $removeKeys = array_keys(array_diff_key($elements, array_flip($adArrayKeys)));

        foreach ($elements as $key => $value) {
            if (in_array($key, $removeKeys)) {
                unset($elements[$key]);
            }
        }

        return $elements;
    }

    /**
     * Czy są jakieś błędy.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return 0 < count($this->errors);
    }

    /**
     * Zwraca błędy jako string.
     *
     * @return string
     */
    public function getErrorsAsString(): string
    {
        $errorStringParts = [];
        foreach ($this->errors as $error) {
            $errorStringParts[] = $error['message'];
        }

        return implode(',', $errorStringParts);
    }
}
