<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;

/**
 * Repozytorim dla encji WniosekNadanieOdebranieZasobow.
 */
class WniosekNadanieOdebranieZasobowRepository extends EntityRepository
{
    /**
     * Wyszukuje wniosek wg zadanego numeru.
     *
     * @param string $nrWniosku
     *
     * @return WniosekNadanieOdebranieZasobow[]
     */
    public function findOneByNumerWniosku($nrWniosku)
    {
        $queryBuilder = $this
            ->createQueryBuilder('wnoz')
            ->leftJoin('wnoz.wniosek', 'w')
            ->leftJoin('w.wniosekNumer', 'wn')
            ->where('wn.numer = :nrWniosku')
            ->setParameter('nrWniosku', $nrWniosku)
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
