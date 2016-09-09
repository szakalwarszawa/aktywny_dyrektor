<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Klaster controller.
 *
 * @Route("/import_rekord")
 */
class ImportRekordDaneController extends Controller
{
    
    protected $dataGraniczna = '2016-08-01';//'2011-10-01';//'2016-08-01'; //'2016-07-31'

/*
    protected $container;

    public function setContainer(Symfony\Component\DependencyInjection\ContainerInterface $container = NULL)
    {
        $this->container = $container;
    }
*/

    public function getSqlDoImportu(){
        
        //$dataGraniczna = date("Y-m-d");
        $sql = "SELECT
            p.SYMBOL,
            COUNT(*) as ile,
            p.IMIE as imie, 
            p.NAZWISKO as nazwisko, 
            departament.OPIS as departamentNazwa,
            departament.KOD  departament,
            stanowisko.OPIS stanowisko,
            rodzaj.NAZWA umowa,
            MIN(umowa.DATA_OD) as UMOWAOD,
            MAX(umowa.DATA_DO) as UMOWADO
            
            from P_PRACOWNIK p
            join PV_MP_PRA mpr on mpr.SYMBOL = p.SYMBOL AND 
                (mpr.DATA_DO is NULL OR mpr.DATA_DO >= '".$this->dataGraniczna."')
            join P_MPRACY departament on departament.KOD = mpr.KOD
            JOIN PV_ST_PRA stjoin on stjoin.SYMBOL= p.SYMBOL AND 
                (stjoin.DATA_DO is NULL OR stjoin.DATA_DO >= '".$this->dataGraniczna."')
            join P_STANOWISKO stanowisko on stanowisko.KOD = stjoin.KOD
            join P_UMOWA umowa on umowa.SYMBOL = p.SYMBOL AND 
                (umowa.DATA_DO is NULL OR umowa.DATA_DO >= '".$this->dataGraniczna."')
            join P_RODZUMOWY rodzaj on rodzaj.RODZAJ_UM = umowa.RODZAJ_UM
            
            GROUP BY 
            
            p.SYMBOL,       
            p.IMIE, 
            p.NAZWISKO, 
            departament.OPIS,
            departament.KOD ,
            stanowisko.OPIS,
            rodzaj.NAZWA,
            umowa.DATA_OD,
            umowa.DATA_DO
            
            ORDER BY 
            p.NAZWISKO, p.IMIE
            ";
        return $sql;
    }
    
    public function getSqlDoUzupelnienieDanychOZwolnionych($ids){
        
        //AND (umowa.DATA_DO is NULL OR umowa.DATA_DO > '".$this->dataGraniczna."') AND umowa.DATA_OD <=  '".$this->dataGraniczna."'
        //$dataGraniczna = date("Y-m-d");
        $sql = "SELECT
            p.SYMBOL,
            COUNT(*) as ile,
            p.IMIE as imie, 
            p.NAZWISKO as nazwisko, 
            rodzaj.NAZWA umowa,
            MIN(umowa.DATA_OD) as UMOWAOD,
            MAX(umowa.DATA_DO) as UMOWADO
            
            from P_PRACOWNIK p
            join P_UMOWA umowa on umowa.SYMBOL = p.SYMBOL 
            join P_RODZUMOWY rodzaj on rodzaj.RODZAJ_UM = umowa.RODZAJ_UM
            where p.SYMBOL IN (".implode(", ", $ids).")
            GROUP BY 
            
            p.SYMBOL,       
            p.IMIE, 
            p.NAZWISKO,
            rodzaj.NAZWA,
            umowa.DATA_OD,
            umowa.DATA_DO
            
            ORDER BY 
            p.NAZWISKO, p.IMIE
            ";
        return $sql;
    }
    
    protected function getUserFromAD($ldap, $dr){
         $aduser = $ldap->getUserFromAD($dr->getLogin());
         if(count($aduser) > 0){
             return $aduser;
         }
         $aduser = $ldap->getNieobecnyUserFromAD($dr->getLogin());
         if(count($aduser) > 0){
             return $aduser;
         }
         $fullname = $dr->getNazwisko()." ".$dr->getImie();
         //echo "<br> szuka ".$fullname;
         $aduser = $ldap->getUserFromAD(null, $fullname);
         if(count($aduser) > 0){
             return $aduser;
         }
         $aduser = $ldap->getNieobecnyUserFromAD(null, $fullname);
         if(count($aduser) > 0){
             return $aduser;
         }
         $fullname = $dr->getNazwisko()." (*) ".$dr->getImie();
         //echo "<br> szuka ".$fullname;
         $aduser = $ldap->getUserFromAD(null, $fullname);
         if(count($aduser) > 0){
             return $aduser;
         }
         $fullname = $dr->getNazwisko()." (*) ".$dr->getImie();
         //echo "<br> szuka ".$fullname;
         $aduser = $ldap->getNieobecnyUserFromAD(null, $fullname);
         if(count($aduser) > 0){
             return $aduser;
         }
         return [];
         
    }
    
    
    /**
     * Lists all Klaster entities.
     *
     * @Route("/importfirebird", name="importfirebird", defaults={})
     * @Method("GET")
     */
    public function importfirebirdWrzucDoBazyAction()
    {
        $saNowi = false;
        $errors = [];
        
        $this->dataGraniczna = date("Y-m-d");
        //$this->dataGraniczna = "2016-09-01";//date("Y-m-d");
        
        
        //$this->dataGraniczna = "2016-08-20";//temp
        $mapowanieDepartamentowPrezesow = [
            '15' => '400', //Prezes - stary uklad !!!! moje oznaczenie 400 , musze dogadac z kadrami !!!
            '216' => '400', //WicePrezes - stary uklad, 3 szt.
            '326' => '416', //Biuro Prezesa - stary uklad, 6 szt.
            '215' => '400',
            '522' => '523',
            '9999' => '400'
        ];
        $pomijajDaneRekord = ['2942'/*krakowiak*/, '3753'/*pocztowska*/, '3126' /*Sługocka-morawska*/, '3798' /*Stasińska*/];
        
        
        $sciecha = "";
        $sql = $this->getSqlDoImportu();
        $rows = $this->executeQuery($sql);
        $em = $this->getDoctrine()->getManager();
        $data = array();
        $imported = array();
        //var_dump($rows); //die();
        foreach($rows as $row){
            if(isset($mapowanieDepartamentowPrezesow[trim($row['DEPARTAMENT'])])){
                //podmieniamy id biur prezesow
                //die('podmieniamy id biur prezesow');
                $row['DEPARTAMENT'] = $mapowanieDepartamentowPrezesow[trim($row['DEPARTAMENT'])];
            }
            $in = $this->parseValue($row['IMIE'])." ".$this->parseValue($row['NAZWISKO']);            
            $data[$in][] = $row;
        }
        //die();
        $totalmsg = [];
        //echo "<pre>"; print_r($data); die();
        $ldap = $this->get('ldap_service');
        
        $rekordIds = [];
/*
        $data["KAMIL JAKACKI"][] = [
            'SYMBOL' => '3834',
            'STANOWISKO' => 'starszy specjalista',
            'DEPARTAMENT' => '521',
            'IMIE' => 'KAMIL',
            'NAZWISKO' => 'JAKACKI',
            'UMOWA' => 'Na czas nieokreślony',
            'UMOWAOD' => '2016-03-01 00:00:00',
            'UMOWADO' => NULL,
        ];
*/
        //temp by sprawdzic czy utworzy dubla mnie
        $data["KAMIL JAKACKI1"][] = [
            'SYMBOL' => '777774',
            'STANOWISKO' => 'starszy specjalista',
            'DEPARTAMENT' => '504',
            'IMIE' => 'KAMIL',
            'NAZWISKO' => 'JAKACKI',
            'UMOWA' => 'Na czas nieokreślony',
            'UMOWAOD' => '2016-01-01',
            'UMOWADO' => NULL,
        ];
        

        $data["ROBERT MUCHACKI"][] = [
            'SYMBOL' => '777774',
            'STANOWISKO' => 'starszy specjalista',
            'DEPARTAMENT' => '504',
            'IMIE' => 'ROBERT',
            'NAZWISKO' => 'MUCHACKI',
            'UMOWA' => 'Na czas nieokreślony',
            'UMOWAOD' => '2016-01-01',
            'UMOWADO' => NULL,
        ];

        
        
        foreach($data as $in => $d){
            if(count($d) > 1){
                //mamy dubla szukamy najpozniejszej umowy
                $minDate = null;
                $minDep = null;
                $teSameDaty = true;
                foreach($d as $r){
                    $uod = $r['UMOWAOD'] ? new \Datetime($r['UMOWAOD']) : null;
                    $teSameDaty = $minDate == null ? true : $teSameDaty && ($minDate->format("Y-m-d") == $uod->format("Y-m-d"));
                    if($uod != null && ($minDate == null || $uod < $minDate)){
                        $minDate = $uod;
                        $minDep = $r['DEPARTAMENT'] ;
                        $row = $r;
                    }
                }
                
                $msg = "Są duplikaty umów dla  ".$in." wybrano najwcześniej podpisaną umowę z dnia ".$minDate->format("Y-m-d")." te same daty: " .($teSameDaty ? "tak" : "nie").".";
                $this->addFlash('warning', $msg);
            }else{
                $row = $d[0];
            }
            if($this->parseValue($row['SYMBOL']) == "3834"){
                //jakacki kamil testujemy update pol
                $row['DEPARTAMENT'] = "516";
                $row['STANOWISKO'] = "starszy specjalista";
                //var_dump('mam jakackiego');
            }
            $dr = $em->getRepository('ParpMainBundle:DaneRekord')->findOneBy(array('symbolRekordId' => $this->parseValue($row['SYMBOL'])));
            
            if($dr == null || !in_array($dr->getSymbolRekordId(), $pomijajDaneRekord)){
                $rekordIds[$this->parseValue($row['SYMBOL'])] = $this->parseValue($row['SYMBOL']);
                //temp
                //$dr == null;
                $d = new \Datetime();
                //print_r($dr); die();
                $nowy = false;
                $poprzednieDane = null;
                if($dr === null){
                    $nowy = true;
                    $dr = new \Parp\MainBundle\Entity\DaneRekord();
                    $dr->setCreatedBy($this->getUser()->getUsername());
                    $dr->setCreatedAt($d);
                    $dr->setNewUnproccessed(1);
                    $em->persist($dr);
                    $saNowi = true;
                    $poprzednieDane = clone $dr;
                }else{
                    $poprzednieDane = clone $dr;
                }
                
                //print_r($row['SYMBOL']);
                //print_r($dr->getNazwisko());
                //print_r($poprzednieDane->getNazwisko());
                
                $dr->setImie($this->parseValue($row['IMIE']));
                $dr->setNazwisko($this->parseNazwiskoValue($row['NAZWISKO']));
                if($nowy){
                    
                    $login = $this->get('samaccountname_generator')->generateSamaccountname($dr->getImie(), $dr->getNazwisko());
                    $dr->setLogin($login);
                }
                
                $dr->setDepartament($this->parseValue($row['DEPARTAMENT']));
                $dr->setStanowisko($this->parseValue($row['STANOWISKO'], false));
                $dr->setUmowa($this->parseValue($row['UMOWA'], false));
                $dr->setSymbolRekordId($this->parseValue($row['SYMBOL'], false));
                
                $d1 = $row['UMOWAOD'] ? new \Datetime($row['UMOWAOD']) : null;
                if(
                    ($d1 !== null) && 
                    (
                        ($dr->getUmowaOd() == null && $d1 != null) || 
                        ($dr->getUmowaOd() != null && $d1 == null) || 
                        ($dr->getUmowaOd()->format("Y-m-d") != $d1->format("Y-m-d"))
                    )
                ){
                    $dr->setUmowaOd($d1);  
                }
                $d2 = $row['UMOWADO'] ? new \Datetime($row['UMOWADO']) : null;
                
                //var_dump($dr->getUmowaDo());
                //var_dump($d2);
                if(
                ($d2 != null) && (
                ($dr->getUmowaDo() == null && $d2 != null) || 
                ($dr->getUmowaDo() != null && $d2 == null) || 
                ($dr->getUmowaDo()->format("Y-m-d") != $d2->format("Y-m-d")))){
                    $dr->setUmowaDo($d2);    
                }
                
                
                $dr->setLastModifiedAt($d);
                
                $uow = $em->getUnitOfWork();
                $uow->computeChangeSets();
                if (1 == 1 && ($uow->isEntityScheduled($dr) || $nowy)) {
                    $changeSet = $uow->getEntityChangeSet($dr);
                    unset($changeSet['lastModifiedAt']);
                    if($nowy){
                        
                    }else{
                        
                    }
                    if(count($changeSet) > 0){
                        $totalmsg[] = "\n".($nowy ? "Utworzono dane" : "Uzupełniono dane ")." dla  ".$dr->getLogin()." .";
                    }
                    
                    
                    if((isset($changeSet['departament']) || isset($changeSet['stanowisko'])) && !$nowy){
                        //die("mam zmiane stanowiska lub depu dla istniejacego");
                        $dr->setNewUnproccessed(2);
                    }elseif(count($changeSet) > 0 && !$nowy){
                        $this->utworzEntry($em, $dr, $changeSet, $nowy, $poprzednieDane);
                        $imported[] = $dr;
                    }
                }
            }
            
        }
        if(count($totalmsg) > 0)
            $this->addFlash('warning', implode("<br>", $totalmsg));
        //echo "<pre>"; print_r($imported); 
        //die();
        
        
        //teraz powinien sprawdzac czy ktos mi nie zniknal
        $drs = $em->getRepository('ParpMainBundle:DaneRekord')->findByNotHavingRekordIds($rekordIds);
        if(count($drs) > 0){
            $ids = [];
            foreach($drs as $d){
                $ids[$d->getSymbolRekordId()] = $d->getSymbolRekordId();
            }
            $sql = $this->getSqlDoUzupelnienieDanychOZwolnionych($ids);
            
            $rowsZwolnieni = $this->executeQuery($sql);
            //echo "<pre>"; \Doctrine\Common\Util\Debug::dump($ids);echo "</pre>";
            //echo "<pre>"; \Doctrine\Common\Util\Debug::dump($drs);echo "</pre>";
            //echo "<pre>"; \Doctrine\Common\Util\Debug::dump($rowsZwolnieni);echo "</pre>";
            //die("mam zwolnienie pracownika!!!");
        }
        $em->flush(); 
        
        return $this->render('ParpMainBundle:DaneRekord:showData.html.twig', ['data' => $errors, 'msg' => implode("<br>", $totalmsg), 'saNowi' => $saNowi]);
        //return $this->redirect($this->generateUrl('danerekord'));
    }
    protected function utworzEntry($em, $dr, $changeSet, $nowy, $poprzednieDane ){
        $ldap = $this->get('ldap_service');
        //echo "<pre>"; print_r($changeSet); //die();
        $entry = new \Parp\MainBundle\Entity\Entry($this->getUser()->getUsername());
        $em->persist($entry);
        $entry->setDaneRekord($dr);
        $dr->addEntry($entry);
        $entry->setSamAccountName($dr->getLogin());
        
        
        
        
        if($nowy){
            $entry->setCn($this->get('samaccountname_generator')->generateFullname($dr->getImie(), $dr->getNazwisko()));                                
        }
        
        if((isset($changeSet['imie']) || isset($changeSet['nazwisko'])) && !$nowy){
            //zmiana imienia i nazwiska
            $entry->setCn($this->get('samaccountname_generator')->generateFullname($dr->getImie(), $dr->getNazwisko(), $poprzednieDane->getImie(), $poprzednieDane->getNazwisko()));                                
        }
        if($nowy || $dr->getUmowaDo())
            $entry->setAccountExpires($dr->getUmowaDo());
            $department = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findOneByNameInRekord($dr->getDepartament());
        
        if($department == null){
            echo('nie mam departamentu "'.$dr->getDepartament().'" dla '.$entry->getCn());
            }else{
            
            if($nowy || isset($changeSet['departament'])){
                $entry->setDepartment($department->getName());
                $entry->setGrupyAD($department);
            }
        
            //CN=Slawek Chlebowski, OU=BA,OU=Zespoly, OU=PARP Pracownicy, DC=AD,DC=TEST
            $tab = explode(".", $this->container->getParameter('ad_domain'));
            $ou = ($this->container->getParameter('ad_ou'));
            if($nowy){
                $dn = "CN=".$entry->getCn().", OU=" . $department->getShortname() . ",".$ou.", DC=" . $tab[0] . ",DC=" . $tab[1];
            }
            else{
                $aduser = $this->getUserFromAD($ldap, $dr);//$ldap->getUserFromAD($dr->getLogin());
                if(count($aduser) == 0){
                    $errors[]  = ("Nie moge znalezc osoby  !!!: ".$dr->getLogin());
                }else{                                
                    $dn = $aduser[0]['distinguishedname'];
                }
            }
            //var_dump($entry->getCn(),  $dr->getImie(), $dr->getNazwisko(), $dn);
            $entry->setDistinguishedname($dn);
            
        }
        //$entry->setDivision();//TODO:
        if($nowy || isset($changeSet['stanowisko']))
            $entry->setTitle($dr->getStanowisko());
        $entry->setFromWhen(new \Datetime());
        if($nowy){
            $in = mb_substr($dr->getImie(), 0, 1, "UTF-8").mb_substr($dr->getNazwisko(), 0, 1, "UTF-8");
            if($in == "")
                $in = null;
            $entry->setInitials($in);                    
/*
            if($dr->getNazwisko() == "Turlej")
                die(".".$dr->getImie().".");
*/
        }
        $entry->setIsImplemented(0);
        $entry->setInitialRights('');
        $entry->setIsDisabled(0);
        return $entry;
    }
    
    /**
     * Lists all Klaster entities.
     *
     * @Route("/", name="importfirebird_index", defaults={})
     * @Method("GET")
     */
    public function importfirebirdTestIndexAction()
    {
        //die($this->parseNazwiskoValue("JAKACKI-TEST"));
        $sciecha = "";
        
        $this->dataGraniczna = date("Y-m-d");
        $sql = $this->getSqlDoImportu();
        $miesiac = 1;
        $rok = 2012;
        //die($sql);
        //$rows = $this->executeQueryIbase($sql);
        $rows = $this->executeQuery($sql);
        
        
        
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $rows]);
        //echo "<pre>"; print_r($rows);
        //die('testfirebird');
    }

    
    /**
     * Lists all Klaster entities.
     *
     * @Route("/importfirebird_test1", name="importfirebird_test1", defaults={})
     * @Method("GET")
     */
    public function importfirebirdTest1Action()
    {
/*
        $em = $this->getDoctrine()->getManager();
        $dr = $em->getRepository('ParpMainBundle:Plik')->find(5);
            print_r($dr);
            
        $dr->setOpis('6565');
        $uow = $em->getUnitOfWork();
        $uow->computeChangeSets();
        if ($uow->isEntityScheduled($dr)) {
            // My entity has changed
            echo "Zmiana!!!";
        }else{
            echo "brak zamiany!!!";
        }
            
        die();
*/
        $sciecha = "";

        $sql = 'select rdb$relation_name
from rdb$relations
where rdb$view_blr is null 
and (rdb$system_flag is null or rdb$system_flag = 0);';


        $rows = $this->executeQuery($sql);
        echo "<pre>"; print_r($rows);
        die('testfirebird');
    }
    
    protected function parseName($n){
        $cz = explode(" ", $n);
        $ret = [];
        foreach($cz as $c){
            $ret[] = mb_strtoupper(mb_substr($c, 0, 1)).mb_strtolower(mb_substr($c, 1));
        }
        return implode(" " , $ret);
    }
    
    /**
     * Lists all Klaster entities.
     *
     * @Route("/departamenty_popraw", name="departamenty_popraw", defaults={})
     * @Method("GET")
     */
    public function departamenty_poprawAction()
    {
        $sciecha = "";
        
        $sql = "SELECT
        *            from P_MPRACY p
            
            ";

        //$rows = $this->executeQueryIbase($sql);
        $rows = $this->executeQuery($sql);
        
        foreach($rows as $row){
            if($row['KOD'] > 400 && $row['KOD'] < 500){
                $d = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Departament')->findOneByNameInRekord($this->parseValue($row['OPIS'], false));
                if($d){
                    
                    $d->setNameInRekord($this->parseValue($row['KOD']));
                $this->getDoctrine()->getManager()->persist($d);
                }
            }
        }
        $this->getDoctrine()->getManager()->flush();
        echo "<pre>"; print_r($rows);
        die('testfirebird');
    }
    /**
     * Lists all Klaster entities.
     *
     * @Route("/departamenty_import", name="departamenty", defaults={})
     * @Method("GET")
     */
    public function departamentyAction()
    {
        $sciecha = "";
        
        $sql = "SELECT
        *            from P_MPRACY p
            
            ";

        //$rows = $this->executeQueryIbase($sql);
        $rows = $this->executeQuery($sql);
        
        foreach($rows as $row){
            if($row['KOD'] > 500 && $row['KOD'] < 600){
                $dep = new \Parp\MainBundle\Entity\Departament();
                $n = ($this->parseValue($row['OPIS']));
                print_r($n);
                $dep->setName($n);
                $dep->setNameInRekord(($row['KOD']));
                $dep->setShortName(mb_strtoupper($this->parseValue($row['SKROT'])));
                $dep->setNowaStruktura(1);
                $this->getDoctrine()->getManager()->persist($dep);
            }
        }
        //$this->getDoctrine()->getManager()->flush();
        echo "<pre>"; print_r($rows);
        die('testfirebird');
    }    
        
    
    
   
    protected function my_mb_ucfirst($str) {
        $fc = mb_strtoupper(mb_substr($str, 0, 1, "UTF-8"), "UTF-8");
        return $fc.mb_substr($str, 1, mb_strlen($str, "UTF-8"), "UTF-8");
    }
    public function parseValue($v, $fupper = true){
        $v = iconv("WINDOWS-1250", "UTF-8", $v);
        $v = $fupper ? $this->my_mb_ucfirst(mb_strtolower($v, "UTF-8")) : mb_strtolower($v, "UTF-8");
        return trim($v);
    }
    public function parseNazwiskoValue($v, $fupper = true){
        $v = iconv("WINDOWS-1250", "UTF-8", $v);
        $v = $fupper ? $this->my_mb_ucfirst(mb_strtolower($v, "UTF-8")) : mb_strtolower($v, "UTF-8");
        
        $ps = explode("-", $v);
        $ret = [];
        foreach($ps as $p){
            if(trim($p) != ""){
                $ret[] = trim($this->my_mb_ucfirst(mb_strtolower($p, "UTF-8")));
            }
        }
        
        return implode("-", $ret);
    }
    
    public function executeQuery($sql){
        $options = array(
            \PDO::ATTR_PERSISTENT    => true,//can help to improve performance
            \PDO::ATTR_ERRMODE       => \PDO::ERRMODE_EXCEPTION, //throws exceptions whenever a db error occurs
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES uft8'  //>= PHP 5.3.6
        );
        ////srv-rekorddb01.parp.local/bazy/PARP_KP.FDB
        $str_conn = $this->container->getParameter('rekord_db'); //"firebird:dbname=/var/www/parp/PARP_KP.FDB;host=localhost";
        //$str_conn = "firebird:dbname=/bazy/PARP_KP.FDB;host=srv-rekorddb01.parp.local";
        $userdb = 'SYSDBA';
        $passdb = 'masterkey';
        try {
          $conn = new \PDO($str_conn, $userdb, $passdb);//, $options);
          $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
          //echo 'Connected to database';
          
          $statement = $conn->query($sql);
          $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
          //echo "<pre>"; print_r($rows);
          return $rows;
        }
        catch(PDOException $e) {
          echo $e->getMessage();
        }
        
/*
        
        if ($db = \ibase_connect('localhost:/var/www/parp/PARP_KP.FDB', 'SYSDBA',
            'masterkey')) {
            //echo 'Connected to the database.';
            
            $result = \ibase_query($db, $sql); // assume $tr is a transaction

            $count = 0;
            while ($row = ibase_fetch_assoc($result)){
                $count++;
                $rows[] = $row;
            }

            \ibase_close($db);
            //die('a');
            //echo "<pre>";
            return $rows;
            //print_r($this->outputCSV($rows)); die();
        } else {
            echo 'Connection failed.';
        }
*/
    }
    
    protected function executeQueryIbase($sql){
        if ($db = \ibase_connect('localhost:/var/www/parp/PARP_KP.FDB', 'SYSDBA',
            'masterkey')) {
            //echo 'Connected to the database.';
            
            $result = \ibase_query($db, $sql); // assume $tr is a transaction

            $count = 0;
            while ($row = ibase_fetch_assoc($result)){
                $count++;
                $rows[] = $row;
            }

            \ibase_close($db);
            //die('a');
            //echo "<pre>";
            return $rows;
            //print_r($this->outputCSV($rows)); die();
        } else {
            echo 'Connection failed.';
        }
    }
    function outputCSV($data) {
        $outputBuffer = fopen("php://output", 'w');
        foreach($data as $val) {
            fputcsv($outputBuffer, $val, ";");
        }
        fclose($outputBuffer);
    }
    protected function getObjectPropertiesAsArray($obj, $props){
        $ret = [];
        foreach($props as $p){
            $getter = "get".ucfirst($p);
            $ret[$p] = $obj->{$getter}();
        }
        return $ret;
    }
    
    /**
     *
     * @Route("/przejrzyjnowych", name="przejrzyjnowych", defaults={})
     * @Method("GET")
     */
    public function przejrzyjnowychAction()
    {
        
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();
        $nowi = $em->getRepository("ParpMainBundle:DaneRekord")->findNewPeople();
        $data = [];
        foreach($nowi as $dr){
            $users = $this->getUserFromAllAD($dr);
            $departament = $em->getRepository("ParpMainBundle:Departament")->findOneByNameInRekord($dr->getDepartament());
            $d = $this->getObjectPropertiesAsArray($dr, ['id', 'imie', 'nazwisko', 'stanowisko', 'umowa', 'umowaOd', 'umowaDo', 'login', 'newUnproccessed']);
            $d['users'] = $users;
            $d['departament'] = $departament;
            $data[] = $d;
        }
        //print_r($nowi); die();
        
        // Pobieramy listę Sekcji
        $sectionsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Section')->findBy(array(), array('name' => 'asc'));
        $sections = array();
        foreach ($sectionsEntity as $tmp) {
            $dep = $tmp->getDepartament() ? $tmp->getDepartament()->getShortname() : "bez departamentu";
            $sections[$dep][$tmp->getName()] = $tmp->getName();
        }
        
        
        return $this->render('ParpMainBundle:DaneRekord:przejrzyjNowych.html.twig', ['data' => $data, 'przelozeni' => $ldap->getPrzelozeni(), 'sekcje' => $sections]);
        //return ['nowi' => $ret];
    }
    
    protected function getUserFromAllAD($dr){
        $ldap = $this->get('ldap_service');
        $ret = [];
        $aduser = $ldap->getUserFromAD($dr->getLogin(), null, null, "wszyscyWszyscy");
        if(count($aduser) > 0){
            foreach($aduser as $u){
                if(!isset($ret[$u['samaccountname']])){
                    $ret[$u['samaccountname']] = $u;
                }
            }
        }
        $fullname = $dr->getNazwisko()." ".$dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getUserFromAD(null, $fullname, null, "wszyscyWszyscy");
        if(count($aduser) > 0){
            foreach($aduser as $u){
                if(!isset($ret[$u['samaccountname']])){
                    $ret[$u['samaccountname']] = $u;
                }
            }
        }
        $fullname = $dr->getNazwisko()." (*) ".$dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getUserFromAD(null, $fullname, null, "wszyscyWszyscy");
        if(count($aduser) > 0){
            foreach($aduser as $u){
                if(!isset($ret[$u['samaccountname']])){
                    $ret[$u['samaccountname']] = $u;
                }
            }
        }
        return $ret;
         
    }
    
    /**
     *
     * @Route("/przypiszUtworzUzytkownika/{id}/{samaccountname}", name="przypiszUtworzUzytkownika", defaults={})
     * @Method("POST")
     */
    public function przypiszUtworzUzytkownikaAction(Request $request, $id, $samaccountname)
    {
        
        $dane = $this->get('request')->request->all();
        //svar_dump($dane);
        
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();
        $dr = $em->getRepository("ParpMainBundle:DaneRekord")->find($id);
        $poprzednieDane = clone $dr;
        if($dr->getNewUnproccessed() > 0){
            $changeSet = [];
            if($samaccountname != "nowy"){
                if($dr->getNewUnproccessed() == 2){
                    $samaccountname = $dr->getLogin();    
                }
                $dr->setLogin($samaccountname);
                $aduser = $ldap->getUserFromAD($samaccountname, null, null, "wszyscyWszyscy");
                if($aduser[0]['name'] != $dr->getNazwisko()." ".$dr->getImie()){
                    $changeSet['imie'] = 1;
                    $changeSet['nazwisko'] = 1;
                }
                if($aduser[0]['department'] != $dr->getDepartament()){
                    $changeSet['department'] = 1;
                }
                if($aduser[0]['title'] != $dr->getStanowisko()){
                    $changeSet['stanowisko'] = 1;
                }
            }else{
                //nowy user                
                $changeSet = ['imie' => 1, 'nazwisko' => 1, 'departament' => 1, 'stanowisko' => 1];
            }
            $entry = $this->utworzEntry($em, $dr, $changeSet, $samaccountname == "nowy", $poprzednieDane);
            if($dr->getNewUnproccessed() == 2){
                //trzeba odebrac stare
                $department = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findOneByShortname($aduser[0]['department']);
                $section = $em->getRepository('ParpMainBundle:Section')->findOneByName($aduser[0]['division']);
                $grupyNaPodstawieSekcjiOrazStanowiska = $ldap->getGrupyUsera(['title' => $aduser[0]['title']], $department->getShortname(), ($section ? $section->getShortname() : ""));
                $entry->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, "-");                
            }
            $department = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findOneByNameInRekord($dr->getDepartament());
            $section = $em->getRepository('ParpMainBundle:Section')->findOneByName($dane['form']['info']);
            $grupyNaPodstawieSekcjiOrazStanowiska = $ldap->getGrupyUsera(['title' => $dr->getStanowisko()], $department->getShortname(), ($section ? $section->getShortname() : ""));
            $entry->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, "+");
            
            if($dane['form']['accountExpires'] != ""){
                $v = \DateTime::createFromFormat('Y-m-d', $dane['form']['accountExpires']);
                $entry->setAccountExpires($v);
            }
            if($dane['form']['info'] != ""){
                $entry->setInfo($dane['form']['info']);
            }
            //$dr->setNewUnproccessed(0);
            $em->flush();
        }
        die("ok");
    }
} 