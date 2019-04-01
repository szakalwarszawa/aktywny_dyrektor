<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update;

use ParpV1\MainBundle\Entity\Entry;
use ParpV1\LdapBundle\Helper\AttributeGetterSetterHelper;
use ParpV1\MainBundle\Constants\AdUserConstants;
use Doctrine\ORM\EntityManager;
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

    public function update(Entry $entry): self
    {
        $userLoginGetter = AttributeGetterSetterHelper::get(AdUserConstants::LOGIN);
        $userLogin = $entry->$userLoginGetter();
        $adUser = $this
            ->ldapFetch
            ->fetchAdUser($userLogin, $this->searchBy, false)
        ;

        if (null === $adUser) {
            throw new \Exception('Nie ma takiego użytkownika w AD');
        }

        $changes = $this
            ->changeCompareService
            ->compareByEntry($entry, $adUser->getUser())
        ;

        $this->pushChangesToAd($changes, $adUser);
        $entry->setIsImplemented(true);

        return $this;
    }
}
