<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Services;

use ParpV1\MainBundle\Form\EdycjaUzytkownikaFormType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use LogicException;
use ReflectionClass;
use InvalidArgumentException;
use UnexpectedValueException;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\SoapBundle\Services\LdapService;
use ParpV1\MainBundle\Helper\AdUserHelper;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Constants\TakNieInterface;
use ParpV1\MainBundle\Constants\WyzwalaczeConstants;
use ParpV1\MainBundle\Constants\PowodAnulowaniaWnioskuConstants;
use ParpV1\MainBundle\Entity\Entry;

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
     * Publiczny konstruktor
     *
     * @param Form $form
     */
    public function __construct(LdapService $ldapService, EntityManager $entityManager)
    {
        $this->ldapService = $ldapService;
        $this->entityManager = $entityManager;
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

    public function saveEntry()
    {
        $form = $this->form;

        if (null === $form) {
            throw new LogicException('Nie zdefiniowano formularza.');
        }

        $formData = $form->getData();

        $changedElements = $this->compareDataCreateEntry();
        $reason = $this->specifyCancellationReason($changedElements, $formData);

        $entry = new Entry();
        $entry
            ->setAccountExpires($formData[AdUserConstants::WYGASA])
            ->setDepartment($formData[AdUserConstants::DEPARTAMENT_NAZWA])
            ->setInfo($formData[AdUserConstants::SEKCJA_NAZWA])
            ->setSamaccountname($formData[AdUserConstants::LOGIN])
            ->setIsDisabled($formData[AdUserConstants::WYLACZONE])
            ->setOpis($reason)
            ->setAccountExpires($formData[AdUserConstants::WYGASA])
            ->setManager($formData[AdUserConstants::PRZELOZONY])
            ->setFromWhen($formData['zmianaOd'])
        ;

        $this
            ->entityManager
            ->persist($entry)
        ;
    }

    /**
     * Porównuje zmiany na formularzu edycji użytkownikami z Active Directory.
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
    public function compareDataCreateEntry()
    {
        $form = $this->form;

        if (null === $form) {
            throw new LogicException('Nie zdefiniowano formularza.');
        }

        $formData = $form->getData();
        $formDataFiltered = $this->removeNonAdElements($form->getData());

        if (!isset($formDataFiltered[AdUserConstants::LOGIN])) {
            throw new UnexpectedValueException('Formularz niepoprawny');
        }

        $adUserData = $this
            ->ldapService
            ->getUserFromAD($formDataFiltered[AdUserConstants::LOGIN])
        ;

        $adUserHelper = new AdUserHelper($adUserData, $this->entityManager);

        $changedElements = $this->compareData($formDataFiltered, $adUserHelper);

        return $changedElements;

        if ($changedElements) {
            $applicationCancellationReason = $this->specifyCancellationReason($changedElements, $formData);
          //  $this->addChangeEntry($formDataFiltered[AdUserConstants::LOGIN], $applicationCancellationReason);
        }
    }


    private function addChangeEntry($username, $applicationCancellationReason)
    {

        var_dump($applicationCancellationReason);

        die;
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
                $trigger = AdUserConstants::WYLACZONE;
            if (!isset($changedElements['DISABLE'])) {
                throw new LogicException('Nie określono powodu wyłączenia.');
            }
            $applicationCancellationReason = $specifyReason($changedElements['DISABLE']);
        }

        if (1 < count($changedElements) && null === $applicationCancellationReason) {
            $trigger = AdUserConstants::FORCE_CLEAN;
            $applicationCancellationReason = PowodAnulowaniaWnioskuConstants::DEFAULT_TITLE;
        }

        if (null === $applicationCancellationReason) {
            $trigger = current($changedElements);
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
}
