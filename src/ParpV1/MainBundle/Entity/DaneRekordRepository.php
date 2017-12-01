<?php

namespace ParpV1\MainBundle\Entity;

/**
 * KomentarzRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DaneRekordRepository extends \Doctrine\ORM\EntityRepository
{
    public function findByNotHavingRekordIds($ids)
    {

        $query = $this->createQueryBuilder('dr')
          ->where('dr.symbolRekordId NOT IN ('.implode(", ", $ids).')')
          ->getQuery();


        return $query->getResult();
    }
    public function findNewPeople()
    {

        if ($_SERVER['REMOTE_ADDR'] == "10.10.50.1") {
            $result = $this
               ->createQueryBuilder('e')
               ->select('e')
               ->andWhere("e.newUnproccessed > 0 or e.id = 529")
               ->orderBy("e.newUnproccessed")
               ->getQuery()
               ->getResult(/* \Doctrine\ORM\Query::HYDRATE_ARRAY */);
        } else {
            $result = $this
               ->createQueryBuilder('e')
               ->select('e')
               ->andWhere("e.newUnproccessed > 0")
               ->andWhere("e.newUnproccessed < 7 ")
               ->orderBy("e.newUnproccessed")
               ->getQuery()
               ->getResult(/* \Doctrine\ORM\Query::HYDRATE_ARRAY */);
        }
        return $result;
    }

    public function findChangesInMonth($rok, $miesiac)
    {
        $dataStart = new \Datetime($rok.'-'.$miesiac.'-01');
        if ($miesiac < 12) {
            $dataEnd = new \Datetime($rok.'-'.($miesiac+1).'-01');
        } else {
            $dataEnd = new \Datetime(($rok+1).'-01-01');
        }

        $result = $this
           ->createQueryBuilder('e')
           ->select('e')
           ->andWhere("
            (e.umowaOd >= :dataStart and e.umowaOd < :dataEnd) or
            (e.umowaDo >= :dataStart and e.umowaDo < :dataEnd)")
            ->orderBy("e.nazwisko")
           ->getQuery()
           ->setParameters([
               'dataStart' => $dataStart,
               'dataEnd' => $dataEnd,

           ])
           ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        //var_dump($result);
        return $result;
    }

    public function findChangesInMonthByPole($rok, $miesiac, $pole = 'departament')
    {
        $dataStart = new \Datetime($rok.'-'.$miesiac.'-01');
        if ($miesiac < 12) {
            $dataEnd = new \Datetime($rok.'-'.($miesiac+1).'-01');
        } else {
            $dataEnd = new \Datetime(($rok+1).'-01-01');
        }

        $result = $this
           ->createQueryBuilder('e')
           ->select('e, w.id, w.data, w.version, w.loggedAt')
           ->innerJoin('ParpV1\MainBundle\Entity\HistoriaWersji', 'w')
           ->where("w.objectId = e.id and w.data like '%\"".$pole."\"%' and w.objectClass = 'ParpV1\MainBundle\Entity\DaneRekord'
           and (w.loggedAt >= :dataStart and w.loggedAt < :dataEnd)")
            ->orderBy("e.nazwisko")
           ->getQuery()
           ->setParameters([
               'dataStart' => $dataStart,
               'dataEnd' => $dataEnd,

           ])
           ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        //var_dump($result);
        return $result;
    }
    public function findChangesByPoleForUser($login, $pola)
    {
        $where = [];
        foreach ($pola as $pole) {
            $where[] = "w.data like '%\"".$pole."\"%'";
        }



        $result = $this
            ->createQueryBuilder('e')
            ->select('e, w.id, w.data, w.version, w.loggedAt')
            ->innerJoin('ParpV1\MainBundle\Entity\HistoriaWersji', 'w')
            ->where("w.objectId = e.id and (".implode(' OR ', $where).") and w.objectClass = 'ParpV1\MainBundle\Entity\DaneRekord'
           and e.login like :login")
            ->orderBy("e.nazwisko")
            ->getQuery()
            ->setParameters([
                'login' => $login

            ])
            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        //var_dump($result);
        return $result;
    }
}
