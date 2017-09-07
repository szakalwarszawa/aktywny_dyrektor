<?php

/**
 * Description of UprawnieniaRepository
 *
 * @author tomasz_bonczak
 */

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UprawnieniaRepository extends EntityRepository
{

    public function findEdytowalneDlaGrupy($grupa)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select(array('u', 'g'))
                ->from('ParpV1\MainBundle\Entity\Uprawnienia', 'u')
                ->leftJoin('u.grupy', 'g')
                ->where('g.kod = :kod')
                ->andWhere('u.czy_edycja = true')
                ->setParameter('kod', $grupa);

        $query = $qb->getQuery();
        $results = $query->getResult();
        
        return $results;
    }
}
