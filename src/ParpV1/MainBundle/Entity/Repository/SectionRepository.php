<?php

namespace ParpV1\MainBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * SectionRepository
 */
class SectionRepository extends EntityRepository
{
    /**
     * Szuka sekcję po nazwie oraz skrócie departamentu.
     *
     * @param string $sectionName
     * @param string $departamentShort
     *
     * @return Section|null
     */
    public function findByNameAndDepartmentShort(string $sectionName, string $departmentShort)
    {
        $queryBuilder = $this->createQueryBuilder('s');

        $queryBuilder
            ->select('s')
            ->where('s.name = :sectionName')
            ->andWhere('s.shortname like :departmentShort')
            ->setParameter('departmentShort', $departmentShort . '.%')
            ->setParameter('sectionName', $sectionName)
        ;

        try {
            $result = $queryBuilder
                ->getQuery()
                ->getSingleResult()
            ;
        } catch (NoResultException $exception) {
            return null;
        }

        return $result;
    }
}
