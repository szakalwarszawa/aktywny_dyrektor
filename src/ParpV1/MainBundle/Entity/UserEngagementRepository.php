<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserEngagementRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserEngagementRepository extends EntityRepository
{

    public function findOneByCryteria($samaccountname = null, $engagement = null, $month = null, $year = null)
    {

        $query = $this->createQueryBuilder('ue')
                ->where('ue.samaccountname = :samaccountname')
                ->andWhere('ue.month = :month')
                ->andWhere('ue.year = :year')
                ->andWhere('ue.engagement = :engagement')
                ->andWhere('ue.kiedyUsuniety IS NULL')
                ->andWhere('ue.ktoUsunal IS NULL')
                ->setParameters(array('samaccountname' => $samaccountname, 'engagement' => $engagement, 'month' => $month, 'year' => $year))
                ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function findOneNieaktywneByCryteria($samaccountname = null, $engagement = null, $month = null, $year = null)
    {

        $query = $this->createQueryBuilder('ue')
                ->where('ue.samaccountname = :samaccountname')
                ->andWhere('ue.month = :month')
                ->andWhere('ue.year = :year')
                ->andWhere('ue.engagement = :engagement')
                ->andWhere('ue.kiedyUsuniety IS NOT NULL')
                ->andWhere('ue.ktoUsunal IS NOT NULL')
                ->addOrderBy('ue.kiedyUsuniety', 'DESC')
                ->setParameters(array('samaccountname' => $samaccountname, 'engagement' => $engagement, 'month' => $month, 'year' => $year))
                ->setMaxResults(1)
                ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function findBySamaccountnameAndYear($samaccountname = null, $year = null)
    {

        $query = $this->createQueryBuilder('ue')
                ->where('ue.samaccountname = :samaccountname')
                ->andWhere('ue.year = :year')
                ->andWhere('ue.kiedyUsuniety IS NULL')
                ->andWhere('ue.ktoUsunal IS NULL')
                ->addOrderBy('ue.engagement', 'ASC') ->addOrderBy('ue.month', 'ASC')
                ->setParameters(array('samaccountname' => $samaccountname, 'year' => $year))
                ->getQuery();

        return $query->getResult();
    }
}
