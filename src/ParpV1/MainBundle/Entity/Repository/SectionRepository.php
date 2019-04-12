<?php

namespace ParpV1\MainBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use DateTime;
use Doctrine\ORM\NoResultException;

/**
 * SectionRepository
 */
class SectionRepository extends EntityRepository
{
    /**
     * @param int $wniosek
     *
     * @return LsiImportToken|null
     */
    public function getLsiImportTokenByWniosek($idWniosku)
    {
        $queryBuilder = $this->createQueryBuilder('l');

        $queryBuilder
            ->select('l')
            ->leftJoin('l.wniosek', 'w')
            ->where('w.id = :idWniosku')
            ->setParameter('idWniosku', $idWniosku)
        ;

        $result = $queryBuilder
            ->getQuery()
            ->getResult();

        return $result;
    }

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
