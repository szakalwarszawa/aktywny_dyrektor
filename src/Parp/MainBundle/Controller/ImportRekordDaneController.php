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
        
    /**
     * Lists all Klaster entities.
     *
     * @Route("/", name="importfirebird_test", defaults={})
     * @Method("GET")
     */
    public function importfirebirdAction()
    {
        $sciecha = "";
        
        $sql = "SELECT
        departament.KOD,
        mpr.DATA_OD,
mpr.DATA_DO,
            p.SYMBOL,
            COUNT(*) as ile,
            p.IMIE as imie, 
            p.NAZWISKO as nazwisko, 
            departament.OPIS  departament,
            stanowisko.OPIS stanowisko,
            rodzaj.NAZWA umowa,
            MIN(umowa.DATA_OD) as UMOWAOD,
            MAX(umowa.DATA_DO) as UMOWADO
            
            from P_PRACOWNIK p
            join PV_MP_PRA mpr on mpr.SYMBOL = p.SYMBOL AND (mpr.DATA_DO is NULL OR mpr.DATA_DO > CURRENT_TIMESTAMP) AND (mpr.DATA_OD < CURRENT_TIMESTAMP)
            join P_MPRACY departament on departament.KOD = mpr.KOD
            JOIN PV_ST_PRA stjoin on stjoin.SYMBOL= p.SYMBOL AND (stjoin.DATA_DO is NULL OR stjoin.DATA_DO > CURRENT_TIMESTAMP)
            join P_STANOWISKO stanowisko on stanowisko.KOD = stjoin.KOD
            join P_UMOWA umowa on umowa.SYMBOL = p.SYMBOL AND (umowa.DATA_DO is NULL OR umowa.DATA_DO > CURRENT_TIMESTAMP)
            join P_RODZUMOWY rodzaj on rodzaj.RODZAJ_UM = umowa.RODZAJ_UM
            
            GROUP BY 
           departament.KOD,
mpr.DATA_OD,
mpr.DATA_DO, 
            p.SYMBOL,       
            p.IMIE, 
            p.NAZWISKO, 
            departament.OPIS ,
            stanowisko.OPIS,
            rodzaj.NAZWA,
            umowa.DATA_OD,
            umowa.DATA_DO
            
            ORDER BY 
            p.NAZWISKO, p.IMIE
            ";

        //$rows = $this->executeQueryIbase($sql);
        $rows = $this->executeQuery($sql);
        echo "<pre>"; print_r($rows);
        die('testfirebird');
    }
    
    
    
    /**
     * Lists all Klaster entities.
     *
     * @Route("/importfirebird_test1", name="importfirebird_test1", defaults={})
     * @Method("GET")
     */
    public function importfirebird1Action()
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
    
    /**
     * Lists all Klaster entities.
     *
     * @Route("/importfirebird", name="importfirebird", defaults={})
     * @Method("GET")
     */
    public function importfirebird2Action()
    {
        $sciecha = "";
        $sql = "SELECT
            p.SYMBOL,
            COUNT(*) as ile,
            p.IMIE as imie, 
            p.NAZWISKO as nazwisko, 
            departament.OPIS  departament,
            stanowisko.OPIS stanowisko,
            rodzaj.NAZWA umowa,
            MIN(umowa.DATA_OD) as UMOWAOD,
            MAX(umowa.DATA_DO) as UMOWADO
            
            from P_PRACOWNIK p
            join PV_MP_PRA mpr on mpr.SYMBOL = p.SYMBOL AND (mpr.DATA_DO is NULL OR mpr.DATA_DO > CURRENT_TIMESTAMP)
            join P_MPRACY departament on departament.KOD = mpr.KOD
            JOIN PV_ST_PRA stjoin on stjoin.SYMBOL= p.SYMBOL AND (stjoin.DATA_DO is NULL OR stjoin.DATA_DO > CURRENT_TIMESTAMP)
            join P_STANOWISKO stanowisko on stanowisko.KOD = stjoin.KOD
            join P_UMOWA umowa on umowa.SYMBOL = p.SYMBOL AND (umowa.DATA_DO is NULL OR umowa.DATA_DO > CURRENT_TIMESTAMP)
            join P_RODZUMOWY rodzaj on rodzaj.RODZAJ_UM = umowa.RODZAJ_UM
            
            GROUP BY 
            
            p.SYMBOL,       
            p.IMIE, 
            p.NAZWISKO, 
            departament.OPIS ,
            stanowisko.OPIS,
            rodzaj.NAZWA,
            umowa.DATA_OD,
            umowa.DATA_DO
            
            ORDER BY 
            p.NAZWISKO, p.IMIE
            ";
        $rows = $this->executeQuery($sql);
        $em = $this->getDoctrine()->getManager();
        $data = array();
        $imported = array();
        foreach($rows as $row){
            
            $in = $this->parseValue($row['IMIE'])." ".$this->parseValue($row['NAZWISKO']);            
            $data[$in][] = $row;
        }
        $totalmsg = "";
        //echo "<pre>"; print_r($data); die();
        $ldap = $this->get('ldap_service');
        foreach($data as $in => $d){
            if(count($d) > 1){
                //mamy dubla szukamy najpozniejszej umowy
                $maxDate = null;
                foreach($d as $r){
                    $uod = $r['UMOWAOD'] ? new \Datetime($r['UMOWAOD']) : null;
                    if($uod != null && ($maxDate == null || $uod > $maxDate)){
                        $maxDate = $uod;
                        $row = $r;
                    }
                }
                $msg = "Są duplikaty umów dla  ".$in." wybrano najpóźniej podpisaną umowę z dnia ".$maxDate->format("Y-m-d").".";
                $this->addFlash('warning', $msg);
            }else{
                $row = $d[0];
            }
            $dr = $em->getRepository('ParpMainBundle:DaneRekord')->findOneBy(array('symbolRekordId' => $this->parseValue($row['SYMBOL'])));
            
            //temp
            $dr == null;
            
            //print_r($dr); die();
            $nowy = false;
            if($dr === null){
                $nowy = true;
                $dr = new \Parp\MainBundle\Entity\DaneRekord();
                $em->persist($dr);
            }
            $dr->setImie($this->parseValue($row['IMIE']));
            $dr->setNazwisko($this->parseValue($row['NAZWISKO']));
            $dr->setDepartament($this->parseValue($row['DEPARTAMENT']));
            $dr->setStanowisko($this->parseValue($row['STANOWISKO'], false));
            $dr->setUmowa($this->parseValue($row['UMOWA'], false));
            $dr->setSymbolRekordId($this->parseValue($row['SYMBOL'], false));
            $login = $this->get('samaccountname_generator')->generateSamaccountname($dr->getImie(), $dr->getNazwisko());
            $dr->setLogin($login);
            
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
            
            
            
            $uow = $em->getUnitOfWork();
            $uow->computeChangeSets();
            if ($uow->isEntityScheduled($dr) || $nowy) {
                $changeSet = $uow->getEntityChangeSet($dr);
                //echo "<pre>"; print_r($changeSet); die();
                if($nowy){
                    
                }else{
                    
                }
                
                $entry = new \Parp\MainBundle\Entity\Entry();
                $entry->setSamAccountName($login);
                
                
                
                
                if($nowy)
                    $entry->setCn($this->get('samaccountname_generator')->generateFullname($dr));
                if($nowy || $dr->getUmowaDo())
                    $entry->setAccountExpires($dr->getUmowaDo());
                $department = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findOneByNameInRekord($dr->getDepartament());
                
                if($department == null){
                    die('nie mam departamentu "'.$dr->getDepartament().'" dla '.$entry->getCn());
                }
                
                if($nowy || isset($changeSet['departament'])){
                    $entry->setDepartment($department->getName());
                    $entry->setGrupyAD($department);                              
                }
                //CN=Slawek Chlebowski, OU=BA,OU=Zespoly, OU=PARP Pracownicy, DC=AD,DC=TEST
                $tab = explode(".", $this->container->getParameter('ad_domain'));
                $ou = ($this->container->getParameter('ad_ou'));
                $entry->setDistinguishedname("CN=".$entry->getCn().", OU=" . $department->getShortname() . ",".$ou.", DC=" . $tab[0] . ",DC=" . $tab[1]);
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
                $em->persist($entry);
                
                $totalmsg .= "\n".($nowy ? "Utworzono dane" : "Uzupełniono dane ")." dla  ".$in." .";
                $imported[] = $dr;
            }
            
            
        }
        if($totalmsg != "")
            $this->addFlash('warning', $totalmsg);
        //echo "<pre>"; print_r($$imported); die();
        $em->flush(); 
        return $this->redirect($this->generateUrl('danerekord'));
    }
    protected function my_mb_ucfirst($str) {
        $fc = mb_strtoupper(mb_substr($str, 0, 1, "UTF-8"), "UTF-8");
        return $fc.mb_substr($str, 1, mb_strlen($str, "UTF-8"), "UTF-8");
    }
    protected function parseValue($v, $fupper = true){
        $v = iconv("WINDOWS-1250", "UTF-8", $v);
        $v = $fupper ? $this->my_mb_ucfirst(mb_strtolower($v, "UTF-8")) : mb_strtolower($v, "UTF-8");
        return trim($v);
    }
    
    protected function executeQuery($sql){
        $options = array(
            \PDO::ATTR_PERSISTENT    => true,//can help to improve performance
            \PDO::ATTR_ERRMODE       => \PDO::ERRMODE_EXCEPTION, //throws exceptions whenever a db error occurs
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES uft8'  //>= PHP 5.3.6
        );
        ////srv-rekorddb01.parp.local/bazy/PARP_KP.FDB
        $str_conn = "firebird:dbname=/var/www/parp/PARP_KP.FDB;host=localhost";
        //$str_conn = "firebird:dbname=/bazy/PARP_KP.FDB;host=srv-rekorddb01.parp.local";
        $userdb = 'SYSDBA';
        $passdb = 'masterkey';
        try {
          $conn = new \PDO($str_conn, $userdb, $passdb);//, $options);
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
} 