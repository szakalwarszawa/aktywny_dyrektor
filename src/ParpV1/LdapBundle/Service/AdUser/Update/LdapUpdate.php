<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update;

use Adldap\Models\User;
use ParpV1\LdapBundle\Service\LdapFetch;
use ParpV1\LdapBundle\Service\AdUser\ChangeCompareService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Adldap\Models\Group;
use ParpV1\LdapBundle\DataCollector\Collector;
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
use ParpV1\LdapBundle\Constants\GroupBy;
use ParpV1\MainBundle\Tool\AdStringTool;

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
     * Klucz po której szuka użytkownika w AD.
     *
     * @var string
     */
    public $searchBy = SearchBy::LOGIN;

    public function __construct(LdapFetch $ldapFetch, ChangeCompareService $changeCompareService)
    {
        $this->ldapFetch = $ldapFetch;
        $this->changeCompareService = $changeCompareService;
        $this->responseMessages = new ArrayCollection();
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

        if (null !== $group) {
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

        if (false !== $group) {
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
            new Messages\InfoMessage(),
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
     * Set LdapFetch
     *
     * @return void
     */
    public function setLdapFetch(LdapFetch $ldapFetch): void
    {
        $this->ldapFetch = $ldapFetch;
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
     * Na podstawie kolekcji obiektów klasy AdUserChange wypycha zmiany do AD.
     *
     * @param ArrayCollection $changes
     * @param AdUser $adUser
     *
     * @return self
     */
    public function pushChangesToAd(ArrayCollection $changes, AdUser $adUser): self
    {
        $simulateProcess = $this->simulateProcess;
        $writableUserObject = $adUser->getUser(AdUser::FULL_USER_OBJECT);
        foreach ($changes as $value) {
            if ($value instanceof AdUserChange) {
                $newValue = $value->getNew();
                if ($newValue instanceof DateTime) {
                    $newValue = LdapTimeHelper::unixToLdap($newValue->getTimestamp());
                }

                if (AdUserConstants::GRUPY_AD === $value->getTarget()) {
                    $this->setGroupsAttribute($newValue, $adUser);

                    continue;
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

                    $newValue = $ldapFetchedUser->getUser()[AdUserConstants::AD_STRING];
                }

                if (!$simulateProcess) {
                    $writableUserObject->setAttribute($value->getTarget(), $newValue);
                }

                $oldValue = function ($value) {
                    if (null === $value) {
                        return 'BRAK';
                    }

                    if (is_bool($value)) {
                        return $value? 'PRAWDA' : 'FAŁSZ';
                    }

                    return $value;
                };
                $messageText = 'Zmiana z: ' . $oldValue($value->getOld()) .
                    ', na: ' . $newValue;

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

        return $this;
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
}
