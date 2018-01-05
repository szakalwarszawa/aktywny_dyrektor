<?php

namespace ParpV1\SoapBundle\Entity;

/**
 * ADUserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ADUserRepository extends \Doctrine\ORM\EntityRepository
{
    public function findPrzedOdebraniem($login, $data)
    {
        $data1 = \DateTime::createFromFormat('Y-m-d', $data);
        $data1->setTime(22, 0);
        $data2 = clone $data1;
        $data2->add(new \Dateinterval('P1D'));
        $data2->setTime(22, 0);
        //var_dump($data1, $data2);
        
        $qb = $this->createQueryBuilder("e");
        $qb
            ->andWhere('e.createdAt BETWEEN :from AND :to AND (e.samaccountname = :login or :login = \'\')')
            ->setParameter('from', $data1)
            ->setParameter('to', $data2)
            ->setParameter('login', $login)
        ;
        $result = $qb->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        return $result;
    }
    public function findPrzezDescription($desc)
    {
        $data1 = \DateTime::createFromFormat('Y-m-d', '2017-01-10');
        $data1->setTime(22, 0);
        $data2 = clone $data1;
        $data2->add(new \Dateinterval('P1D'));
        $data2->setTime(22, 0);
        //var_dump($data1, $data2);
        
        $qb = $this->createQueryBuilder("e");
        $qb
            ->andWhere('e.createdAt BETWEEN :from AND :to AND e.description = :desc')
            ->setParameter('from', $data1)
            ->setParameter('to', $data2)
            ->setParameter('desc', $desc)
        ;
        $result = $qb->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        return $result;
    }
    
    
    public function findDlaDnia($login, $data)
    {
        $data1 = \DateTime::createFromFormat('Y-m-d', $data);
        $data1->setTime(22, 0);
        $data2 = clone $data1;
        $data2->add(new \Dateinterval('P1D'));
        $data2->setTime(22, 0);
        //var_dump($data1, $data2);
        
        $qb = $this->createQueryBuilder("e");
        $qb
            ->andWhere('e.createdAt BETWEEN :from AND :to AND (e.samaccountname = :login or :login = \'\')')
            ->setParameter('from', $data1)
            ->setParameter('to', $data2)
            ->setParameter('login', $login)
        ;
        $result = $qb->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        return $result;
    }
}