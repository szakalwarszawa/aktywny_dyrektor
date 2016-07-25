<?php

namespace Parp\MainBundle\Entity;

/**
 * KomentarzRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DaneRekordRepository extends \Doctrine\ORM\EntityRepository
{
    public function findByNotHavingRekordIds($ids){
        
        $query = $this->createQueryBuilder('dr')
          ->where('dr.symbolRekordId NOT IN ('.implode(", ", $ids).')')
          ->getQuery();
        
        
        return $query->getResult();
        
    }
}
