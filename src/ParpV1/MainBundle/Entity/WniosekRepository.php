<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;
use ParpV1\MainBundle\Entity\Wniosek;

/**
 * Repozytorium dla encji Wniosek.
 */
class WniosekRepository extends EntityRepository
{
    /**
     * Wyszukuje wniosek wg zadanego numeru.
     *
     * @param string $numer
     *
     * @return Wniosek[]
     */
    public function findOneByNumer($numer)
    {
        $queryBuilder = $this
            ->createQueryBuilder('w')
            ->where('w.numer = :numer')
            ->setParameter('numer', $numer)
        ;

        $result = $queryBuilder
            ->getQuery()
            ->setMaxResults(1)
            ->getResult()
        ;
        $result = empty($result) ? null : $result[0];

        return $result;
    }
}
