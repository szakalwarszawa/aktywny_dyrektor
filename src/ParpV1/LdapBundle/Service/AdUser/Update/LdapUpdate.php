<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update;

use Adldap\Models\User;
use ParpV1\LdapBundle\Service\LdapFetch;
use ParpV1\LdapBundle\Service\AdUser\ChangeCompareService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Adldap\Models\Group;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\LdapBundle\Constants\SearchBy;
use Doctrine\Common\Collections\ArrayCollection;
use ParpV1\LdapBundle\AdUser\AdUser;
use ParpV1\LdapBundle\DataCollection\Change\Changes\AdUserChange;
use ParpV1\LdapBundle\DataCollection\Message\Messages;
use Symfony\Component\VarDumper\VarDumper;
use DateTime;
use ParpV1\LdapBundle\Helper\LdapTimeHelper;
use ParpV1\LdapBundle\DataCollection\Message\Message;
use ParpV1\MainBundle\Tool\AdStringTool;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\LdapBundle\Service\LdapCreate;
use Adldap\Models\Attributes\AccountControl;

/**
 * LdapUpdate
 */
class LdapUpdate
{
    /**
     * @var string
     */
    const REMOVE_GROUP_SIGN = '-';

    /**
     * @var string
     */
    const ADD_GROUP_SIGN = '+';

    /**
     * @var ChangeCompareService
     */
    protected $changeCompareService;

    /**
     * @var LdapFetch
     */
    protected $ldapFetch;

    /**
     * @var LdapFetch
     */
    protected $ldapCreate;

    /**
     * @var ArrayCollection
     */
    protected $responseMessages;

    /**
     * Czy to będzie tylko symulacja wprowdzenia zmian.
     *
     * @param bool
     */
    protected $simulateProcess = false;

    /**
     * Czy przed zmianami grupy użytkownika mają zostać wyzerowane.
     */
    protected $eraseUserGroups = false;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Klucz po której szuka użytkownika w AD.
     *
     * @var string
     */
    public $searchBy = SearchBy::LOGIN;

    public function __construct(
        LdapFetch $ldapFetch,
        ChangeCompareService $changeCompareService,
        EntityManager $entityManager
    ) {
        $this->ldapFetch = $ldapFetch;
        $this->changeCompareService = $changeCompareService;
        $this->responseMessages = new ArrayCollection();
        $this->entityManager = $entityManager;
    }

    /**
     * Dodaje użytkownika do grupy
     *
     * @param AdUser $adUser
     * @param Group|string $group - obiekt Group lub nazwa grupy
     *
     * @return bool - akcja powiodła się
     */
    private function groupAdd(AdUser $adUser, $group): bool
    {
        $simulateProcess = $this->simulateProcess;
        $writableAdUser = $adUser->getUser(AdUser::FULL_USER_OBJECT);
        $groupCopy = $group;
        if (!$group instanceof Group) {
            if (self::ADD_GROUP_SIGN === substr($group, 0, 1)) {
                $group = ltrim($group, self::ADD_GROUP_SIGN);
            }

            $group = $this
                ->ldapFetch
                ->fetchGroup($group, false)
            ;
        }

        if ($group) {
            if (!$writableAdUser->inGroup($group)) {
                if (!$simulateProcess) {
                    $group->addMember($writableAdUser);
                }
                $this->addMessage(
                    new Messages\SuccessMessage(),
                    'Dodano do grupy - ' . $group->getName(),
                    AdUserConstants::GRUPY_AD,
                    $adUser->getUser()
                );

                return true;
            }

            $this->addMessage(
                new Messages\InfoMessage(),
                'Użytkownik jest już w grupie - ' . $group->getName(),
                AdUserConstants::GRUPY_AD,
                $adUser->getUser()
            );

            return true;
        }

        $this->addMessage(
            new Messages\WarningMessage(),
            '[Dodaj] Nie odnaleziono w AD grupy - ' . $groupCopy,
            AdUserConstants::GRUPY_AD,
            $adUser->getUser()
        );

        return false;
    }

    /**
     * Usuwa użytkownika z grupy
     *
     * @param AdUser $adUser
     * @param Group|string $group - obiekt Group lub nazwa grupy
     *
     * @return bool - akcja powiodła się
     */
    private function groupRemove(AdUser $adUser, $group): bool
    {
        $simulateProcess = $this->simulateProcess;
        $writableAdUser = $adUser->getUser(AdUser::FULL_USER_OBJECT);
        $groupCopy = $group;
        if (!$group instanceof Group) {
            if (self::REMOVE_GROUP_SIGN === substr($group, 0, 1)) {
                $group = ltrim($group, self::REMOVE_GROUP_SIGN);
            }

            $group = $this
                ->ldapFetch
                ->fetchGroup($group, false)
            ;
        }

        if (false !== $group && null !== $group) {
            if ($writableAdUser->inGroup($group)) {
                if (!$simulateProcess) {
                    $group->removeMember($writableAdUser);
                }

                $this->addMessage(
                    new Messages\SuccessMessage(),
                    'Usunięto z grupy - ' . $group->getName(),
                    AdUserConstants::GRUPY_AD,
                    $adUser->getUser()
                );

                return true;
            }

            $this->addMessage(
                new Messages\InfoMessage(),
                'Użytkownik nie był w grupie ' . $group->getName(),
                AdUserConstants::GRUPY_AD,
                $adUser->getUser()
            );

            return true;
        }

        $this->addMessage(
            new Messages\WarningMessage(),
            '[Usuń] Nie odnaleziono w AD grupy - ' . $groupCopy,
            AdUserConstants::GRUPY_AD,
            $adUser->getUser()
        );

        return false;
    }

    /**
     * Dodaje wiadomoość na podstawie typu.
     * Oszczędza parę linijek kodu.
     *
     * @param Message $message
     * @param string $text
     * @param string $target
     * @param mixed $vars
     *
     * @return void
     */
    private function addMessage(Message $message, string $text, string $target, $vars = null): void
    {
        $message
            ->setTarget($target)
            ->setMessage($text)
            ->setVars($vars)
        ;

        $this
            ->responseMessages
            ->add($message)
        ;
    }

    /**
     * Grupy potrzebują specjalnego traktowania dlatego jest na
     * to przewidziana osobna metoda. Jezeli dana wchodząca jest typu '-GRUPA,+GRUPA'
     * należy to rozbić i odpowiednio obsłużyć. Metoda dodaje lub/i usuwa grupy użytkownika.
     *
     * @param array|string $groupsAd
     * @param AdUser $adUser
     *
     * @return void
     */
    public function setGroupsAttribute($groupsAd, AdUser $adUser): void
    {
        if (is_array($groupsAd)) {
            (new OptionsResolver())
                ->setRequired(['add', 'remove'])
                ->resolve($groupsAd)
            ;

            foreach ($groupsAd['add'] as $groupAdd) {
                $this->groupAdd($adUser, $groupAdd);
            }

            foreach ($groupsAd['remove'] as $groupRemove) {
                $this->groupRemove($adUser, $groupRemove);
            }
        }

        foreach (explode(',', $groupsAd) as $groupName) {
            if (self::REMOVE_GROUP_SIGN === substr($groupName, 0, 1)) {
                $groupName = ltrim($groupName, self::REMOVE_GROUP_SIGN);
                $this->groupRemove($adUser, $groupName);
            }
            if (self::ADD_GROUP_SIGN === substr($groupName, 0, 1)) {
                $groupName = ltrim($groupName, self::ADD_GROUP_SIGN);
                $this->groupAdd($adUser, $groupName);
            }
        }
    }

    /**
     * Na podstawie kolekcji obiektów klasy AdUserChange wypycha zmiany do AD.
     * Kilka niestandardowych operacji:
     *  - Grupy AD - są wypychany w inny sposób niż zmiana atrybutu
     *  - Typ DateTime - trzeba go konwertować na czas z LDAPa
     *  - Przełożony - dana przychodzi w postaci `Nazwisko Imię`, konwertowana jest do pełnego stringa AD
     *  - Wygasa - dane są konwertowane do jednego formatu - czasu LDAPowego (int) i porównywane
     *  - Wylaczone & Powod wylaczenia - są obsługiwane jako jedno i tak muszą trafić do metody
     *      włączajacej lub wyłączającej konto.
     *
     *  $disableEnableAccount (array) - jeżeli tablica zostanie uzupełniona to znaczy, że konto musi zostać wyłączone|włączone
     *  $moveToAnotherOu (bool|object) - jeżeli jest zmiana departamentu to należy przenieśc użytkownika
     *      musi się to dziać na samym końcu!
     *
     * @todo zmiana imienia i nazwiska
     *
     * @param ArrayCollection $changes
     * @param AdUser $adUser
     *
     * @return self
     */
    public function pushChangesToAd(ArrayCollection $changes, AdUser $adUser): self
    {
        $writableUserObject = $adUser->getUser(AdUser::FULL_USER_OBJECT);
        $simulateProcess = $this->simulateProcess;
        if ($this->eraseUserGroups) {
            $this->removeAllUserGroups($adUser);
            $adUser = $this
                ->ldapFetch
                ->refreshAdUser($adUser)
            ;
        }
        $moveToAnotherOu = false;

        foreach ($changes as $value) {
            $parseViewData = false;
            if ($value instanceof AdUserChange && $value->getNew() !== $value->getOld()) {
                $newValue = $value->getNew();

                if (AdUserConstants::DEPARTAMENT_NAZWA === $value->getTarget()) {
                    $moveToAnotherOu = $value;
                }

                if ($newValue instanceof DateTime) {
                    $newValue = LdapTimeHelper::unixToLdap($newValue->getTimestamp());
                }

                if (AdUserConstants::WYLACZONE === $value->getTarget()) {
                    $disableEnableAccount[AdUserConstants::WYLACZONE] = $value->getTarget();

                    continue;
                }

                if (AdUserConstants::POWOD_WYLACZENIA === $value->getTarget()) {
                    $disableEnableAccount[AdUserConstants::POWOD_WYLACZENIA] = $newValue;

                    continue;
                }

                if (AdUserConstants::GRUPY_AD === $value->getTarget()) {
                    $this->setGroupsAttribute($newValue, $adUser);

                    continue;
                }

                if (AdUserConstants::WYGASA === $value->getTarget()) {
                    if ((int) $newValue === (int) $value->getOld()) {
                        continue;
                    }
                    $parseViewData = true;
                    if (null === $newValue) {
                        $newValue = 0;
                    }
                    $writableUserObject->setAccountExpiry($newValue);
                }

                if (AdUserConstants::PRZELOZONY === $value->getTarget()) {
                    $ldapFetchedUser = $this
                        ->ldapFetch
                        ->fetchAdUser(
                            AdStringTool::getValue($newValue, AdStringTool::CN),
                            SearchBy::CN_AD_STRING,
                            false
                        )
                    ;

                    if (null === $ldapFetchedUser) {
                        $message = new Messages\ErrorMessage();
                        $this->addMessage(
                            $message,
                            'Nie udało się odszukać przełożonego o nazwisku ' . $newValue,
                            $value->getTarget(),
                            $adUser->getUser()
                        );

                        return $this;
                    }

                    if ($value->getOld() === $ldapFetchedUser->getUser()[AdUserConstants::AD_STRING]) {
                        continue;
                    }

                    $newValue = $ldapFetchedUser->getUser()[AdUserConstants::AD_STRING];
                }

                if (!$simulateProcess) {
                    $writableUserObject->setAttribute($value->getTarget(), $newValue);
                }

                $oldValue = function ($value) use ($parseViewData) {
                    if ($parseViewData) {
                        if (0 === $value) {
                            return 0;
                        }

                        if (null !== $value) {
                            $date =  (new DateTime())
                                ->setTimestamp(LdapTimeHelper::ldapToUnix($value))
                            ;

                            return $date->format('Y-m-d');
                        }
                    }

                    if (null === $value) {
                        return 'BRAK';
                    }

                    if (is_bool($value)) {
                        return $value? 'PRAWDA' : 'FAŁSZ';
                    }

                    return $value;
                };

                $newValueParsed = function ($value) use ($parseViewData) {
                    if ($parseViewData) {
                        if (0 === $value) {
                            return 0;
                        }

                        if (null !== $value) {
                            $date =  (new DateTime())
                                ->setTimestamp(LdapTimeHelper::ldapToUnix($value))
                            ;

                            return $date->format('Y-m-d');
                        }
                    }

                    return $value;
                };

                $messageText = 'Zmiana z: ' . $oldValue($value->getOld()) .
                    ', na: ' . $newValueParsed($newValue);

                $this->addMessage(
                    new Messages\SuccessMessage(),
                    $messageText,
                    $value->getTarget(),
                    $adUser->getUser()
                );
            }
        }

        if (!$simulateProcess) {
            $writableUserObject->save();
        }

        if (isset($disableEnableAccount[AdUserConstants::WYLACZONE])) {
            $this->disableEnableAccount($disableEnableAccount, $adUser);

            return $this;
        }

        if ($moveToAnotherOu) {
            $this->changeUserDepartment($moveToAnotherOu, $adUser);

            return $this;
        }

        if (!$simulateProcess) {
            $writableUserObject->save();
        }

        return $this;
    }

    /**
     * Zmienia departament użytkownika, mianowicie zmienia atrybut distinguishedname
     * odpowiadający za przeniesienie użytkownika do konkretnego OU.
     *
     * @param AdUserChange $adUserChange - gdzie target == departament
     * @param AdUser $adUser
     *
     * @return AdUser
     */
    public function changeUserDepartment(AdUserChange $adUserChange, AdUser $adUser): AdUser
    {
        $newDepartment = $this
            ->entityManager
            ->getRepository(Departament::class)
            ->findOneBy([
                'name' => $adUserChange->getNew(),
                'nowaStruktura' => 1
            ])
        ;

        if (null === $newDepartment) {
            $this
                ->addMessage(
                    new Messages\ErrorMessage(),
                    'Nie odnaleziono departamentu w słowniku - ' . $adUserChange->getNew(),
                    AdUserConstants::DEPARTAMENT_NAZWA,
                    $adUser->getUser()
                )
            ;
        }

        $newAdString = AdStringTool::replaceValue(
            $adUser->getUser()[AdUserConstants::AD_STRING],
            AdStringTool::OU,
            $newDepartment->getShortname()
        );

        $writableUserObject = $adUser->getUser(AdUser::FULL_USER_OBJECT);
        $simulateProcess = $this->simulateProcess;
        if (!$simulateProcess) {
            $writableUserObject->setDistinguishedName($newAdString);

            $writableUserObject->save();
        }

        return new AdUser($writableUserObject);
    }

    /**
     * Dodaje nowego użytkownika do AD.
     *
     * @param AdUser $adUser
     * @param ArrayCollection $params
     *
     * @return self
     */
    public function pushNewUserToAd(AdUser $adUser, ArrayCollection $params)
    {
        $changes = [];
        foreach ($params as $adUserChange) {
            $changes[$adUserChange->getTarget()] = $adUserChange->getNew();
        }

        $writableUserObject = $adUser->getUser(AdUser::FULL_USER_OBJECT);
        $newDepartment = $this
            ->entityManager
            ->getRepository(Departament::class)
            ->findOneBy([
                'name' => $changes[AdUserConstants::DEPARTAMENT_NAZWA],
                'nowaStruktura' => 1
            ])
        ;

        $baseParameters = $this
            ->ldapFetch
            ->getBaseParameters()
        ;

        $dnBuilder = $writableUserObject
            ->getDnBuilder()
        ;
        $dnBuilder
            ->addOu($newDepartment->getShortname())
            ->addCn($changes[AdUserConstants::CN_AD_STRING])
        ;

        $adOu = explode(',', $baseParameters['base_ou']);
        foreach ($adOu as $value) {
            $dnBuilder->addOu($value);
        }

        $writableUserObject
            ->setDn($dnBuilder)
            ->setAccountName($changes[AdUserConstants::LOGIN])
        ;

        $accountControl = $writableUserObject->getUserAccountControlObject();
        $accountControl->passwordIsNotRequired();
        $writableUserObject->setUserAccountControl($accountControl);

        $writableUserObject->save();

        $this->addMessage(
            new Messages\SuccessMessage(),
            'Utworzono nowego użytkownika',
            'new_user',
            $adUser->getUser()
        );

        return $this;
    }

    /**
     * Wyrzuca użytkownika ze wszystkich jego grup.
     *
     * @param AdUser $adUser
     *
     * @return AdUser
     */
    public function removeAllUserGroups(AdUser $adUser): AdUser
    {
        $simulateProcess = $this->simulateProcess;
        $writableUserObject = $adUser
            ->getUser(AdUser::FULL_USER_OBJECT)
        ;
        $userGroups = $writableUserObject
            ->getGroups()
        ;

        if (!$simulateProcess) {
            foreach ($userGroups as $group) {
                try {
                    $group->removeMember($writableUserObject);
                } catch (ContextErrorException $exception) {
                    continue;
                }
            }

            $writableUserObject->save();
        }

        $this->addMessage(
            new Messages\SuccessMessage(),
            'WYZEROWANO WSZYSTKIE GRUPY',
            AdUserConstants::GRUPY_AD,
            $adUser->getUser()
        );

        return new AdUser($writableUserObject);
    }

    /**
     * Włącza lub wyłącza konto użytkownika.
     *
     * @param array $values
     * @param AdUser $adUser
     *
     * @return void
     */
    private function disableEnableAccount(array $values, AdUser $adUser): void
    {
        $simulateProcess = $this->simulateProcess;
        $optionsResolver = (new OptionsResolver())
            ->setDefault(AdUserConstants::POWOD_WYLACZENIA, null)
            ->setRequired(AdUserConstants::WYLACZONE)
            ->setAllowedTypes(AdUserConstants::WYLACZONE, ['string', 'null'])
            ->setAllowedTypes(AdUserConstants::POWOD_WYLACZENIA, ['string', 'null'])
        ;

        $values = $optionsResolver->resolve($values);

        $writableUserObject = $adUser->getUser(AdUser::FULL_USER_OBJECT);
        $disableAccount = null !== $values[AdUserConstants::POWOD_WYLACZENIA];
        $disableAccountReason = $disableAccount ? ' - ' .$values[AdUserConstants::POWOD_WYLACZENIA] : '';
        $messageText = $disableAccount ? 'Konto wyłączone' : 'Konto włączone';

        $this->addMessage(
            new Messages\SuccessMessage(),
            $messageText . $disableAccountReason,
            $values[AdUserConstants::WYLACZONE],
            $adUser->getUser()
        );

        $userAccountControl = $writableUserObject->getUserAccountControlObject();
        $flagForRemove = null;

        if ($disableAccount) {
            $flagForRemove = AccountControl::NORMAL_ACCOUNT;
            $userAccountControl->accountIsDisabled();
            $writableUserObject->setAttribute('description', $values[AdUserConstants::POWOD_WYLACZENIA]);
        }

        if (!$disableAccount) {
            $flagForRemove = AccountControl::ACCOUNTDISABLE;
            $userAccountControl->accountIsNormal();
        }

        $newFlags = [];
        foreach ($userAccountControl->getValues() as $value) {
            if ($value !== $flagForRemove && !in_array($value, $newFlags)) {
                $newFlags[] = $value;
            }
        }

        $userAccountControl->setValues($newFlags);
        $writableUserObject->setUserAccountControl($userAccountControl);

        if (!$simulateProcess) {
            $writableUserObject->save();
        }
    }

    /**
     * Zwraca kolekcję wiadomości dla użytkownika.
     *
     * @param string|null $groupBy - wiadomości będą pogrupowane według klucza
     *
     * @return ArrayCollection|array
     */
    public function getResponseMessages(string $groupBy = null)
    {
        if (null !== $groupBy) {
            $groupedArray = [];

            foreach ($this->responseMessages as $message) {
                if (null !== $message->getVars()) {
                    $groupedArray[$message->getVars()[$groupBy]][] = $message;
                }
            }

            if (!empty($groupedArray)) {
                return $groupedArray;
            }
        }
        return $this->responseMessages;
    }

    /**
     * Zwraca czy istnieje błąd niepozwalający na poprawne wypchnięcie zmian.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        foreach ($this->responseMessages as $message) {
            if ($message instanceof Messages\ErrorMessage) {
                return true;
            }
        }

        return false;
    }

    /**
     * Przełączenie flagi odpowiadającej za wykonanie tylko symulacji wypchnięcia.
     * Zmiany nie będą wprowadzone do AD.
     * Musi być wywołane przed akcją `update` bo zmiany grup są od razu wypychane
     * bez konieczności użycia `->save()` na użytkowniku!!
     *
     * @return self
     */
    public function doSimulateProcess(): self
    {
        $this->simulateProcess = true;

        return $this;
    }

    /**
     * Przed wszystkimi zmianami zostaną wyzerowane grupy w których jest użytkownik.
     * (Uzytkownik zostanie z nich wyrzucony).
     * MUSI zostać wywołany przed `pushChangesToAd`.
     *
     * @return self
     */
    public function doEraseUserGroups(): self
    {
        $this->eraseUserGroups = true;

        return $this;
    }

    /**
     * Set LdapFetch
     *
     * @return void
     */
    public function setLdapFetch(LdapFetch $ldapFetch): void
    {
        $this->ldapFetch = $ldapFetch;
    }

    /**
     * Set LdapCreate
     *
     * @return void
     */
    public function setLdapCreate(LdapCreate $ldapCreate): void
    {
        $this->ldapCreate = $ldapCreate;
    }

    /**
     * Set ChangeCompareService
     *
     * @return void
     */
    public function setChangeCompareService(ChangeCompareService $changeCompareService): void
    {
        $this->changeCompareService = $changeCompareService;
    }

    /**
     * Inicjalizuje nową kolekcję ArrayCollection
     *
     * @return void
     */
    public function setNewResponseMessagesCollection(): void
    {
        $this->responseMessages = new ArrayCollection();
    }

    /**
     * Set searchBy
     *
     * @param string $searchBy
     *
     * @return self
     */
    public function setSearchBy(string $searchBy): self
    {
        $this->searchBy = $searchBy;

        return $this;
    }

    /**
     * Set entityManager
     *
     * @param EntityManager $entityManager
     *
     * @return void
     */
    public function setEntityManager(EntityManager $entityManager): void
    {
        $this->entityManager = $entityManager;
    }
}
