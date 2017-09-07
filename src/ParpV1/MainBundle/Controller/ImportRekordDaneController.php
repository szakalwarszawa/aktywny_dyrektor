<?php

namespace ParpV1\MainBundle\Controller;

use APY\DataGridBundle\Grid\Source\Vector;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\DaneRekord;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Services\ParpMailerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Klaster controller.
 * @Route("/import_rekord")
 */
class ImportRekordDaneController extends Controller
{

    protected $dataGraniczna = '2016-08-01';//'2011-10-01';//'2016-08-01'; //'2016-07-31'


    /**
     * Lists all Klaster entities.
     * @Route("/grid", name="importfirebird_grid_index", defaults={})
     * @Template("ParpMainBundle:DaneRekord:index.html.twig")
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function importfirebirdTestGridIndexAction()
    {
        $this->dataGraniczna = date('Y-m-d');
        if ($this->getUser()->getUsername() === 'kamil_jakacki') {
            //$this->dataGraniczna = '2016-09-01';//DEV
            echo '...DEV...';
        }

        $sql = $this->getSqlDoImportu();
        //$rows = $this->executeQueryIbase($sql);
        $dane = $this->executeQuery($sql);
        $rows = [];
        foreach ($dane as $d) {
            $row = [];
            foreach ($d as $k => $v) {
                $row[$k] = $this->parseValue($v);
            }
            $rows[] = $row;
        }
        $grid = $this->get('grid');
        $source = new Vector($rows);

        $source->setId('SYMBOL');
        $grid->setSource($source);
        $grid->setLimits(5000);

        $grid->isReadyForRedirect();

        return $grid->getGridResponse();
    }

    /*
        protected $container;

        public function setContainer(Symfony\Component\DependencyInjection\ContainerInterface $container = NULL)
        {
            $this->container = $container;
        }
    */

    public function getSqlDoImportu()
    {

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

    public function getSqlDoUzupelnienieDanychOZwolnionych($ids)
    {

        //AND (umowa.DATA_DO is NULL OR umowa.DATA_DO > '".$this->dataGraniczna."') AND umowa.DATA_OD <=  '".$this->dataGraniczna."'
        //$dataGraniczna = date("Y-m-d");
        $sql = 'SELECT
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
            where p.SYMBOL IN ('.implode(', ', $ids).')
            GROUP BY 
            
            p.SYMBOL,       
            p.IMIE, 
            p.NAZWISKO,
            rodzaj.NAZWA,
            umowa.DATA_OD,
            umowa.DATA_DO
            
            ORDER BY 
            p.NAZWISKO, p.IMIE
            ';

        return $sql;
    }

    protected function getUserFromAD($ldap, $dr)
    {
        $aduser = $ldap->getUserFromAD($dr->getLogin());
        if (count($aduser) > 0) {
            return $aduser;
        }
        $aduser = $ldap->getNieobecnyUserFromAD($dr->getLogin());
        if (count($aduser) > 0) {
            return $aduser;
        }
        $fullname = $dr->getNazwisko().' '.$dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getUserFromAD(null, $fullname);
        if (count($aduser) > 0) {
            return $aduser;
        }
        $aduser = $ldap->getNieobecnyUserFromAD(null, $fullname);
        if (count($aduser) > 0) {
            return $aduser;
        }
        $fullname = $dr->getNazwisko().' (*) '.$dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getUserFromAD(null, $fullname);
        if (count($aduser) > 0) {
            return $aduser;
        }
        $fullname = $dr->getNazwisko().' (*) '.$dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getNieobecnyUserFromAD(null, $fullname);
        if (count($aduser) > 0) {
            return $aduser;
        }

        return [];
    }


    /**
     * Lists all Klaster entities.
     * @Route("/importfirebird", name="importfirebird", defaults={})
     * @Method("GET")
     *
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function importfirebirdWrzucDoBazyAction()
    {
        $saNowi = false;
        $errors = [];

        $this->dataGraniczna = date('Y-m-d');
        //$this->dataGraniczna = "2016-09-01";//date("Y-m-d");


        //$this->dataGraniczna = "2016-08-20";//temp
        $mapowanieDepartamentowPrezesow = [
            '15'   => '400', //Prezes - stary uklad !!!! moje oznaczenie 400 , musze dogadac z kadrami !!!
            '216'  => '400', //WicePrezes - stary uklad, 3 szt.
            '326'  => '416', //Biuro Prezesa - stary uklad, 6 szt.
            '215'  => '400',
            '522'  => '523',
            '9999' => '400',
        ];
        $pomijajDaneRekord =
            ['2942'/*krakowiak*/, '3753'/*pocztowska*/, '3126' /*Sługocka-morawska*/, '3798' /*Stasińska*/];


        $sciecha = '';
        $sql = $this->getSqlDoImportu();
        $rows = $this->executeQuery($sql);
        $em = $this->getDoctrine()->getManager();
        $data = array();
        $imported = array();
        foreach ($rows as $row) {
            if (isset($mapowanieDepartamentowPrezesow[trim($row['DEPARTAMENT'])])) {
                //podmieniamy id biur prezesow
                $row['DEPARTAMENT'] = $mapowanieDepartamentowPrezesow[trim($row['DEPARTAMENT'])];
            }
            $in = $this->parseValue($row['IMIE']).' '.$this->parseValue($row['NAZWISKO']);
            $data[$in][] = $row;
        }
        $totalmsg = [];
        $ldap = $this->get('ldap_service');

        $rekordIds = [];
        /*
                $data["KAMIL JAKACKI"][] = [
                    'SYMBOL' => '3834',
                    'STANOWISKO' => 'starszy specjalista',
                    'DEPARTAMENT' => '526',
                    'IMIE' => 'KAMIL',
                    'NAZWISKO' => 'JAKACKI',
                    'UMOWA' => 'Na czas nieokreślony',
                    'UMOWAOD' => '2016-03-24x 00:00:00',
                    'UMOWADO' => NULL,
                ];
        */
        //temp by sprawdzic czy utworzy dubla mnie
        /*
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
        */


        foreach ($data as $in => $d) {
            if (count($d) > 1) {
                //mamy dubla szukamy najpozniejszej umowy
                $minDate = null;
                $minDep = null;
                $teSameDaty = true;
                foreach ($d as $r) {
                    $uod = $r['UMOWAOD'] ? new \Datetime($r['UMOWAOD']) : null;
                    $teSameDaty =
                        $minDate == null ? true : $teSameDaty && ($minDate->format('Y-m-d') == $uod->format('Y-m-d'));
                    if ($uod != null && ($minDate == null || $uod < $minDate)) {
                        $minDate = $uod;
                        $minDep = $r['DEPARTAMENT'];
                        $row = $r;
                    }
                }

                $msg =
                    'Są duplikaty umów dla  '.
                    $in.
                    ' wybrano najwcześniej podpisaną umowę z dnia '.
                    $minDate->format('Y-m-d').
                    ' te same daty: '.
                    ($teSameDaty ? 'tak' : 'nie').
                    '.';
                $this->addFlash('warning', $msg);
            } else {
                $row = $d[0];
            }

            $dr =
                $em->getRepository('ParpMainBundle:DaneRekord')
                    ->findOneBy(array('symbolRekordId' => $this->parseValue($row['SYMBOL'])));

            if ($dr == null || !in_array($dr->getSymbolRekordId(), $pomijajDaneRekord, true)) {
                $rekordIds[$this->parseValue($row['SYMBOL'])] = $this->parseValue($row['SYMBOL']);
                //temp
                //$dr == null;
                $d = new \Datetime();
                $nowy = false;
                $poprzednieDane = null;
                if ($dr === null) {
                    $nowy = true;
                    $dr = new \ParpV1\MainBundle\Entity\DaneRekord();
                    $dr->setCreatedBy($this->getUser()->getUsername());
                    $dr->setCreatedAt($d);
                    $dr->setNewUnproccessed(1);
                    $em->persist($dr);
                    $saNowi = true;
                    $poprzednieDane = clone $dr;
                } else {
                    $poprzednieDane = clone $dr;
                }

                //print_r($row['SYMBOL']);
                //print_r($dr->getNazwisko());
                //print_r($poprzednieDane->getNazwisko());

                $dr->setImie($this->parseValue($row['IMIE']));
                $dr->setNazwisko($this->parseNazwiskoValue($row['NAZWISKO']));
                if ($nowy) {
                    $login =
                        $this->get('samaccountname_generator')
                            ->generateSamaccountname($dr->getImie(), $dr->getNazwisko());
                    $dr->setLogin($login);
                    //$this->sendMailAboutNewUser($dr->getNazwisko()." ".$dr->getImie(), $login);
                }

                $dr->setDepartament($this->parseValue($row['DEPARTAMENT']));
                $dr->setStanowisko($this->parseValue($row['STANOWISKO'], false));
                $dr->setUmowa($this->parseValue($row['UMOWA'], false));
                $dr->setSymbolRekordId($this->parseValue($row['SYMBOL'], false));

                $d1 = $row['UMOWAOD'] ? new \Datetime($row['UMOWAOD']) : null;
                if (($d1 !== null) &&
                    (
                        ($dr->getUmowaOd() == null && $d1 != null) ||
                        ($dr->getUmowaOd() != null && $d1 == null) ||
                        ($dr->getUmowaOd()->format('Y-m-d') != $d1->format('Y-m-d'))
                    )
                ) {
                    $dr->setUmowaOd($d1);
                }
                $d2 = $row['UMOWADO'] ? new \Datetime($row['UMOWADO']) : null;

                //var_dump($dr->getUmowaDo());
                //var_dump($d2);
                if ((
                    ($dr->getUmowaDo() == null && $d2 != null) ||
                    ($dr->getUmowaDo() != null && $d2 == null) ||
                    ($dr->getUmowaDo() != null &&
                        $d2 != null &&
                        $dr->getUmowaDo()->format('Y-m-d') != $d2->format('Y-m-d')))
                ) {
                    $dr->setUmowaDo($d2);
                }


                $dr->setLastModifiedAt($d);

                $uow = $em->getUnitOfWork();
                $uow->computeChangeSets();
                if (1 == 1 && ($uow->isEntityScheduled($dr) || $nowy)) {
                    $changeSet = $uow->getEntityChangeSet($dr);
                    unset($changeSet['lastModifiedAt']);
                    if ($nowy) {
                    } else {
                    }
                    if (count($changeSet) > 0) {
                        $totalmsg[] = "\n".($nowy ? 'Utworzono dane' : 'Uzupełniono dane ').' dla  '.$dr->getLogin().
                            ' .';
                    }


                    if ((isset($changeSet['departament']) || isset($changeSet['stanowisko'])) && !$nowy) {
                        //die("mam zmiane stanowiska lub depu dla istniejacego");
                        $dr->setNewUnproccessed(2);
                    } elseif (count($changeSet) > 0 && !$nowy) {
                        $this->utworzEntry($em, $dr, $changeSet, $nowy, $poprzednieDane);
                        $imported[] = $dr;
                    }

                    if ($nowy) {
                    } else {
                        if (isset($changeSet['departament'])) {
                            //zmiana departamentu
                            $this->get('parp.mailer')->sendEmailZmianaKadrowaMigracja($dr, $poprzednieDane, true);
                        }
                        /*
                        if(isset($changeSet['stanowisko']){
                            //zmiana departamentu
                            $this->get('parp.mailer')->sendEmailZmianaKadrowaMigracja($dr, $poprzednieDane, true);
                        }*/
                    }
                }

                if ($dr->getLogin() === 'kamil_jakacki') {
                    //var_dump($changeSet);
                    //die('nie zapisuje bo kamil_jakacki');
                }
            }
        }
        if (count($totalmsg) > 0) {
            $this->addFlash('warning', implode('<br>', $totalmsg));
        }
        //echo "<pre>"; print_r($imported);
        //die();


        //teraz powinien sprawdzac czy ktos mi nie zniknal
        $drs = $em->getRepository('ParpMainBundle:DaneRekord')->findByNotHavingRekordIds($rekordIds);
        if (count($drs) > 0) {
            $ids = [];
            foreach ($drs as $d) {
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

        return $this->render(
            'ParpMainBundle:DaneRekord:showData.html.twig',
            ['data' => $errors, 'msg' => implode('<br>', $totalmsg), 'saNowi' => $saNowi]
        );
        //return $this->redirect($this->generateUrl('danerekord'));
    }

    /**
     * @param EntityManager $em
     * @param DaneRekord    $dr
     * @param array         $changeSet
     * @param boolean       $nowy
     * @param array         $poprzednieDane
     *
     * @return Entry
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    protected function utworzEntry($em, $dr, $changeSet, $nowy, $poprzednieDane)
    {
        $ldap = $this->get('ldap_service');

        $entry = new Entry($this->getUser()->getUsername());
        $em->persist($entry);

        $entry->setDaneRekord($dr);
        $dr->addEntry($entry);
        $entry->setSamaccountname($dr->getLogin());

        if (true === $nowy) {
            $entry->setCn($this->get('samaccountname_generator')->generateFullname($dr->getImie(), $dr->getNazwisko()));
        }

        if ((isset($changeSet['imie']) || isset($changeSet['nazwisko'])) && !$nowy) {
            //zmiana imienia i nazwiska
            $entry->setCn($this->get('samaccountname_generator')
                ->generateFullname(
                    $dr->getImie(),
                    $dr->getNazwisko(),
                    $poprzednieDane->getImie(),
                    $poprzednieDane->getNazwisko()
                ));
        }

        if ($nowy || $dr->getUmowaDo()) {
            if ($dr->getUmowaDo()) {
                $entry->setAccountExpires($dr->getUmowaDo());
            } else {
                $entry->setAccountExpires(new \Datetime('2000-01-01'));//musimy wstawic nie null zeby potem wypychanie do AD wiedzialo ze chcemy wyczyscic to pole
            }
        }
        $department =
            $this->getDoctrine()
                ->getRepository('ParpMainBundle:Departament')
                ->findOneByNameInRekord($dr->getDepartament());

        if ($department == null) {
            echo('nie mam departamentu "'.$dr->getDepartament().'" dla '.$entry->getCn());
        } else {
            if ($nowy || isset($changeSet['departament'])) {
                $entry->setDepartment($department->getName());
                $entry->setGrupyAD($department);
            }

            //CN=Slawek Chlebowski, OU=BA,OU=Zespoly, OU=PARP Pracownicy, DC=AD,DC=TEST
            $tab = explode('.', $this->container->getParameter('ad_domain'));
            $ou = ($this->container->getParameter('ad_ou'));
            if ($nowy) {
                $dn = 'CN='.$entry->getCn().', OU='.$department->getShortname().','.$ou.', DC='.$tab[0].
                    ',DC='.$tab[1];
            } else {
                $aduser = $this->getUserFromAD($ldap, $dr);//$ldap->getUserFromAD($dr->getLogin());
                if (count($aduser) === 0) {
                    $errors[] = ('Nie moge znalezc osoby  !!!: '.$dr->getLogin());
                } else {
                    $dn = $aduser[0]['distinguishedname'];
                }
            }
            //var_dump($entry->getCn(),  $dr->getImie(), $dr->getNazwisko(), $dn);
            $entry->setDistinguishedname($dn);
        }
        //$entry->setDivision();//TODO:
        if ($nowy || isset($changeSet['stanowisko'])) {
            $mapa = [
                'rzecznik beneficjenta parp, dyrektor' => 'Rzecznik Beneficjenta PARP',
            ];
            $stanowisko = $dr->getStanowisko();
            if (isset($mapa[$dr->getStanowisko()])) {
                $stanowisko = $mapa[$dr->getStanowisko()];
            }
            $entry->setTitle($dr->getStanowisko());
        }
        $entry->setFromWhen(new \Datetime());
        if ($nowy) {
            $in = mb_substr($dr->getImie(), 0, 1, 'UTF-8').mb_substr($dr->getNazwisko(), 0, 1, 'UTF-8');
            if ($in === '') {
                $in = null;
            }
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
     * @Route("/sql", name="importfirebird_sql", defaults={})
     * @Method("GET")
     */
    public function showSqlAction()
    {
        //die($this->parseNazwiskoValue("JAKACKI-TEST"));
        $sciecha = '';

        $this->dataGraniczna = date('Y-m-d');
        //$this->dataGraniczna = '2016-11-01';
        $sql = $this->getSqlDoImportu();
        $miesiac = 1;
        $rok = 2012;
        die($sql);
    }

    /**
     * Lists all Klaster entities.
     * @Route("/", name="importfirebird_index", defaults={})
     * @Method("GET")
     */
    public function importfirebirdTestIndexAction()
    {
        //die($this->parseNazwiskoValue("JAKACKI-TEST"));
        $sciecha = '';

        $this->dataGraniczna = date('Y-m-d');
        //$this->dataGraniczna = '2016-11-01';
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
        $sciecha = '';

        $sql = 'select rdb$relation_name
from rdb$relations
where rdb$view_blr is null 
and (rdb$system_flag is null or rdb$system_flag = 0);';


        $rows = $this->executeQuery($sql);
        echo '<pre>';
        print_r($rows);
        die('testfirebird');
    }

    protected function parseName($n)
    {
        $cz = explode(' ', $n);
        $ret = [];
        foreach ($cz as $c) {
            $ret[] = mb_strtoupper(mb_substr($c, 0, 1)).mb_strtolower(mb_substr($c, 1));
        }

        return implode(' ', $ret);
    }

    /**
     * Lists all Klaster entities.
     * @Route("/departamenty_popraw", name="departamenty_popraw", defaults={})
     * @Method("GET")
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function departamentyPoprawAction()
    {
        $sciecha = '';

        $sql = 'SELECT
        *            from P_MPRACY p
            
            ';

        //$rows = $this->executeQueryIbase($sql);
        $rows = $this->executeQuery($sql);

        foreach ($rows as $row) {
            if ($row['KOD'] > 400 && $row['KOD'] < 500) {
                $d =
                    $this->getDoctrine()
                        ->getManager()
                        ->getRepository('ParpMainBundle:Departament')
                        ->findOneByNameInRekord($this->parseValue($row['OPIS'], false));
                if ($d) {
                    $d->setNameInRekord($this->parseValue($row['KOD']));
                    $this->getDoctrine()->getManager()->persist($d);
                }
            }
        }
        $this->getDoctrine()->getManager()->flush();
        echo '<pre>';
        print_r($rows);
        die('testfirebird');
    }

    /**
     * Lists all Klaster entities.
     * @Route("/departamenty_import", name="departamenty", defaults={})
     * @Method("GET")
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function departamentyAction()
    {
        $sciecha = '';

        $sql = 'SELECT
        *            from P_MPRACY p
            
            ';

        //$rows = $this->executeQueryIbase($sql);
        $rows = $this->executeQuery($sql);

        foreach ($rows as $row) {
            if ($row['KOD'] > 500 && $row['KOD'] < 600) {
                $dep = new \ParpV1\MainBundle\Entity\Departament();
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
        echo '<pre>';
        print_r($rows);
        die('testfirebird');
    }


    protected function myMbUcfirst($str)
    {
        $fc = mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8');

        return $fc.mb_substr($str, 1, mb_strlen($str, 'UTF-8'), 'UTF-8');
    }

    public function parseValue($v, $fupper = true)
    {
        $v = iconv('WINDOWS-1250', 'UTF-8', $v);
        $v = $fupper ? $this->myMbUcfirst(mb_strtolower($v, 'UTF-8')) : mb_strtolower($v, 'UTF-8');

        return trim($v);
    }

    public function parseNazwiskoValue($v, $fupper = true)
    {
        $v = iconv('WINDOWS-1250', 'UTF-8', $v);
        $v = $fupper ? $this->myMbUcfirst(mb_strtolower($v, 'UTF-8')) : mb_strtolower($v, 'UTF-8');

        $ps = explode('-', $v);
        $ret = [];
        foreach ($ps as $p) {
            if (trim($p) !== '') {
                $ret[] = trim($this->myMbUcfirst(mb_strtolower($p, 'UTF-8')));
            }
        }

        return implode('-', $ret);
    }

    public function executeQuery($sql)
    {
        $options = array(
            \PDO::ATTR_PERSISTENT         => true,//can help to improve performance
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION, //throws exceptions whenever a db error occurs
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES uft8'  //>= PHP 5.3.6
        );
        ////srv-rekorddb01.parp.local/bazy/PARP_KP.FDB
        $str_conn =
            $this->container->getParameter('rekord_db'); //"firebird:dbname=/var/www/parp/PARP_KP.FDB;host=localhost";
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
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    protected function executeQueryIbase($sql)
    {
        if ($db = \ibase_connect(
            'localhost:/var/www/parp/PARP_KP.FDB',
            'SYSDBA',
            'masterkey'
        )
        ) {
            //echo 'Connected to the database.';

            $result = \ibase_query($db, $sql); // assume $tr is a transaction

            $count = 0;
            while ($row = ibase_fetch_assoc($result)) {
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

    public function outputCSV($data)
    {
        $outputBuffer = fopen('php://output', 'bw');
        foreach ($data as $val) {
            fputcsv($outputBuffer, $val, ';');
        }
        fclose($outputBuffer);
    }

    protected function getObjectPropertiesAsArray($obj, $props)
    {
        $ret = [];
        foreach ($props as $p) {
            $getter = 'get'.ucfirst($p);
            $ret[$p] = $obj->{$getter}();
        }

        return $ret;
    }

    /**
     * @Route("/przejrzyjnowych", name="przejrzyjnowych", defaults={})
     * @Method("GET")
     * @throws \LogicException
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    public function przejrzyjnowychAction()
    {
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();
        $nowi = $em->getRepository('ParpMainBundle:DaneRekord')->findNewPeople();
        $data = [];
        foreach ($nowi as $dr) {
            $users = $this->getUserFromAllAD($dr);
            $departament =
                $em->getRepository('ParpMainBundle:Departament')->findOneByNameInRekord($dr->getDepartament());
            $d =
                $this->getObjectPropertiesAsArray($dr, [
                    'id',
                    'imie',
                    'nazwisko',
                    'stanowisko',
                    'umowa',
                    'umowaOd',
                    'umowaDo',
                    'login',
                    'newUnproccessed',
                ]);
            $d['users'] = $users;
            $d['departament'] = $departament;
            $data[] = $d;
        }

        // Pobieramy listę Sekcji
        $sectionsEntity =
            $this->getDoctrine()->getRepository('ParpMainBundle:Section')->findBy(array(), array('name' => 'asc'));
        $sections = array();
        foreach ($sectionsEntity as $tmp) {
            $dep = $tmp->getDepartament() ? $tmp->getDepartament()->getShortname() : 'bez departamentu';
            $sections[$dep][$tmp->getName()] = $tmp->getName();
        }

        return $this->render(
            'ParpMainBundle:DaneRekord:przejrzyjNowych.html.twig',
            [
                'data'       => $data,
                'przelozeni' => $ldap->getPrzelozeni(),
                'sekcje'     => $sections,
            ]
        );
    }

    protected function getUserFromAllAD($dr)
    {
        $ldap = $this->get('ldap_service');
        $ret = [];
        $aduser = $ldap->getUserFromAD($dr->getLogin(), null, null, 'wszyscyWszyscy');
        if (count($aduser) > 0) {
            foreach ($aduser as $u) {
                if (!isset($ret[$u['samaccountname']])) {
                    $ret[$u['samaccountname']] = $u;
                }
            }
        }
        $fullname = $dr->getNazwisko().' '.$dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getUserFromAD(null, $fullname, null, 'wszyscyWszyscy');
        if (count($aduser) > 0) {
            foreach ($aduser as $u) {
                if (!isset($ret[$u['samaccountname']])) {
                    $ret[$u['samaccountname']] = $u;
                }
            }
        }
        $fullname = $dr->getNazwisko().' (*) '.$dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getUserFromAD(null, $fullname, null, 'wszyscyWszyscy');
        if (count($aduser) > 0) {
            foreach ($aduser as $u) {
                if (!isset($ret[$u['samaccountname']])) {
                    $ret[$u['samaccountname']] = $u;
                }
            }
        }

        return $ret;
    }

    /**
     * @Route("/przypiszUtworzUzytkownika/{id}/{samaccountname}", name="przypiszUtworzUzytkownika", defaults={})
     * @Method("POST")
     * @param Request $request
     * @param         $id
     * @param         $samaccountname
     *
     * @return Response
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function przypiszUtworzUzytkownikaAction(Request $request, $id, $samaccountname)
    {
        $zmieniamySekcje = false;
        $dane = $request->request->all();

        $ldapService = $this->get('ldap_service');
        $userFromAD = $ldapService->getUserFromAD($samaccountname);
        $objectManager = $this->getDoctrine()->getManager();
        /** @var DaneRekord $daneRekord */
        $daneRekord = $objectManager->getRepository('ParpMainBundle:DaneRekord')->find($id);
        $poprzednieDane = clone $daneRekord;

        if ($daneRekord->getNewUnproccessed() > 0) {
            $changeSet = [];

            if ($samaccountname !== 'nowy') {
                $nowy = false;

                if ($daneRekord->getNewUnproccessed() === 2) {
                    // Użytkownik już istnieje w Rekordzie
                    $samaccountname = $daneRekord->getLogin();
                } else {
                    $daneRekord->setLogin($samaccountname);
                }

                $userFromAD = $ldapService->getUserFromAD($samaccountname, null, null, 'wszyscyWszyscy');

                if (count($userFromAD) === 0) {
                    $nowy = true;
                    $changeSet = ['imie' => 1, 'nazwisko' => 1, 'departament' => 1, 'stanowisko' => 1];
                } else {
                    if ($userFromAD[0]['name'] !== $daneRekord->getNazwisko().' '.$daneRekord->getImie()) {
                        $changeSet['imie'] = 1;
                        $changeSet['nazwisko'] = 1;
                    }
                    if ($userFromAD[0]['department'] !== $daneRekord->getDepartament()) {
                        $changeSet['department'] = 1;
                    }
                    if ($userFromAD[0]['title'] !== $daneRekord->getStanowisko()) {
                        $changeSet['stanowisko'] = 1;
                    }
                    if ($dane['form']['info'] !== '' && $userFromAD[0]['info'] !== $dane['form']['info']) {
                        $zmieniamySekcje = true;
                    }
                }
            } else {
                $nowy = true;
                //nowy user
                $changeSet = ['imie' => 1, 'nazwisko' => 1, 'departament' => 1, 'stanowisko' => 1];
                $zmieniamySekcje = true;
            }

            $entry = $this->utworzEntry($objectManager, $daneRekord, $changeSet, $nowy, $poprzednieDane);

            if (!$nowy && $daneRekord->getNewUnproccessed() === 2) {
                // Jeśli nie jest nowy i istnieje w Rekordzie
                //trzeba odebrac stare
                $oldDepartament =
                    $this->getDoctrine()
                        ->getRepository('ParpMainBundle:Departament')
                        ->findOneByName($userFromAD[0]['department']);
                $section = $objectManager->getRepository('ParpMainBundle:Section')->findOneByName($userFromAD[0]['division']);
                $grupyNaPodstawieSekcjiOrazStanowiska =
                    $ldapService->getGrupyUsera(
                        $userFromAD[0],
                        $oldDepartament->getShortname(),
                        ($section ? $section->getShortname() : '')
                    );
                $entry->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, '-');
            }

            $departament =
                $this->getDoctrine()
                    ->getRepository('ParpMainBundle:Departament')
                    ->findOneByNameInRekord($daneRekord->getDepartament());
            $entry->setDepartment($departament->getName());
            $section = $objectManager->getRepository('ParpMainBundle:Section')->findOneByName($dane['form']['info']);
            $grupyNaPodstawieSekcjiOrazStanowiska =
                $ldapService->getGrupyUsera(
                    ['title' => $daneRekord->getStanowisko()],
                    $departament->getShortname(),
                    ($section ? $section->getShortname() : '')
                );
            $entry->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, '+');

            if ($dane['form']['accountExpires'] !== '') {
                $v = \DateTime::createFromFormat('Y-m-d', $dane['form']['accountExpires']);
                $entry->setAccountExpires($v);
            }
            if ($dane['form']['info'] !== '') {
                $entry->setInfo($dane['form']['info']);
            }
            if ($dane['form']['manager'] !== '') {
                $manager = $ldapService->getUserFromAD($dane['form']['manager']);
                $entry->setManager($manager[0]['name']);
            }

            if (!$nowy) {
                $administratorzy = [];

                // muchar: To wygląda na jakiś rozgrzebany kod. W encji UserZasoby nie ma getAdministratorZasobu. Jest
                // w Zasob.
                //                /** @var UserZasoby[] $userzasoby */
                $userzasoby = $objectManager->getRepository('ParpMainBundle:UserZasoby')
                    ->findBy(['samaccountname' => $samaccountname]);

                foreach ($userzasoby as $uz) {
                    $zasob = $objectManager->getRepository('ParpMainBundle:Zasoby')->find($uz->getZasobId());
                    if ($uz->getZasobId()
                        && !in_array($zasob->getAdministratorZasobu(), $administratorzy, true)
                    ) {
                        // Pobieramy administratora zasobu
                        $administratorzy[] = $zasob->getAdministratorZasobu();
                    }
                }

                if ($zmieniamySekcje && !isset($changeSet['departament'])) {
                    $this->get('parp.mailer')
                        ->sendEmailZmianaSekcji($userFromAD[0], $dane['form']['info'], $administratorzy);
                }
                if (isset($changeSet['stanowisko'])) {
                    // Do stanowiska pobieramy dane bezpośrednio z Rekorda - gdyż nie ma w formularzu możliwości
                    // wyboru stanowiska. W ogóle z ciekawostek - $administratorzy nie są w ogóle wykorzystyw
                    $this->get('parp.mailer')
                        ->sendEmailZmianaStanowiska($userFromAD[0], $daneRekord->getStanowisko(), $administratorzy);
                }
            } else {
                //['departament', 'data_nadania_uprawnien_poczatkowych']
                $now = new \Datetime();
                $dane = [
                    'imie_nazwisko'                       => $daneRekord->getImie().' '.$daneRekord->getNazwisko(),
                    'login'                               => $daneRekord->getLogin(),
                    'departament'                         => $departament->getName(),
                    'data_nadania_uprawnien_poczatkowych' => $now,
                ];

                $this->get('parp.mailer')->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEIMPORT, $dane);
                $this->get('parp.mailer')
                    ->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIENADANIEUPRAWNIEN, $dane);
            }

            $daneRekord->setNewUnproccessed(0);
            $objectManager->flush();
        }

        return new Response();
    }

    public function sendMailAboutNewUser($name, $samaccountname)
    {
        $mails = ['kamil_jakacki@parp.gov.pl', 'marcin_lipinski@parp.gov.pl'];
        $view =
            'Dnia '.
            date('Y-m-d').
            " został utworzony nowy użytkownik '".
            $name.
            "' o loginie '".
            $samaccountname.
            "', utwórz mu pocztę pliz :)";
        $message = \Swift_Message::newInstance()
            ->setSubject('Nowy użytkownik w AkD')
            ->setFrom('intranet@parp.gov.pl')
            //->setFrom("kamikacy@gmail.com")
            ->setTo($mails)
            ->setBody($view)
            ->setContentType('text/html');

        $this->container->get('mailer')->send($message);
    }
}
