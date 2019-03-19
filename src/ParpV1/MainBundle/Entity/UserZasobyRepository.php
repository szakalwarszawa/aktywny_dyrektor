<?php

/**
 * Description of UserZasobyRepository
 *
 * @author tomasz_bonczak
 */

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Intl\Exception\MethodNotImplementedException;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class UserZasobyRepository
 * @package ParpV1\MainBundle\Entity
 */
class UserZasobyRepository extends EntityRepository
{

    /**
     * @param $samaccountname
     * @param $zasob
     * @return mixed
     */
    public function findByAccountnameAndResource($samaccountname, $zasob)
    {
        $query = $this->createQueryBuilder('uz')
                ->where('uz.samaccountname = :samaccountname')
                ->andWhere('uz.zasobId = :zasobId')
                ->setParameters(array('samaccountname' => $samaccountname, 'zasobId' => $zasob))
                ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param $samaccountname
     * @return array
     */
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

    /**
     * @param $samaccountname
     * @return array
     */
    public function findNameByAccountname($samaccountname)
    {

        $query = $this->getEntityManager()->createQuery(
            'SELECT uz.id, uz.samaccountname,z.nazwa, z.opis, z.id as zid, uz.poziomDostepu
              FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND uz.samaccountname = :samaccountname
              ORDER BY z.nazwa ASC
              '
        )->setParameter('samaccountname', $samaccountname);


        return $query->getResult();
    }

    /**
     * @param $samaccountname
     * @return array
     */
    public function findUserZasobyByAccountname($samaccountname)
    {

        $query = $this->getEntityManager()->createQuery('SELECT uz.id, uz.samaccountname,z.nazwa, z.opis, z.id as zid FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND uz.samaccountname = :samaccountname and uz.czyAktywne = 1
              ORDER BY z.nazwa ASC
              ')->setParameter('samaccountname', $samaccountname);
        $a1 = $query->getResult();


        $query = $this->getEntityManager()->createQuery('SELECT uz.id, uz.samaccountname,z.nazwa, z.opis, z.id as zid FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND uz.samaccountname = :samaccountname and uz.czyOdebrane = 1
              ORDER BY z.nazwa ASC
              ')->setParameter('samaccountname', $samaccountname);
        $a2 = $query->getResult();

        return array_merge($a1, $a2);
    }

    /**
     * @param $zasobId
     * @return array
     */
    public function findUsersByZasobId($zasobId)
    {
        global $kernel;

        if ('AppCache' == get_class($kernel)) {
            $kernel = $kernel->getKernel();
        }

        $ldap = $kernel->getContainer()->get('ldap_service');
        /*
        $query = $this->getEntityManager()->createQuery('SELECT uz FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND z.id = :zasobId
              ORDER BY uz.samaccountname ASC
              ')->setParameter('zasobId', $zasobId);


        $res = $query->getResult();
        */
        $res = $this->getEntityManager()
        ->createQueryBuilder()
        ->select('uz, w')
        ->from('ParpMainBundle:UserZasoby', 'uz')
        ->leftJoin('uz.wniosek', 'w')
        ->innerJoin('ParpMainBundle:Zasoby', 'z')
        ->andWhere('z.id = uz.zasobId')
        ->andWhere('(w.id = uz.wniosek or uz.wniosek is null)')
        ->andWhere('z.id = :zasobId')
        ->setParameter('zasobId', $zasobId)
        ->orderBy('uz.samaccountname')
        ->getQuery()
        ->getResult();

        foreach ($res as $uz) {
            //echo $uz->getSamaccountname()."<br>";

            $ADUser = $ldap->getUserFromAD($uz->getSamaccountname());
            //echo "<pre>";print_r($ADUser); echo "</pre>";
            if (count($ADUser) > 0) {
                $uz->setADUser($ADUser[0]);
            } else {
            }
        }
        return $res;
    }

    /**
     * @param $wniosek
     * @return array
     */
    public function findByWniosekWithZasob($wniosek)
    {
        $ktoryWniosekSzukam = $wniosek->getOdebranie() ? "wniosekOdebranie" : "wniosek";
        $query = $this->getEntityManager()->createQuery('SELECT uz FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND uz.'.$ktoryWniosekSzukam.' = :wniosekId
              ORDER BY uz.samaccountname ASC, z.nazwa ASC
              ')->setParameter('wniosekId', $wniosek);

        $res = $query->getResult();
        foreach ($res as $uz) {
            $query2 = $this->getEntityManager()->createQuery('SELECT z FROM  ParpMainBundle:Zasoby z
              WHERE z.id = :zasobId
              ')->setParameter('zasobId', $uz->getZasobId());
              $res2 = $query2->getResult();
            if (count($res2) > 0) {
                $uz->setZasobNazwa($res2[0]->getNazwa());
            }
        }
        //print_r($res);die();
        return $res;
    }

    /**
     * @param string $samaccountname
     *
     * @deprecated Należy stosować ->getZasob()
     *
     * @return array
     */
    public function findAktywneDlaOsoby($samaccountname)
    {
//        throw new MethodNotImplementedException('findAktywneDlaOsoby');
        $query = $this->getEntityManager()->createQuery('
              SELECT uz FROM ParpMainBundle:UserZasoby uz
              JOIN ParpMainBundle:Zasoby z
              WHERE uz.zasobId = z.id
              AND (uz.samaccountname = :samaccountname or 1 = 12) and uz.czyNadane = 1
              ORDER BY z.nazwa ASC
              ')->setParameter('samaccountname', $samaccountname);
        $a2 = $query->getResult();
        return $a2;
    }

    /**
     * Znajduje aktywne zasoby dla podanego użytkownika.
     *
     * @param string $nazwaUzytkownika
     *
     * @return array
     */
    public function findAktywneZasobyDlaUzytkownika($nazwaUzytkownika)
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder
            ->select('u')
            ->where('u.deletedAt is null')
            ->andWhere('w.deletedAt is null')
            ->andWhere('u.wniosek is not null')
            ->andWhere('u.samaccountname = :nazwaUzytkownika')
            ->andWhere('u.powodOdebrania is null')
            ->join('u.wniosek', 'w')
            ->setParameter('nazwaUzytkownika', $nazwaUzytkownika)
        ;

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * Zwraca zasoby użytkownika.
     *
     * @todo zasob powinien być w normalnej obiektowej relacji UserZasoby#zasob -> Zasoby
     *
     * @param string $username
     *
     * @return array
     */
    public function findZasobyUzytkownika(string $username): array
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder
            ->select('u as userZasob, z.nazwa as nazwa_zasobu')
            ->where('u.deletedAt is null')
            ->andWhere('z.deletedAt is null')
            ->andWhere('u.samaccountname = :username')
            ->join('u.wniosek', 'w')
            ->join(Zasoby::class, 'z', Join::WITH, 'z.id = u.zasobId')
            ->setParameter('username', $username)
        ;

        $result = $queryBuilder
            ->getQuery()
            ->getResult()
        ;

        $groupedResources = [];
        foreach ($result as $singleRow) {
            $mergedArray = array_merge([
                    'user_zasob' => $singleRow['userZasob']
                ], [
                    'nazwa_zasobu' => $singleRow['nazwa_zasobu']
                ]);
            if (true === $singleRow['userZasob']->getCzyAktywne()) {
                $groupedResources['aktywne'][] = $mergedArray;
                continue;
            }
            $groupedResources['nieaktywne'][] = $mergedArray;
        }

        return $groupedResources;
    }

    /**
     * @param $samaccountname
     * @param $dataStart
     * @param $dataEnd
     * @return array
     */
    public function findDlaOsoby($samaccountname, $dataStart, $dataEnd)
    {
        $query = $this->getEntityManager()->createQuery('
        SELECT uz FROM ParpMainBundle:UserZasoby uz
              WHERE uz.aktywneOd >= :dataStart and uz.aktywneOd <= :dataEnd
              and uz.samaccountname = :samaccountname
              ORDER BY uz.aktywneOd  ASC
              ')->setParameter('samaccountname', $samaccountname)
            ->setParameter('dataStart', $dataStart)
            ->setParameter('dataEnd', $dataEnd);
        $a2 = $query->getResult();
        return $a2;
    }
}
