<?php

/**
 * Description of UprawnieniaRepository
 *
 * @author tomasz_bonczak
 */

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UserUprawnieniaRepository extends EntityRepository
{

    public function findSekcja($samaccountname)
    {
        $query = $this->getEntityManager()->createQuery(
                        'SELECT uu
                 FROM ParpMainBundle:UserUprawnienia uu ,ParpMainBundle:Uprawnienia u
                 WHERE uu.uprawnienie_id = u.id 
                 AND uu.czyAktywne = true
                 AND u.czy_sekcja = true
                 AND uu.samaccountname = :samaccountname
                '
                )->setParameter('samaccountname', $samaccountname);

        return $query->getOneOrNullResult();
    }

    public function findDepartament($samaccountname)
    {
        $query = $this->getEntityManager()->createQuery(
                        'SELECT uu
                 FROM ParpMainBundle:UserUprawnienia uu ,ParpMainBundle:Uprawnienia u
                 WHERE uu.uprawnienie_id = u.id 
                 AND uu.czyAktywne = true
                 AND u.czy_edycja = true
                 AND uu.samaccountname = :samaccountname
                '
                )->setParameter('samaccountname', $samaccountname);

        return $query->getResult();
    }

}
