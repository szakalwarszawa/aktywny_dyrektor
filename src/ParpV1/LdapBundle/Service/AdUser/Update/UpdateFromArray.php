<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update;

use ParpV1\LdapBundle\Service\AdUser\Update\LdapUpdate;
use ParpV1\MainBundle\Constants\AdUserConstants;
use Doctrine\ORM\EntityManager;
use ParpV1\LdapBundle\Constants\SearchBy;
use ParpV1\LdapBundle\AdUser\AdUser;

/**
 * UpdateFromArray
 */
final class UpdateFromArray extends LdapUpdate
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
     * Aktualizuje obiekt w AD na podstawie tablicy.
     *
     * @param array $updateArray
     *
     * @return self
     */
    public function update(array $updateArray): self
    {
        $userLogin = $updateArray[AdUserConstants::LOGIN];
        $adUser = $this
            ->ldapFetch
            ->fetchAdUser($userLogin, SearchBy::LOGIN, false)
        ;

        if (null === $adUser) {
            throw new \Exception('Nie ma takiego użytkownika w AD');
        }

        $changes = $this
            ->changeCompareService
            ->compareByArray($updateArray, $adUser->getUser())
        ;

        $this->pushChangesToAd($changes, $adUser);

        return $this;
    }
}
