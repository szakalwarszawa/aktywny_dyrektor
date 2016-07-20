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

    public function findByAccountnameAndEcm($samaccountname)
    {

        $query = $this->getEntityManager()->createQuery('SELECT uz FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND uz.samaccountname = :samaccountname
              AND uz.importedFromEcm = 1
              ORDER BY z.nazwa ASC
              ')->setParameter('samaccountname', $samaccountname);
               
        
        return $query->getResult();
    }
    public function findNameByAccountname($samaccountname)
    {

        $query = $this->getEntityManager()->createQuery('SELECT uz.id, uz.samaccountname,z.nazwa, z.opis, z.id as zid FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND uz.samaccountname = :samaccountname
              AND uz.czyNadane = true
              ORDER BY z.nazwa ASC
              ')->setParameter('samaccountname', $samaccountname);
               
        
        return $query->getResult();
    }
    public function findUsersByZasobId($zasobId){
        global $kernel;
        
        if ('AppCache' == get_class($kernel)) {
            $kernel = $kernel->getKernel();
        }
        
        $ldap = $kernel->getContainer()->get('ldap_service');
        
        $query = $this->getEntityManager()->createQuery('SELECT uz FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND z.id = :zasobId
              ORDER BY uz.samaccountname ASC
              ')->setParameter('zasobId', $zasobId);
               
        
        $res = $query->getResult();
        
        foreach($res as $uz){
            //echo $uz->getSamaccountname()."<br>";
            
            $ADUser = $ldap->getUserFromAD($uz->getSamaccountname());
            //echo "<pre>";print_r($ADUser); echo "</pre>";
            $uz->setADUser($ADUser[0]);
        }
        return $res;
    }
    public function findByWniosekWithZasob($wniosek){
        $ktoryWniosekSzukam = $wniosek->getOdebranie() ? "wniosekOdebranie" : "wniosek";
        $query = $this->getEntityManager()->createQuery('SELECT uz FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND uz.'.$ktoryWniosekSzukam.' = :wniosekId
              ORDER BY uz.samaccountname ASC
              ')->setParameter('wniosekId', $wniosek);
               
        $res = $query->getResult();
        foreach($res as $uz){
            $query2 = $this->getEntityManager()->createQuery('SELECT z FROM  ParpMainBundle:Zasoby z
              WHERE z.id = :zasobId
              ')->setParameter('zasobId', $uz->getZasobId());
              $res2 = $query2->getResult();
              if(count($res2) > 0){
                  $uz->setZasobNazwa($res2[0]->getNazwa());
              }
        }
        //print_r($res);die();
        return $res;
    }
}
