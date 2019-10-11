<?php

namespace ParpV1\MainBundle\Entity;

use DateTime;
use Doctrine\ORM\EntityRepository;

/**
 * EntryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ZastepstwoRepository extends EntityRepository
{
    /**
     * @param $samaccountname
     *
     * @return array
     */
    public function znajdzZastepstwa($samaccountname)
    {
        $qb = $this->_em->createQueryBuilder();
        $now = new Datetime();
        $qb->select('z')
            ->from('ParpMainBundle:Zastepstwo', 'z')
            ->where('z.ktoZastepuje = :samaccountname')
            ->andWhere('z.dataOd <= :now')
            ->andWhere('z.dataDo >= :now')
            ->setParameters(array('samaccountname' => $samaccountname, 'now' => $now));

        return $qb->getQuery()->getResult();
    }

    /**
     * Znajduje osoby, które mają ustawione zastępstwo za wybraną osobę
     *
     * @param string $samaccountname
     *
     * @return array|null
     */
    public function znajdzKtoZastepuje(string $samaccountname): ?array
    {
        $queryBuilder = $this->createQueryBuilder('zk');
        $now = new DateTime();
        $queryBuilder->select('z')
            ->from('ParpMainBundle:Zastepstwo', 'z')
            ->where('z.kogoZastepuje = :samaccountname')
            ->andWhere('z.dataOd <= :now')
            ->andWhere('z.dataDo >= :now')
            ->setParameters([
                'samaccountname' => $samaccountname,
                'now' => $now
            ]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $samaccountname
     *
     * @return array
     */
    public function znajdzKogoZastepuje($samaccountname)
    {
        $res = $this->znajdzZastepstwa($samaccountname);
        $ret = [$samaccountname];
        foreach ($res as $z) {
            $ret[] = $z->getKogoZastepuje();
        }
        return $ret;
    }
}
