<?php

namespace Parp\MainBundle\Entity;

/**
 * HistoriaWersjiRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class HistoriaWersjiRepository extends \Gedmo\Loggable\Entity\Repository\LogEntryRepository
{
    public function getDoPoprawy()
    {
        //wybrac wszystkie historia wersji
        // ktore sa na encji WniosekEditor
        // ktorych id jest wiekszy od
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select(array('h'))
            ->from('Parp\MainBundle\Entity\HistoriaWersji', 'h')
            ->where('h.objectClass = \'Parp\MainBundle\Entity\WniosekEditor\'')
            ->andWhere('h.loggedAt > \'2017-05-17 07:55:00\'')
            ->andWhere('h.loggedAt < \'2017-05-17 12:00:00\'')
            ->orderBy('h.id', 'ASC');

        $query = $qb->getQuery();
        $results = $query->getResult();

        return $results;
    }
}
