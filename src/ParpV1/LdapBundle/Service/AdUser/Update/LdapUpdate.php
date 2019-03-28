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
     * @param User $adUser
     * @param Group|string $group - obiekt Group lub nazwa grupy
     *
     * @return bool - akcja powiodła się
     */
    private function groupAdd(User $adUser, $group): bool
    {
        $simulateProcess = $this->simulateProcess;
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
            if (!$adUser->inGroup($group)) {
                if (!$simulateProcess) {
                    $group->addMember($adUser);
                }

                $message = (new Messages\InfoMessage('Dodano do grupy - ' . $group->getName()))
                    ->setTarget(AdUserConstants::GRUPY_AD)
                ;
                $this
                    ->responseMessages
                    ->add($message)
                ;

                return true;
            }
        }

        $message = (new Messages\WarningMessage('[Dodaj] Nie odnaleziono w AD grupy - ' . $groupCopy))
            ->setTarget(AdUserConstants::GRUPY_AD)
        ;
        $this
            ->responseMessages
            ->add($message)
        ;

        return false;
    }

    /**
     * Usuwa użytkownika z grupy
     *
     * @param User $adUser
     * @param Group|string $group - obiekt Group lub nazwa grupy
     *
     * @return bool - akcja powiodła się
     */
    private function groupRemove(User $adUser, $group): bool
    {
        $simulateProcess = $this->simulateProcess;
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
            if ($adUser->inGroup($group)) {
                if (!$simulateProcess) {
                    $group->removeMember($adUser);
                }

                $message = (new Messages\InfoMessage('Usunięto z grupy - ' . $group->getName()))
                    ->setTarget(AdUserConstants::GRUPY_AD)
                ;
                $this
                    ->responseMessages
                    ->add($message)
                ;

                return true;
            }
        }

        $message = (new Messages\WarningMessage('[Usuń] Nie odnaleziono w AD grupy - ' . $groupCopy))
            ->setTarget(AdUserConstants::GRUPY_AD)
        ;
        $this
            ->responseMessages
            ->add($message)
        ;

        return false;
    }

    /**
     * Grupy potrzebują specjalnego traktowania dlatego jest na
     * to przewidziana osobna metoda. Jezeli dana wchodząca jest typu '-GRUPA,+GRUPA'
     * należy to rozbić i odpowiednio obsłużyć. Metoda dodaje lub/i usuwa grupy użytkownika.
     *
     * @param array|string $groupsAd
     * @param User $adUser
     *
     * @return void
     */
    public function setGroupsAttribute($groupsAd, User $adUser): void
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
                    $this->setGroupsAttribute($newValue, $writableUserObject);

                    continue;
                }

                if (AdUserConstants::PRZELOZONY === $value->getTarget()) {
                    $ldapFetchedUser = $this
                        ->ldapFetch
                        ->fetchAdUser($newValue, SearchBy::CN_AD_STRING, false)
                    ;

                    if (null === $ldapFetchedUser) {
                        $messageText = 'Nie udało się odszukać przełożonego o nazwisku ' . $newValue;
                        $message = new Messages\ErrorMessage($messageText, $value->getTarget());
                        $this
                            ->responseMessages
                            ->add($message)
                        ;

                        return $this;
                    }

                    $newValue = $ldapFetchedUser->getUser()[AdUserConstants::AD_STRING];
                }

                if (!$simulateProcess) {
                    $writableUserObject->setAttribute($value->getTarget(), $newValue);
                }
            }

            $messageText = 'Zmiana z: ' . (null !== $value->getOld()? $value->getOld() : 'BRAK') .
                ', na: ' . $value->getNew();
            $message = new Messages\InfoMessage($messageText, $value->getTarget());
            $this
                ->responseMessages
                ->add($message)
            ;
        }

        if (!$simulateProcess) {
            $writableUserObject->save();
        }

        return $this;
    }

    /**
     * Zwraca kolekcję wiadomości dla użytkownika.
     *
     * @return ArrayCollection
     */
    public function getResponseMessages(): ArrayCollection
    {
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
