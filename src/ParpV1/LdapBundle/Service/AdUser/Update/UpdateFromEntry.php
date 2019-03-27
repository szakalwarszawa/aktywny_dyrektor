<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update;

use ParpV1\MainBundle\Entity\Entry;
use ParpV1\LdapBundle\Helper\AttributeGetterSetterHelper;
use ParpV1\MainBundle\Constants\AdUserConstants;
use Doctrine\ORM\EntityManager;
use ParpV1\LdapBundle\AdUser\AdUser;
use DateTime;
use ParpV1\LdapBundle\Helper\LdapTimeHelper;
use ParpV1\LdapBundle\Constants\SearchBy;
use ParpV1\LdapBundle\MessageCollector\Message\InfoMessage;
use ParpV1\LdapBundle\Service\AdUser\Update\LdapUpdate;
use ParpV1\LdapBundle\MessageCollector\Collector;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Klasa wprowadzająca zmiany w AD na podstawie obiektu Entry.
 */
final class UpdateFromEntry extends LdapUpdate
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Aktualizuje użytkownika w AD na podstawie wpisu Entry.
     *
     * @param Entry $entry
     *
     * @return self
     */
    public function update(Entry $entry): self
    {
        $userLoginGetter = AttributeGetterSetterHelper::get(AdUserConstants::LOGIN);
        $userLogin = $entry->$userLoginGetter();
        $adUser = $this
            ->ldapFetch
            ->fetchAdUser($userLogin, SearchBy::LOGIN, false)
        ;

        if (null === $adUser) {
            throw new \Exception('Nie ma takiego użytkownika w AD');
        }

        $changes = $this
            ->changeCompareService
            ->compareByEntry($entry, $adUser->getUser())
        ;

        $writableUserObject = $adUser->getUser(AdUser::FULL_USER_OBJECT);

        foreach ($changes as $key => $value) {
            $newValue = $value['new'];
            if ($newValue instanceof DateTime) {
                $newValue = LdapTimeHelper::unixToLdap($newValue->getTimestamp());
            }
            if (AdUserConstants::GRUPY_AD === $key) {
                $this->setGroupsAttribute($newValue, $writableUserObject);

                continue;
            }
            $writableUserObject->setAttribute($key, $newValue);

            $message = (new InfoMessage('Zmiana z: ' . (empty($value['old'])? 'BRAK' : $value['old']) . ', na: ' . $newValue))
                ->setTarget($key)
            ;

            $this
                ->messageCollector
                ->add($message)
            ;
        }
        $writableUserObject->save();

        return $this;
    }
}
