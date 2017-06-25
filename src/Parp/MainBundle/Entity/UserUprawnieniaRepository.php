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
        $res = $query->getResult();
        return count($res) > 0 ? $res[0] : null; //$query->getOneOrNullResult();
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
    
    public function findUsersByUprawnienieId($uprawnienieId)
    {
        global $kernel;
        
        if ('AppCache' == get_class($kernel)) {
            $kernel = $kernel->getKernel();
        }
        
        $ldap = $kernel->getContainer()->get('ldap_service');
        
        $query = $this->getEntityManager()->createQuery('SELECT uu FROM ParpMainBundle:UserUprawnienia uu
              JOIN ParpMainBundle:Uprawnienia u
              WHERE uu.uprawnienie_id = u.id
              AND u.id = :uprawnienieId
              ORDER BY uu.samaccountname ASC
              ')->setParameter('uprawnienieId', $uprawnienieId);
               
        
        $res = $query->getResult();
        
        foreach ($res as $uz) {
            //echo $uz->getSamaccountname()."<br>";
            
            $ADUser = $ldap->getUserFromAD($uz->getSamaccountname());
            //echo "<pre>";print_r($ADUser); echo "</pre>";
            $uz->setADUser($ADUser[0]);
        }
        return $res;
    }
}
