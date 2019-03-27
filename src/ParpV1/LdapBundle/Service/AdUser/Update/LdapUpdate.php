<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update;

use Adldap\Models\User;
use ParpV1\LdapBundle\Service\LdapFetch;
use ParpV1\LdapBundle\Service\AdUser\ChangeCompareService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Adldap\Models\Group;
use ParpV1\LdapBundle\MessageCollector\Message\InfoMessage;
use ParpV1\LdapBundle\MessageCollector\MessageCollectorInterface;
use ParpV1\LdapBundle\MessageCollector\Collector;
use ParpV1\LdapBundle\MessageCollector\Message\WarningMessage;
use ParpV1\MainBundle\Constants\AdUserConstants;

/**
 * LdapUpdate
 */
class LdapUpdate implements MessageCollectorInterface
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
     * @var Collector|null
     */
    protected $messageCollector = null;

    public function __construct(LdapFetch $ldapFetch, ChangeCompareService $changeCompareService)
    {
        $this->ldapFetch = $ldapFetch;
        $this->changeCompareService = $changeCompareService;
        if (null === $this->messageCollector) {
            $this->messageCollector = new Collector();
        }
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
                $group->addMember($adUser);

                $message = (new InfoMessage('Dodano do grupy ' . $group->getName()))
                    ->setTarget(AdUserConstants::GRUPY_AD)
                ;
                $this
                    ->messageCollector
                    ->add($message)
                ;

                return true;
            }
        }

            $message = (new WarningMessage('Nie odnaleziono w AD grupy ' . $group))
                ->setTarget(AdUserConstants::GRUPY_AD)
            ;
            $this
                ->messageCollector
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
                $group->removeMember($adUser);

                $message = (new InfoMessage('Usunięto z grupy ' . $group->getName()))
                    ->setTarget(AdUserConstants::GRUPY_AD)
                ;
                $this
                    ->messageCollector
                    ->add($message)
                ;

                return true;
            }
        }

        $message = (new WarningMessage('Nie odnaleziono w AD grupy ' . $group))
            ->setTarget(AdUserConstants::GRUPY_AD)
        ;
        $this
            ->messageCollector
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
     * @see MessageCollectorInterface
     */
    public function setCollector(Collector $collector = null): void
    {
        if (null === $collector) {
            $this->messageCollector = new Collector();
        }
        if ($collector instanceof Collector) {
            $this->messageCollector = $collector;
        }
    }

    /**
     * @see MessageCollectorInterface
     */
    public function getCollector(): Collector
    {
        return $this->messageCollector;
    }

    /**
     * Set LdapFetch
     */
    public function setLdapFetch(LdapFetch $ldapFetch)
    {
        $this->ldapFetch = $ldapFetch;
    }

    /**
     * Set ChangeCompareService
     */
    public function setChangeCompareService(ChangeCompareService $changeCompareService)
    {
        $this->changeCompareService = $changeCompareService;
    }
}
