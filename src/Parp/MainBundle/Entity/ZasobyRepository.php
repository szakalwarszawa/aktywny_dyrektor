<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EntryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ZasobyRepository extends EntityRepository
{

    public function findBySamaccountnames()
    {
    
        $temps = $this->getRepository("ParpMainBundle:UserZasoby")->findAll();
        print_r($temps);
        die();
/*
        $query = $this->createQueryBuilder('e')
                ->join('e.userzasoby', 'uz')
                ->where('uz.samaccountname = :sams')
                ->setParameters(array('sams' => "kjakacki"))
                ->getQuery();

        return $query->getResult();    
*/
    }


    public function findByGrupaAD($grupa)
    {

        $query = $this->createQueryBuilder('z')
                ->where('z.nazwa like :grupa or z.grupyAD like :grupa ')
                ->setParameters(array('grupa' => "%$grupa%"))
                ->getQuery();
        $results = $query->getResult();
        return count($results) == 1 ? $results[0] : null;
    }
}
