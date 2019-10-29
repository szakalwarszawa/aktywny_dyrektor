<?php

namespace ParpV1\MainBundle\Controller;

use APY\DataGridBundle\Grid\Source\Vector;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\DaneRekord;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\Section;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Entity\Position;
use ParpV1\MainBundle\Services\ParpMailerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use ParpV1\MainBundle\Entity\OdebranieZasobowEntry;

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

        // w linii fragment 'AND mpr.KOD < 1000' - pomijamy pracownikow z dep o ID > 1000, Redmine #66470
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
                (mpr.DATA_DO is NULL OR mpr.DATA_DO >= '" . $this->dataGraniczna . "')
            join P_MPRACY departament on departament.KOD = mpr.KOD
            JOIN PV_ST_PRA stjoin on stjoin.SYMBOL= p.SYMBOL AND
                (stjoin.DATA_DO is NULL OR stjoin.DATA_DO >= '" . $this->dataGraniczna . "')
            join P_STANOWISKO stanowisko on stanowisko.KOD = stjoin.KOD
            join P_UMOWA umowa on umowa.SYMBOL = p.SYMBOL AND
                (umowa.DATA_DO is NULL OR umowa.DATA_DO >= '" . $this->dataGraniczna . "')
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
            where p.SYMBOL IN (' . implode(', ', $ids) . ')
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
        $fullname = $dr->getNazwisko() . ' ' . $dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getUserFromAD(null, $fullname);
        if (count($aduser) > 0) {
            return $aduser;
        }
        $aduser = $ldap->getNieobecnyUserFromAD(null, $fullname);
        if (count($aduser) > 0) {
            return $aduser;
        }
        $fullname = $dr->getNazwisko() . ' (*) ' . $dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getUserFromAD(null, $fullname);
        if (count($aduser) > 0) {
            return $aduser;
        }
        $fullname = $dr->getNazwisko() . ' (*) ' . $dr->getImie();
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
            $in = $this->parseValue($row['IMIE']) . ' ' . $this->parseValue($row['NAZWISKO']);
            $data[$in][] = $row;
        }
        $totalmsg = [];

        $rekordIds = [];

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
                    'Są duplikaty umów dla  ' .
                    $in .
                    ' wybrano najwcześniej podpisaną umowę z dnia ' .
                    $minDate->format('Y-m-d') .
                    ' te same daty: ' .
                    ($teSameDaty ? 'tak' : 'nie') .
                    '.';
                $this->addFlash('warning', $msg);
            } else {
                $row = $d[0];
            }

            $dr =
                $em->getRepository(DaneRekord::class)
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
                    $dr = new DaneRekord();
                    $dr
                        ->setCreatedBy($this->getUser()->getUsername())
                        ->setCreatedAt($d)
                        ->setNewUnproccessed(1)
                        ->setStaticStatusNumber(1)
                    ;

                    $em->persist($dr);
                    $saNowi = true;
                }

                $poprzednieDane = clone $dr;

                $dr
                    ->setImie($this->parseValue($row['IMIE']))
                    ->setNazwisko($this->parseNazwiskoValue($row['NAZWISKO']))
                ;
                if ($nowy) {
                    $login =
                        $this->get('samaccountname_generator')
                            ->generateSamaccountname($dr->getImie(), $dr->getNazwisko());
                    $dr->setLogin($login);
                    //$this->sendMailAboutNewUser($dr->getNazwisko()." ".$dr->getImie(), $login);
                }

                $dr
                    ->setDepartament($this->parseValue($row['DEPARTAMENT']))
                    ->setStanowisko($this->parseValue($row['STANOWISKO'], false))
                    ->setUmowa($this->parseValue($row['UMOWA'], false))
                    ->setSymbolRekordId($this->parseValue($row['SYMBOL'], false))
                ;

                $d1 = $row['UMOWAOD'] ? new \Datetime($row['UMOWAOD']) : null;
                if (
                    ($d1 !== null) &&
                    (
                        ($dr->getUmowaOd() == null && $d1 != null) ||
                        ($dr->getUmowaOd() != null && $d1 == null) ||
                        ($dr->getUmowaOd()->format('Y-m-d') != $d1->format('Y-m-d'))
                    )
                ) {
                    $dr->setUmowaOd($d1);
                }
                $d2 = $row['UMOWADO'] ? new \Datetime($row['UMOWADO']) : null;

                if (
                    (
                    ($dr->getUmowaDo() == null && $d2 != null) ||
                    ($dr->getUmowaDo() != null && $d2 == null) ||
                    ($dr->getUmowaDo() != null &&
                        $d2 != null &&
                        $dr->getUmowaDo()->format('Y-m-d') != $d2->format('Y-m-d')))
                ) {
                    $dr->setUmowaDo($d2)->setTime(23, 59);
                }


                $dr->setLastModifiedAt($d);

                $uow = $em->getUnitOfWork();
                $uow->computeChangeSets();
                if (1 == 1 && ($uow->isEntityScheduled($dr) || $nowy)) {
                    $changeSet = $uow->getEntityChangeSet($dr);
                    unset($changeSet['lastModifiedAt']);

                    if (count($changeSet) > 0) {
                        $totalmsg[] = "\n" . ($nowy ? 'Utworzono dane' : 'Uzupełniono dane ') . ' dla  ' . $dr->getLogin() .
                            ' .';
                    }

                    if ((isset($changeSet['departament']) || isset($changeSet['stanowisko'])) && !$nowy) {
                        //die("mam zmiane stanowiska lub depu dla istniejacego");
                        $dr
                            ->setNewUnproccessed(2)
                            ->setStaticStatusNumber(2)
                        ;
                    } elseif (count($changeSet) > 0 && !$nowy) {
                        $this->utworzEntry($em, $dr, $changeSet, $nowy, $poprzednieDane, false);
                        $imported[] = $dr;
                    }
                }
            }
        }
        if (count($totalmsg) > 0) {
            $this->addFlash('warning', implode('<br>', $totalmsg));
        }

        //teraz powinien sprawdzac czy ktos mi nie zniknal
        $drs = $em->getRepository(DaneRekord::class)->findByNotHavingRekordIds($rekordIds);
        if (count($drs) > 0) {
            $ids = [];
            foreach ($drs as $d) {
                $ids[$d->getSymbolRekordId()] = $d->getSymbolRekordId();
            }
            $sql = $this->getSqlDoUzupelnienieDanychOZwolnionych($ids);

            $rowsZwolnieni = $this->executeQuery($sql);
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
    protected function utworzEntry($em, $dr, $changeSet, $nowy, $poprzednieDane, $resetDoPodstawowych)
    {
        $ldap = $this->get('ldap_service');
        $entry = (null !== $this->getUser()) ? new Entry($this->getUser()->getUsername()) : new Entry();


        $entry->setDaneRekord($dr);
        $dr->addEntry($entry);
        $entry->setSamaccountname($dr->getLogin());

        if (true === $nowy) {
            $entry->setCn($this->get('samaccountname_generator')->generateFullname($dr->getImie(), $dr->getNazwisko()));
        }

        if ((isset($changeSet['imie']) || isset($changeSet['nazwisko'])) && !$nowy) {
            //zmiana imienia i nazwiska
            if (is_object($poprzednieDane)) {
                $imie = $poprzednieDane->getImie();
                $nazwisko = $poprzednieDane->getNazwisko();
            } else {
                $imie = $poprzednieDane[1];
                $nazwisko = $poprzednieDane[0];
            }
            $entry->setRenaming(true);
            $entry->setCn($this->get('samaccountname_generator')
                ->generateFullname(
                    $dr->getImie(),
                    $dr->getNazwisko(),
                    $imie,
                    $nazwisko
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
                ->getRepository(Departament::class)
                ->findOneByNameInRekord($dr->getDepartament());

        if ($department == null) {
            echo('nie mam departamentu "' . $dr->getDepartament() . '" dla ' . $entry->getCn());
        } else {
            if ($nowy || isset($changeSet['departament'])) {
                $entry->setDepartment($department->getName());
                $entry->setGrupyAD($department);
            }

            //CN=Slawek Chlebowski, OU=BA,OU=Zespoly, OU=PARP Pracownicy, DC=AD,DC=TEST
            $tab = explode('.', $this->container->getParameter('ad_domain'));
            $ou = ($this->container->getParameter('ad_ou'));
            if ($nowy) {
                $dn = 'CN=' . $entry->getCn() . ', OU=' . $department->getShortname() . ',' . $ou . ', DC=' . $tab[0] .
                    ',DC=' . $tab[1];
            } else {
                $aduser = $this->getUserFromAD($ldap, $dr);//$ldap->getUserFromAD($dr->getLogin());
                if (count($aduser) === 0) {
                    $errors[] = ('Nie moge znalezc osoby  !!!: ' . $dr->getLogin());
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
            $in = mb_substr($dr->getImie(), 0, 1, 'UTF-8') . mb_substr($dr->getNazwisko(), 0, 1, 'UTF-8');
            if ($in === '') {
                $in = null;
            }
            $entry->setInitials($in);
            /*
                        if($dr->getNazwisko() == "Turlej")
                            die(".".$dr->getImie().".");
            */
        }


        if ($resetDoPodstawowych) {
            $resetEntry = new OdebranieZasobowEntry();
            $resetEntry
                ->setPowodOdebrania('Zmiana departamentu/sekcji/stanowiska (rekord import)')
                ->setUzytkownik($entry->getSamaccountname())
            ;

            $this->getDoctrine()->getManager()->persist($resetEntry);
            $entry->setOdebranieZasobowEntry($resetEntry);
        }


        $entry->setIsImplemented(0);
        $entry->setInitialRights('');
        $entry->setIsDisabled(0);

        $em->persist($entry);

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
            $ret[] = mb_strtoupper(mb_substr($c, 0, 1)) . mb_strtolower(mb_substr($c, 1));
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
                        ->getRepository(Departament::class)
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

        return $fc . mb_substr($str, 1, mb_strlen($str, 'UTF-8'), 'UTF-8');
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

        $str_conn = $this->container->getParameter('rekord_db');
        $userdb = $this->container->getParameter('rekord_db_user');
        $passdb = $this->container->getParameter('rekord_db_password');

        try {
            $conn = new \PDO($str_conn, $userdb, $passdb);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $statement = $conn->query($sql);
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

            return $rows;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    protected function executeQueryIbase($sql)
    {
        if (
            $db = \ibase_connect(
                'localhost:/var/www/parp/PARP_KP.FDB',
                'user',
                'pass'
            )
        ) {
            $result = \ibase_query($db, $sql);

            $count = 0;
            while ($row = ibase_fetch_assoc($result)) {
                $count++;
                $rows[] = $row;
            }

            \ibase_close($db);

            return $rows;
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
            $getter = 'get' . ucfirst($p);
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
    public function przejrzyjnowychAction(Request $request)
    {
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();
        $nowi = $em->getRepository(DaneRekord::class)->findNewPeople();
        $data = [];
        foreach ($nowi as $dr) {
            $users = $this->getUserFromAllAD($dr);
            $departament =
                $em->getRepository(Departament::class)->findOneByNameInRekord($dr->getDepartament());
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
            $d['konto_wylaczone'] = false;
            $d['departament'] = (null != $departament) ? $departament : new Departament();

            if (false !== strpos(current($users)['useraccountcontrol'], 'ACCOUNTDISABLE')) {
                $d['konto_wylaczone'] = true;
            }

            $data[] = $d;
        }

        // Pobieramy listę Sekcji
        $sectionsEntity =
            $this->getDoctrine()->getRepository(Section::class)->findBy(array(), array('name' => 'asc'));
        $sections = array();
        foreach ($sectionsEntity as $tmp) {
            $dep = $tmp->getDepartament() ? $tmp->getDepartament()->getShortname() : 'bez departamentu';
            $sections[$dep][$tmp->getShortname()] = $tmp->getName();
        }

        $adapter = new ArrayAdapter($data);
        $page = (int)($request->query->get('page') == null ?  1 : $request->query->get('page'));
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(5);
        $pagerfanta->setCurrentPage($page);

        return $this->render('ParpMainBundle:DaneRekord:przejrzyjNowych.html.twig', [
                    'data' => $pagerfanta,
                    //'data' => $data,
                    'przelozeni' => $ldap->getPrzelozeni(),
                    'sekcje' => $sections,
        ]);

//        return $this->render(
//            'ParpMainBundle:DaneRekord:przejrzyjNowych.html.twig',
//            [
//                'data'       => $data,
//                'przelozeni' => $ldap->getPrzelozeni(),
//                'sekcje'     => $sections,
//                'my_pager' => $pagerfanta,
//            ]
//        );
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
        $fullname = $dr->getNazwisko() . ' ' . $dr->getImie();
        //echo "<br> szuka ".$fullname;
        $aduser = $ldap->getUserFromAD(null, $fullname, null, 'wszyscyWszyscy');
        if (count($aduser) > 0) {
            foreach ($aduser as $u) {
                if (!isset($ret[$u['samaccountname']])) {
                    $ret[$u['samaccountname']] = $u;
                }
            }
        }
        $fullname = $dr->getNazwisko() . ' (*) ' . $dr->getImie();
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

        if (false !== strpos(current($userFromAD)['useraccountcontrol'], 'ACCOUNTDISABLE')) {
            return new Response();
        }
        $objectManager = $this->getDoctrine()->getManager();
        /** @var DaneRekord $daneRekord */
        $daneRekord = $objectManager->getRepository(DaneRekord::class)->find($id);
        $poprzednieDane = explode(' ', current($userFromAD)['name']);
        $daneRekord->setStaticStatusNumber($daneRekord->getNewUnproccessed());
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
                    if ($userFromAD[0]['name'] !== $daneRekord->getNazwisko() . ' ' . $daneRekord->getImie()) {
                        $changeSet['imie'] = 1;
                        $changeSet['nazwisko'] = 1;
                    }

                    $depName = $objectManager
                        ->getRepository(Departament::class)
                        ->findOneBy([
                            'nameInRekord' => $daneRekord->getDepartament()
                        ]);

                    if (null !== $depName) {
                        $depName = $depName->getName();
                    }
                    if ($userFromAD[0]['department'] !== $depName) {
                        $changeSet['department'] = 1;
                    }
                    if ($userFromAD[0]['title'] !== $daneRekord->getStanowisko()) {
                        $changeSet['stanowisko'] = 1;
                    }
                    if ($dane['form']['info'] !== '' && $userFromAD[0]['division'] !== $dane['form']['info']) {
                        $zmieniamySekcje = true;
                    }
                }
            } else {
                $nowy = true;
                //nowy user
                $changeSet = ['imie' => 1, 'nazwisko' => 1, 'departament' => 1, 'stanowisko' => 1];
                $zmieniamySekcje = true;
            }

            $resetDoPodstawowych = false;
            if (isset($changeSet['departament']) || $zmieniamySekcje) {
                $resetDoPodstawowych = true;
            }

            if ($userFromAD && !$this->czyStanowiskoZtejSamejGrupy($userFromAD[0]['title'], $daneRekord->getStanowisko())) {
                $resetDoPodstawowych = true;
            }

            $entry = $this->utworzEntry($objectManager, $daneRekord, $changeSet, $nowy, $poprzednieDane, $resetDoPodstawowych);

            if (!$nowy && $daneRekord->getNewUnproccessed() === 2 && true === $resetDoPodstawowych) {
                // Jeśli nie jest nowy i istnieje w Rekordzie
                //trzeba odebrac stare
                $oldDepartament =
                    $this->getDoctrine()
                        ->getRepository(Departament::class)
                        ->findOneByName($userFromAD[0]['department']);
                $section = $objectManager->getRepository(Section::class)->findOneByShortname($userFromAD[0]['division']);
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
                    ->getRepository(Departament::class)
                    ->findOneByNameInRekord($daneRekord->getDepartament());
            $section = $objectManager->getRepository(Section::class)->findOneByShortname($dane['form']['info']);
            if (null === $departament) {
                $departament = $section->getDepartament();
            }

            if (empty($entry->getDistinguishedname())) {
                $tab = explode('.', $this->container->getParameter('ad_domain'));
                $adOu = $this->container->getParameter('ad_ou');
                $adDn = 'CN=' . $entry->getCn() . ',OU=' . $departament->getShortname() . ',' . $adOu . ',DC=' . $tab[0] .
                    ',DC=' . $tab[1];
                $entry->setDistinguishedname($adDn);
            }

            $entry->setDepartment($departament->getName());

            if (true === $resetDoPodstawowych) {
                $grupyNaPodstawieSekcjiOrazStanowiska =
                $ldapService->getGrupyUsera(
                    ['title' => $daneRekord->getStanowisko()],
                    $departament->getShortname(),
                    ($section ? $section->getShortname() : '')
                );
                $entry->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, '+');
            }

            if ($dane['form']['accountExpires'] !== '') {
                $v = \DateTime::createFromFormat('Y-m-d', $dane['form']['accountExpires']);
                $entry->setAccountExpires($v);
            }
            if ($dane['form']['info'] !== '') {
                $entry->setDivision($dane['form']['info']);
                $entry->setInfo($section->getName());
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
                $userzasoby = $objectManager->getRepository(UserZasoby::class)
                    ->findBy(['samaccountname' => $samaccountname]);

                foreach ($userzasoby as $uz) {
                    $zasob = $objectManager->getRepository(Zasoby::class)->find($uz->getZasobId());
                    if (
                        $uz->getZasobId()
                        && !in_array($zasob->getAdministratorZasobu(), $administratorzy, true)
                    ) {
                        // Pobieramy administratora zasobu
                        $administratorzy[] = $zasob->getAdministratorZasobu();
                    }
                }

                if (isset($changeSet['stanowisko']) && 1 === count($changeSet)) {
                    $mailerService =  $this->get('parp.mailer');
                    $mailerService
                        ->disableFlush()
                        ->sendEmailZmianaStanowiska($userFromAD[0], $daneRekord->getStanowisko(), $departament->getDyrektor())
                    ;
                }
            } else {
                //['departament', 'data_nadania_uprawnien_poczatkowych']
                $manager = ($dane['form']['manager'] !== '') ? $dane['form']['manager'] : '';
                $now = new \Datetime();
                $dane = [
                    'imie_nazwisko'                       => $daneRekord->getImie() . ' ' . $daneRekord->getNazwisko(),
                    'login'                               => $daneRekord->getLogin(),
                    'departament'                         => $departament->getName(),
                    'data_nadania_uprawnien_poczatkowych' => $now->format('Y-m-d'),
                    'odbiorcy'                            => [$manager],
                ];

                $this->get('parp.mailer')->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEIMPORT, $dane);
                $this->get('parp.mailer')
                    ->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIENADANIEUPRAWNIEN, $dane);

                $dane['tytul'] = $daneRekord->getImie() . ' ' . $daneRekord->getNazwisko();
                $dane['stanowisko'] = $daneRekord->getStanowisko();
                $dane['umowa_od'] = $daneRekord->getUmowaOd();

                // e-mail do dyrektora z linkiem do formularza GLPI-BA:
                $dane['odbiorcy'] = [$departament->getDyrektor() . '@parp.gov.pl'];
                $this->get('parp.mailer')->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEFORM, $dane);

                $dane['odbiorcy'] = [ParpMailerService::EMAIL_DO_GLPI];
                // wysłanie zgłoszenia do [BI]:
                $this->get('parp.mailer')->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEBI, $dane);
                // dodaktowe zgłoszenie do [BI] dla administratorów serwera Exchange:
                $this->get('parp.mailer')->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEBIEXCHANGE, $dane);
            }

            $daneRekord->setNewUnproccessed(0);
            $objectManager->flush();
        }

        return new Response();
    }

    /**
     * Porównujemy czy stnaowiska należą do tej samej grupy
     *
     * @param string $stanowiskoStare
     * @param string $stanowiskoNowe
     *
     * @return bool
     */
    public function czyStanowiskoZtejSamejGrupy(string $stanowiskoStare, string $stanowiskoNowe): bool
    {
        $stanowiska = $this->getDoctrine()->getRepository(Position::class)->findBy([
            'name' => [$stanowiskoStare, $stanowiskoNowe],
        ]);

        if (count($stanowiska) === 1 && !($stanowiska[0]->getGroup() instanceof PositionGroups)) {
            return true;
        }

        return ($stanowiska[0]->getGroup() === $stanowiska[1]->getGroup());
    }

    /**
     * @Route("/usunUzytkownikaZKolejki/{id}", name="usunUzytkownikaZKolejki", defaults={})
     * @Security("has_role('PARP_ADMIN') or has_role('PARP_BZK_1')")
     * @Method("POST")
     * @param Request $request
     * @param         $id
     *
     * @return Response
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function usunUzytkownikaZKolejkiAction(Request $request, $id)
    {
        $id = (int) $id;
        $em = $this->getDoctrine()->getManager();
        $daneRekord = $em->getRepository(DaneRekord::class)->find($id);

        // 7 - nieaktywne, nieobsługiwane rekordy
        $daneRekord->setNewUnproccessed(7);
        $em->persist($daneRekord);
        $em->flush();

        return new Response($id);
    }

    /**
     * Umożliwia przeniesienie pracownika do problematycznych
     *
     * @Route("/przenies_do_problematycznych/{UserRekordId}", name="przeniesDoProblematycznych", defaults={})
     *
     * @Security("has_role('PARP_ADMIN') or has_role('PARP_BZK_2')")
     *
     * @Method("GET")
     *
     * @param Request $request
     * @param         $UserRekordId
     *
     * @return Response
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function przeniesDoProblematycznychAction(Request $request, $UserRekordId)
    {
        $UserRekordId = (int) $UserRekordId;
        $entityManager = $this->getDoctrine()->getManager();
        $daneRekord = $entityManager->getRepository(DaneRekord::class)->find($UserRekordId);

        // 2 - zmiana departamentu/sekcji/przelozonego, wrzucenie pracownika do problematycznych
        $daneRekord->setNewUnproccessed(2);
        $entityManager->persist($daneRekord);
        $entityManager->flush();

        $this->addFlash('warning', 'Pracownik został dodany do listy.');

        return $this->redirect($this->generateUrl('przejrzyjnowych'));
    }
}
