<?php

namespace ParpV1\AuthBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use ParpV1\MainBundle\Entity\AclRole;
use ParpV1\AuthBundle\Security\ParpUser;

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
