<?php

namespace ParpV1\MainBundle\Repository;

use ParpV1\MainBundle\Entity\PositionGroups;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method PositionGroups|null find($id, $lockMode = null, $lockVersion = null)
 * @method PositionGroups|null findOneBy(array $criteria, array $orderBy = null)
 * @method PositionGroups[]    findAll()
 * @method PositionGroups[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PositionGroupsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PositionGroups::class);
    }
}
