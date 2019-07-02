<?php

namespace ParpV1\JasperReportsBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PathRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PathRepository extends EntityRepository
{
    /**
     * Zwraca dane do siatki.
     *
     * @return array
     */
    public function findDataToGrid(): array
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->select('p.id, p.url, p.isRepository, p.title')
        ;

        $result = $queryBuilder
            ->getQuery()
            ->getArrayResult()
        ;

        return $result;
    }
}
