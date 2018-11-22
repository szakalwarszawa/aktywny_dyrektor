<?php

namespace ParpV1\MainBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use DateTime;

/**
 * LsiImportTokenRepository
 */
class LsiImportTokenRepository extends EntityRepository
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
}
