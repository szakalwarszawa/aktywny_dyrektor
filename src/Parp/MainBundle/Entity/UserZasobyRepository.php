<?php

/**
 * Description of UserZasobyRepository
 *
 * @author tomasz_bonczak
 */

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UserZasobyRepository extends EntityRepository
{

    public function findByAccountnameAndResource($samaccountname, $zasob)
    {
        $query = $this->createQueryBuilder('uz')
                ->where('uz.samaccountname = :samaccountname')
                ->andWhere('uz.zasobId = :zasobId')
                ->setParameters(array('samaccountname' => $samaccountname, 'zasobId' => $zasob))
                ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function findNameByAccountname($samaccountname)
    {

        $query = $this->getEntityManager()->createQuery('SELECT uz.samaccountname,z.nazwa, z.opis FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND uz.samaccountname = :samaccountname
              ORDER BY z.nazwa ASC
              ')->setParameter('samaccountname', $samaccountname);
               
        
        return $query->getResult();
    }

}
