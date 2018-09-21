<?php

namespace ParpV1\AuthBundle\Services;

use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\AclRole;

/**
 * Klasa UserLoginService
 */
class UserLoginService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Publiczny konstruktor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Zwraca nazwy wszystkich dostępnych ról
     *
     * @return array
     */
    public function getAkdRolesNames()
    {
        $entityManager = $this->entityManager;
        $availableRoles = array();
        $aclRole = $this
            ->entityManager
            ->getRepository(AclRole::class)
            ->findAll();

        foreach ($aclRole as $role) {
            $availableRoles[] = $role->getName();
        }

        return $availableRoles;
    }
}
