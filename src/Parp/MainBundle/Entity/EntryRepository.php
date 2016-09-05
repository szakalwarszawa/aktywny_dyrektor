<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EntryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EntryRepository extends EntityRepository
{
    public function findNowaSekcjaTYLKOuzywaneWreorganizacji2016($sam){
        $query = $this->createQueryBuilder('e')
                ->where('e.isImplemented = false')
                ->andWhere('e.samaccountname like :sam')
                ->andWhere("(e.info not like '' and e.info is not null)")
                ->setParameters(array('sam' => $sam))
                ->getQuery();
        $ret = $query->getResult();
        return $ret;
    }

    public function findByIsImplementedAndFromWhen($ids = "")
    {
        $where = "1 = 1";
        if($ids != ""){
            $where = 'e.id IN ('.$ids.')';
        }
        $query = $this->createQueryBuilder('e')
                ->where('e.isImplemented = false')
                ->andWhere('e.fromWhen <= :date')
                ->andWhere($where)
                //->andWhere('e.memberOf = \'\' or e.memberOf is null')
                ->addOrderBy('e.id',  'ASC')
                ->setParameters(array('date' => new \DateTime()))
                ->getQuery();

        return $query->getResult();
    }
    
    public function getTempEntriesAsUsers($ldap){
        $rets = array();
        /*
            samaccountname: "aktywny_dyrektor",
name: "Dyrektor Aktywny",
initials: "AD2",
title: "Dyrektor (p.o.)",
info: "fdsfd",
department: "Biuro Informatyki",
description: "BI",
division: "fdsfd",
lastlogon: "1601-01-01 01:00:00",
manager: "CN=Lipiński Marcin,OU=BA,OU=Zespoly,OU=PARP Pracownicy,DC=AD,DC=TEST",
thumbnailphoto: "",
useraccountcontrol: "INTERDOMAIN_TRUST_ACCOUNT,NOT_DELEGATED,",
accountexpires: ""
            */
        $new = $this->findByIsImplementedAndFromWhen();
        foreach($new as $e){
            
            $ADUser = $ldap->getUserFromAD($e->getSamaccountname());
            //print_r($ADUser); 
            if(count($ADUser) == 0){
                $ret = array();
                $ret['id'] = $e->getId(); // aktywny_dyrektor
                $ret['samaccountname'] = $e->getSamaccountname(); // aktywny_dyrektor
                $ret['name'] = $e->getCn(); // Dyrektor Aktywny
                $ret['initials'] = $e->getInitials(); // AD2
                $ret['title'] = $e->getTitle(); // Dyrektor (p.o.)
                $ret['info'] = $e->getInfo(); // fdsfd
                $ret['department'] = $e->getDepartment(); // Biuro Informatyki
                $ret['description'] = "";//$e->getDescription(); // BI
                $ret['division'] = $e->getDivision(); // fdsfd
                $ret['lastlogon'] = "";//$e->getLastlogon(); // 1601-01-01 01:00:00
                $ret['manager'] = $e->getManager(); // CN=Lipiński Marcin,OU=BA,OU=Zespoly,OU=PARP Pracownicy,DC=AD,DC=TEST
                $ret['thumbnailphoto'] = "";
                $ret['useraccountcontrol'] = "";//$e->getUseraccountcontrol(); // INTERDOMAIN_TRUST_ACCOUNT,NOT_DELEGATED,
                
                $ret['accountexpires'] = "";//$e->getAccountexpires(); //""
                $ret['isDisabled'] = $e->getIsDisabled(); // fdsfd
                $ret['disableDescription'] = $e->getDisableDescription(); // fdsfd
                $ret['accountExpires'] = "";
                $ret['email'] = "";
                $ret['cn'] = "";
                $ret['distinguishedname'] = "";
                $ret['memberOf'] = "";
                $ret['roles'] = "";
                $rets[] = $ret;
            }
        }
        return $rets;
    }
    

}
