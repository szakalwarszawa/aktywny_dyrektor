<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update;

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
use DateTime;
use ParpV1\LdapBundle\Helper\LdapTimeHelper;
use ParpV1\LdapBundle\DataCollection\Message\Message;
use ParpV1\MainBundle\Tool\AdStringTool;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\LdapBundle\Service\LdapCreate;
use ParpV1\LdapBundle\Service\AdUser\Update\Simulation;
use ParpV1\LdapBundle\Service\AdUser\AccountState;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Services\ParpMailerService;
use Adldap\Models\Attributes\DistinguishedName;
use ParpV1\MainBundle\Services\UprawnieniaService;
use ParpV1\MainBundle\Services\StatusWnioskuService;
use ParpV1\LdapBundle\Service\AdUser\Update\Chain\EntryChain;
use ParpV1\LdapBundle\Service\LogChanges;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * LdapUpdate
 */
class LdapUpdate extends Simulation
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
     * Czy przed zmianami grupy użytkownika mają zostać wyzerowane.
     *
     * @var bool
     */
    protected $eraseUserGroups = false;

    /**
     * Czy ma nastąpić odblokowanie konta użytkownika.
     *
     * @var bool
     */
    protected $unblockAccount = false;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ParpMailerService
     */
    private $parpMailerService;

    /**
     * @var UprawnieniaService
     */
    protected $uprawnieniaService;

    /**
     * @var StatusWnioskuService
     */
    protected $statusWnioskuService;

    /**
     * @var EntryChain
     */
    protected $entryChain;

    /**
     * @param LogChanges
     */
    protected $logPushChanges;

    /**
     * @var ParpUser|null
     */
    protected $currentUser = null;

    /**
     * Klucz po której szuka użytkownika w AD.
     *
     * @var string
     */
    public $searchBy = SearchBy::LOGIN;

    public function __construct(
        LdapFetch $ldapFetch,
        ChangeCompareService $changeCompareService,
        EntityManager $entityManager,
        ParpMailerService $parpMailerService
    ) {
        $this->ldapFetch = $ldapFetch;
        $this->changeCompareService = $changeCompareService;
        $this->responseMessages = new ArrayCollection();
        $this->entityManager = $entityManager;
        $this->parpMailerService = $parpMailerService;
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
        $simulateProcess = $this->isSimulation();
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
                    '[Dodaj] Dodano do grupy - ' . $group->getName(),
                    AdUserConstants::GRUPY_AD,
                    $adUser->getUser()
                );

                return true;
            }

            $this->addMessage(
                new Messages\InfoMessage(),
                '[Dodaj] Użytkownik jest już w grupie - ' . $group->getName(),
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
        $simulateProcess = $this->isSimulation();
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
                    '[Usuń] Usunięto z grupy - ' . $group->getName(),
                    AdUserConstants::GRUPY_AD,
                    $adUser->getUser()
                );

                return true;
            }

            $this->addMessage(
                new Messages\InfoMessage(),
                '[Usuń] Użytkownik nie był w grupie ' . $group->getName(),
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
    private function addMessage(Message $message, string $text = '', string $target = '', $vars = null): void
    {
        if (!empty($text)) {
            $message
                ->setMessage($text)
            ;
        }

        if (!empty($target)) {
            $message
                ->setTarget($target)
            ;
        }

        if (null !== $vars) {
            $message
                ->setVars($vars)
            ;
        }

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

        $groupsRemoved = [];
        $groupsAdded = [];
        foreach (explode(',', $groupsAd) as $groupName) {
            if (self::REMOVE_GROUP_SIGN === substr($groupName, 0, 1)) {
                $groupName = ltrim($groupName, self::REMOVE_GROUP_SIGN);
                if (!in_array($groupName, $groupsRemoved)) {
                    $this->groupRemove($adUser, $groupName);
                    $groupsRemoved[] = $groupName;
                }
            }
            if (self::ADD_GROUP_SIGN === substr($groupName, 0, 1)) {
                $groupName = ltrim($groupName, self::ADD_GROUP_SIGN);
                if (!in_array($groupName, $groupsAdded)) {
                    $this->groupAdd($adUser, $groupName);
                    $groupsAdded[] = $groupName;
                }
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
     *  $keepAttributeValue - mimo zmiany wartośc atrybutu zostanie zachowana
     *
     * @param ArrayCollection $changes
     * @param AdUser $adUser
     * @param Entry|null $entry
     *
     * @return self
     */
    public function pushChangesToAd(ArrayCollection $changes, AdUser $adUser, Entry $entry = null): self
    {
        $writableUserObject = $adUser->getUser(AdUser::FULL_USER_OBJECT);
        $baseParameters = $this
            ->ldapFetch
            ->getBaseParameters()
        ;
        $simulateProcess = $this->isSimulation();

        if ($this->eraseUserGroups) {
            $this->removeAllUserGroups($adUser, $entry);
            $adUser = $this
                ->ldapFetch
                ->refreshAdUser($adUser)
            ;
        }

        if (true === $this->unblockAccount) {
            $changeAccountState = new AccountState\Enable(
                $adUser,
                $baseParameters,
                $this->isSimulation()
            );

            if (null === $entry) {
                $this->addMessage(
                    new Messages\ErrorMessage(),
                    'Brak obiektu Entry',
                    AdUserConstants::WYLACZONE,
                    $adUser->getUser()
                );

                return $this;
            }

            $changeAccountState->saveByDistinguishedName($entry->getDistinguishedName());

            foreach ($changeAccountState->getResponseMessages() as $value) {
                $this
                    ->responseMessages
                    ->add($value)
                ;
            }

            $adUser = $this
                ->ldapFetch
                ->refreshAdUser($adUser)
            ;
        }
        $moveToAnotherOu = false;
        $userGroups = $writableUserObject->getGroupNames();
        foreach ($changes as $value) {
            $parseViewData = false;
            $keepAttributeValue = false;
            if ($value instanceof AdUserChange && $value->getNew() !== $value->getOld()) {
                $newValue = $value->getNew();

                if (AdUserConstants::DEPARTAMENT_NAZWA === $value->getTarget()) {
                    $moveToAnotherOu = $value;
                }

                if (AdUserConstants::AD_STRING === $value->getTarget()) {
                    continue;
                }

                if (AdUserConstants::CN_AD_STRING === $value->getTarget()) {
                    $this->renameUser($adUser, $newValue);
                    $adUser = $this
                        ->ldapFetch
                        ->refreshAdUser($adUser)
                    ;

                    continue;
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

                if (!$simulateProcess && !$keepAttributeValue) {
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

                    if (false !== strpos($value, AdStringTool::CN)) {
                        return implode('', [
                            AdStringTool::getValue($value, AdStringTool::CN, true),
                            ' (',
                            current(AdStringTool::getValue($value, AdStringTool::OU)),
                            ')'
                        ]);
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

                    if (false !== strpos($value, AdStringTool::CN)) {
                        return implode('', [
                            AdStringTool::getValue($value, AdStringTool::CN, true),
                            ' (',
                            current(AdStringTool::getValue($value, AdStringTool::OU)),
                            ')'
                        ]);
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
            $changeAccountState = new AccountState\Disable(
                $adUser,
                $baseParameters,
                $this->isSimulation()
            );

            $disableReason = $disableEnableAccount[AdUserConstants::POWOD_WYLACZENIA];
            $changeAccountState->saveByReason($disableReason);

            foreach ($changeAccountState->getResponseMessages() as $value) {
                $this
                    ->responseMessages
                    ->add($value)
                ;
            }


            if ($disableReason === AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY) {
                $this->removeAllUserGroups($adUser, $entry);

                if (!$simulateProcess) {
                    $this->sendMailToIntExtAdmins($adUser, $userGroups);
                }
            }
        }

        if ($moveToAnotherOu) {
            $this->changeUserDepartment($moveToAnotherOu, $adUser);

            $adUser = $this
                ->ldapFetch
                ->refreshAdUser($adUser)
            ;
        }

        if (!$simulateProcess) {
            $writableUserObject->save();
        }

        return $this;
    }

    /**
     * Wysyła mail do GLPI o usuniętych grupach INT/EXT użytkownika.
     *
     * @param array $userGroups
     *
     * @return void
     */
    private function sendMailToIntExtAdmins(AdUser $adUser, array $userGroups)
    {
        $adUser = $adUser->getUser();
        $mailData = [
            'imie_nazwisko' => $adUser[AdUserConstants::IMIE_NAZWISKO],
            'login' => $adUser[AdUserConstants::LOGIN],
            'tytul' => $adUser[AdUserConstants::LOGIN],
            'odbiorcy' => [ParpMailerService::EMAIL_DO_GLPI],
            'usuniete_int' => preg_grep('/^INT/i', $userGroups),
            'usuniete_ext'  => preg_grep('/^EXT/i', $userGroups),
        ];

        $this
            ->parpMailerService
            ->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIEBI, $mailData);
    }

    /**
     * Zmienia nazwę (imię i nazwisko) użytkownika.
     *
     * @param AdUser $adUser
     * @param string $newValue
     *
     * @return bool - prawda jeżeli zmiana się powiodła
     */
    public function renameUser(AdUser $adUser, string $newValue): void
    {
        $oldName = $adUser->getUser()[AdUserConstants::CN_AD_STRING];

        $this
            ->addMessage(
                new Messages\SuccessMessage(),
                'Zmiana danych osobowych z: ' . $oldName . ' na: ' . $newValue,
                AdUserConstants::CN_AD_STRING,
                $adUser->getUser()
            )
        ;

        if (!$this->isSimulation()) {
            $writableUserObject = $adUser->getUser(AdUser::FULL_USER_OBJECT);
            $renameStatus = $writableUserObject->rename(AdStringTool::CN . $newValue, null);
            $writableUserObject->syncOriginal();
            $writableUserObject->save();

            if (!$renameStatus) {
                $this
                    ->addMessage(
                        new Messages\ErrorMessage(),
                        'Nie powiodła się zmiana danych osobowych!',
                        AdUserConstants::CN_AD_STRING,
                        $adUser->getUser()
                    )
                ;
            }
        }
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
    public function changeUserDepartment(AdUserChange $adUserChange, AdUser $adUser)
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

        $writableUserObject = $adUser->getUser(AdUser::FULL_USER_OBJECT);
        if (!$this->isSimulation()) {
            $baseParameters = $this
                ->ldapFetch
                ->getBaseParameters()
            ;

            $newDn = new DistinguishedName();
            $newDn
                ->addOu($newDepartment->getShortname())
            ;

            foreach (explode(',', $baseParameters['base_ou']) as $value) {
                $newDn
                    ->addOu($value)
                ;
            }

            foreach (explode(',', $baseParameters['base_dn']) as $value) {
                $newDn
                    ->addDc($value)
                ;
            }

            if (!$writableUserObject->move($newDn)) {
                $this
                    ->addMessage(
                        new Messages\ErrorMessage(),
                        'Wystąpił problem z przeniesieniem użytkownika do innego departamentu. ' .
                        'Spróbuj jeszcze raz opublikować zmiany.',
                        AdUserConstants::DEPARTAMENT_NAZWA,
                        $adUser->getUser()
                    )
                ;
            }

            $writableUserObject->save();
        }
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

        foreach (explode(',', $baseParameters['base_ou']) as $dnPart) {
            $dnBuilder->addOu($dnPart);
        }

        $writableUserObject
            ->setDn($dnBuilder)
            ->setAccountName($changes[AdUserConstants::LOGIN])
        ;

        if (!$this->isSimulation()) {
            $accountControl = $writableUserObject->getUserAccountControlObject();
            $accountControl->passwordIsNotRequired();
            $writableUserObject->setUserAccountControl($accountControl);

            $writableUserObject->save();
        }

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
     * @param Entry|null $entry - jeżeli jest obiekt, modyfikowany jest parametr memberOf
     *      Wrzucane są usunięte grupy z prefiksem `-`.
     *
     * @return void
     */
    public function removeAllUserGroups(AdUser $adUser, ?Entry $entry = null): void
    {
        $writableUserObject = $adUser
            ->getUser(AdUser::FULL_USER_OBJECT)
        ;
        $userGroups = $writableUserObject
            ->getGroups()
        ;

        $responseMessage = new Messages\SuccessMessage(
            'Wyzerowano wszystkie grupy',
            AdUserConstants::GRUPY_AD,
            $adUser->getUser()
        );

        $removedGroups = [];
        foreach ($userGroups as $group) {
            try {
                if (!$this->isSimulation()) {
                    $group->removeMember($writableUserObject);
                    $removedGroups[] = '-' . $group->getName();
                }

                $responeMessageChild = new Messages\SuccessMessage(
                    'Usunięto z grupy ' . $group->getName()
                );
                $responseMessage
                    ->children
                    ->add($responeMessageChild)
                ;
            } catch (ContextErrorException $exception) {
                continue;
            }
        }

        if (!$this->isSimulation() && $entry && !empty($removedGroups)) {
            $entry->setMemberOf($entry->getMemberOf() . ',' . implode(',', $removedGroups));
            $this->entityManager->persist($entry);
        }

        $this->addMessage($responseMessage);
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
     * Odwraca metodę doEraseUserGroups
     *
     * @return self
     */
    public function keepUserGroups(): self
    {
        $this->eraseUserGroups = false;

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

    /**
     * Set uprawnieniaService
     *
     * @param UprawnieniaService $uprawnieniaService
     *
     * @return void
     */
    public function setUprawnieniaService(UprawnieniaService $uprawnieniaService): void
    {
        $this->uprawnieniaService = $uprawnieniaService;
    }

    /**
     * Set currentUser
     *
     * @param TokenStorage $tokenStorage
     */
    public function setCurrentUser(TokenStorage $tokenStorage): void
    {
        if (null !== $tokenStorage->getToken()) {
            $this->currentUser = $tokenStorage->getToken()->getUser();
        }

        if (null === $tokenStorage->getToken()) {
            $this->currentUser = null;
        }
    }

    /**
     * Set statusWnioskuService
     *
     * @param UprawnieniaService $statusWnioskuService
     *
     * @return void
     */
    public function setStatusWnioskuService(StatusWnioskuService $statusWnioskuService): void
    {
        $this->statusWnioskuService = $statusWnioskuService;
    }

    /**
     * Set entryChain
     *
     * @param EntryChain $entryChain
     *
     * @return void
     */
    public function setEntryChain(EntryChain $entryChain): void
    {
        $this->entryChain = $entryChain;
    }

    /**
     * Set logPushChanges
     *
     * @param LogChanges $logPushChanges
     *
     * @return void
     */
    public function setLogPushChanges(LogChanges $logPushChanges): void
    {
        $this->logPushChanges = $logPushChanges;
    }

    /**
     * Set parpMailerService
     *
     * @param ParpMailerService $parpMailerService
     *
     * @return void
     */
    public function setParpMailerService(ParpMailerService $parpMailerService): void
    {
        $this->parpMailerService = $parpMailerService;
    }

    /**
     * Nastąpi odblokowanie konta. Przeniesienie z OU Zablokowane lub Nieobecni.
     *
     * @return void
     */
    protected function unblockAccount(): void
    {
        $this->unblockAccount = true;
    }

    /**
     * Status konta nie będzie zmieniony.
     *
     * @return void
     */
    protected function keepAccountBlockedUnblocked(): void
    {
        $this->unblockAccount = false;
    }
}
