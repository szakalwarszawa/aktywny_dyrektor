<?php

namespace ParpV1\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use ParpV1\MainBundle\Entity\ImportSekcjeUser;
use ParpV1\MainBundle\Entity\DaneRekord;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\Section;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Entity\UserZasoby;

/**
 * Klaster controller.
 *
 * @Route("/reorganizacja")
 */
class ReorganizacjaParpController extends Controller
{

    /**
     * Lists all Klaster entities.
     *
     * @Route("/nadajUprawnieniaPoczatkoweIzmienOU2naPodstawieRekordIAkD", name="nadajUprawnieniaPoczatkoweIzmienOU2naPodstawieRekordIAkD", defaults={})
     * @Method("GET")
     */
    public function nadajUprawnieniaPoczatkoweIzmienOU2naPodstawieRekordIAkDAction()
    {
        $titles = [];
        $mapowanieNazwisk = [
            'Skubiszewska Aleksandra' => 'Skubiszewska-Nietz Aleksandra'
        ];
        $pomijajOsoby = [
            'anita_szczykutowicz',
            'beata_bartnicka',
            'katarzyna_czuprynska',
            'marlena_plochocka',
            'Beata_Machowiak',
            'katarzyna_stasinska',
            'karolina_gromulska',
            'ewa_plaskota',
            'Edyta_Dominiak',
            'Katarzyna_Pytel',
            'katarzyna_sosnowska',
            'milena_zeber',
            'monika_dylag'


        ];
/*


        ];
*/
        $nadajAll = false;

        $zmieniajOU = false || $nadajAll;
        $zmieniajGrupy = false || $nadajAll;
        $zmieniajSekcjewIDescriptionAD = false || $nadajAll;

        $poprawNieZnaleziono = true;


        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        $users = $ldap->getAllFromAD(false, false, "2016");
        $tab = explode(".", $this->container->getParameter('ad_domain'));
        $bledy = [];
        $ok = 0;
        $brakImport = 0;
        $brakRekord = 0;
        $okad = 0;
        foreach ($users as $u) {
            if (!in_array($u['samaccountname'], $pomijajOsoby)) {
                $u['name'] = isset($mapowanieNazwisk[$u['name']]) ? $mapowanieNazwisk[$u['name']] : $u['name'];
                $name1 = trim(mb_strtoupper($u['name']));
                $name2 = $this->get('samaccountname_generator')->ADnameToRekordName($u['name']);
                $import = $em->getRepository(ImportSekcjeUser::class)->findBy(['pracownik' => $name1]);
                if (count($import) == 0) {
                    $import = $em->getRepository(ImportSekcjeUser::class)->findBy(['pracownik' => $name2]);
                }
                $entry = $em->getRepository(Entry::class)->findNowaSekcjaTYLKOuzywaneWreorganizacji2016($u['samaccountname']);
                //die(".".count($entry).".".$u['samaccountname'].".");
                $imieNazwisko = $this->get('samaccountname_generator')->ADnameToRekordNameAsArray($u['name']);
                $danerekord = $em->getRepository(DaneRekord::class)->findOneBy(['imie' => $imieNazwisko[1], 'nazwisko' => $imieNazwisko[0]]);
                $rekordDepartament = "_Nie_ma_departamentu_rekord_";
                $rekordTitle = $u['title'];
                if (!$danerekord) {
                    $bledy[] = [
                        'blad' => 'Nie znalazl danych w systemie rekord!!!',
                        'user' => $u['samaccountname'],
                        'name' => $u['name'],
                        'rekordDepartament' => $rekordDepartament,
                        'coMa' => $imieNazwisko[0].", ".$imieNazwisko[1],
                        'coPowinienMiec' => $imieNazwisko,
                        'info' => '',
                    ];
                    $brakRekord++;
                } else {
                    $rekordDepartament = $danerekord->getDepartament();
                    $rekordTitle = $this->get('samaccountname_generator')->parseStanowisko($danerekord->getStanowisko());
                    $titles[$rekordTitle] = $rekordTitle;
                }
                if (count($entry) == 0) {
                    $bledy[] = [
                        'blad' => 'nie znalazl nowej sekcji w AkD!!!',
                        'user' => $u['samaccountname'],
                        'name' => $u['name'],
                        'rekordDepartament' => $rekordDepartament,
                        'coMa' => "'".$name1."'",
                        'coPowinienMiec' => "'".$name2."'",
                        'info' => '',
                    ];
                    $brakImport++;
                }/*
    elseif(count($entry) > 1){

                    $bledy[] = [
                        'blad' => 'Znalazl za duzo wpisow: '.count($entry).' nowych sekcji w AkD!!!',
                        'user' => $u['samaccountname'],
                        'name' => $u['name'],
                        'coMa' => $name1,
                        'coPowinienMiec' => $name2,
                        'info' => '',
                    ];
                }
    */
                {
                if (count($entry) > 1) {
                    $sekcje = [];
                    foreach ($entry as $s) {
                        $sekcje [] = $s->getInfo();
                    }
                    $bledy[] = [
                    'blad' => 'Znalazl za duzo wpisow: '.count($entry).' nowych sekcji w AkD!!!',
                    'user' => $u['samaccountname'],
                    'name' => $u['name'],
                    'rekordDepartament' => $rekordDepartament,
                    'coMa' => $name1,
                    'coPowinienMiec' => $name2,
                    'info' => 'sekcje: "'.implode(",", $sekcje).'"!!!',
                    ];
                }
                    //jest ok znalazl
                    $ok++;
                    $coMa = [
                        'dn' => $this->get('samaccountname_generator')->standarizeString($this->getOUDNfromUserDN($u)),
                        'description' => $u['description'],//departament skrot
                        'department' => $u['department'],//pelna nazwa dep
                        'extensionAttribute14' => $u['extensionAttribute14'],//skrot db
                        'info' => $u['info'],//sekcja pelna nazwa
                        'division' => $u['division'], //skrot sekcji
                        'title' => $u['title']
                    ];
                    $typPracownika = "nieznanyTyp";
                    if (count($import) == 1) {
                        $typPracownika = $import[0]->getTypPracownika();
                    }
                    $nieZnaleziony = true;
                    $newDepartamentSkrot = "___NIE_ZNALAZL (typPracownika: ".$typPracownika.")___"; //$entry[0]->getDepartamentSkrot();
                    $newDepartamentNazwa = "___NIE_ZNALAZL (typPracownika: ".$typPracownika.")___"; //$entry[0]->getDepartament();
                    if ($u['samaccountname'] == 'kamila_sek') {
                        //echo "<pre>"; var_dump($danerekord); die();
                    }
                    if ($danerekord) {
                        //wtedy bierzemy jednak z rekorda!!!
                        $departament = $em->getRepository(Departament::class)->findOneBy(['nameInRekord' => $danerekord->getDepartament(), 'nowaStruktura' => true]);
                        if (!$departament && $danerekord->getDepartament() > 500) {
                            die("Nie mam departamentu ".$danerekord->getDepartament());
                        }
                        if ($departament) {
                            $newDepartamentSkrot = $departament->getShortname();
                            $newDepartamentNazwa = $departament->getName();
                            $nieZnaleziony = false;
                        } else {
                            $nieZnaleziony = true;
                            $newDepartamentSkrot .= "na podstawie id ".$danerekord->getDepartament()." ___"; //$entry[0]->getDepartamentSkrot();
                            $newDepartamentNazwa .= "na podstawie id ".$danerekord->getDepartament()." ___"; //$entry[0]->getDepartament();
                        }
                    } else {
                            $nieZnaleziony = true;

                            $newDepartamentSkrot .= " w rekordzie  ___"; //$entry[0]->getDepartamentSkrot();
                            $newDepartamentNazwa .= " w rekordzie  ___"; //$entry[0]->getDepartament();
                    }
                    $sekcja = count($entry) > 0 ? $entry[0]->getInfo() : $u['info'];
                    $section = $em->getRepository(Section::class)->findOneBy(['name' => $sekcja]);
                    $sekcjaSkrot = $section ? $section->getShortname() : "";
                    $coPowinienMiec = [
                        'dn' => $this->get('samaccountname_generator')->standarizeString('OU=' . $newDepartamentSkrot . ',OU=Zespoly_2016,OU=PARP Pracownicy,DC=' . $tab[0] . ',DC=' . $tab[1]),
                        'description' => $newDepartamentSkrot,//departament skrot
                        'department' => $newDepartamentNazwa,//pelna nazwa dep
                        'extensionAttribute14' => $newDepartamentSkrot,//skrot db
                        'info' => $sekcja,//sekcja pelna nazwa
                        'division' => $sekcjaSkrot, //skrot sekcji
                        'title' => $rekordTitle
                    ];
                    $roznica = array_diff_assoc($coPowinienMiec, $coMa);
                    //czasem to wylaczam bo za duzo bledow
                    if (count($roznica) > 0) {
                        //var_dump($coPowinienMiec, $coMa, $roznica); die();
                        $bledy[] = [
                            'blad' => 'Nie wszystko sie zgadza w AD!!!',
                            'user' => $u['samaccountname'],
                            'name' => $u['name'],
                            'rekordDepartament' => $rekordDepartament,
                            'coMa' => $coMa,
                            'coPowinienMiec' => $coPowinienMiec,
                            'info' => $roznica,
                        ];
                        $roznicaDn = isset($roznica['dn']) ? $roznica['dn'] : false;
                        if (isset($roznica['dn'])) {
                            unset($roznica['dn']);
                        }

                        if ($roznicaDn && $zmieniajOU) {
                            //zmiana ou
                            $b = $ldapAdmin->ldapRename($ldapconn, $u['distinguishedname'], "CN=" . $u['name'], $roznicaDn, true);
                            $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                            //var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                            echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapRename $ldapstatus ".$u['distinguishedname']."</span> \r\n<br>";
                        }
                        if (count($roznica) > 0 && $zmieniajSekcjewIDescriptionAD) {
                            foreach ($roznica as $k => $v) {
                                if ($v == "") {
                                    unset($roznica[$k]);
                                }
                            }
                            //zmiana danych
                            //unset($roznica['dn']);
                            $res = $ldapAdmin->ldapModify($ldapconn, $u['distinguishedname'], $roznica);
                            $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                            //var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                            echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModify $ldapstatus ".$u['distinguishedname']."</span> \r\n<br>";
                        }
                        if ($zmieniajGrupy) {
                            $grupy = $this->get('ldap_service')->getGrupyUsera($u, $newDepartamentSkrot, $sekcjaSkrot);
                            foreach ($grupy as $g) {
                                $dn = $u['distinguishedname'];
                                $grupa = $ldapAdmin->getGrupa($g);
                                $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                                //var_dump($g, $addtogroup, array('member' => $dn ));
                                $ldapAdmin->ldapModAdd($ldapconn, $addtogroup, array('member' => $dn ));
                                $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                                echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModAdd $ldapstatus dla osoby ".$addtogroup." ".$dn."</span>\r\n<br>";
                            }
                        }

                        if ($poprawNieZnaleziono && $nieZnaleziony) {
                            $ou = $this->get('ldap_service')->getOUfromDN($u);
                            $dep = $em->getRepository(Departament::class)->findOneBy(['shortname' => $ou, 'nowaStruktura' => true]);
                            $zmiany = [
                                'description' => $ou,
                                'department' => $dep->getName(),
                                'extensionAttribute14' => $ou,
                                //'division' => ''
                            ];

                            $res = $ldapAdmin->ldapModify($ldapconn, $u['distinguishedname'], $zmiany);
                            $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                            //var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                            echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModify $ldapstatus ".$u['distinguishedname']."</span> \r\n<br>";
                        }
                    } else {
                        $okad++;
                    }


                    }
            }
        }
        //echo "<pre>"; print_r($titles); die();
        $bledy[] = [
            'blad' => 'Przetworzone rekordy '.count($users),
            'user' => 'Wpisow ktore maja rekordy w imporcie sekcji '.$ok,
            'name' => 'Wpisow ktore nie maja rekordu w imporcie '.$brakImport,
            'coMa' => 'Wpisow z bledami '.count($bledy),
            'coPowinienMiec' => 'Zgadza sie w AD '.$okad,
            'info' => 'Braki w rekord '.$brakRekord,
        ];
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $bledy]);
    }


    /**
     * Lists all Klaster entities.
     *
     * @Route("/nadajUprawnieniaPoczatkoweIzmienOUnaPodstawieRekordIExcel", name="nadajUprawnieniaPoczatkoweIzmienOUnaPodstawieRekordIExcel", defaults={})
     * @Method("GET")
     */
    public function nadajUprawnieniaPoczatkoweIzmienOUnaPodstawieRekordIExcelAction()
    {
        $zmieniajOU = false;
        $zmieniajGrupy = false;
        $zmieniajSekcjewIDescriptionAD = false;
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        $users = $ldap->getAllFromAD(false, false, "2016");
        $tab = explode(".", $this->container->getParameter('ad_domain'));
        $bledy = [];
        $ok = 0;
        $brakImport = 0;
        $brakRekord = 0;
        $okad = 0;
        foreach ($users as $u) {
            $name1 = trim(mb_strtoupper($u['name']));
            $name2 = $this->get('samaccountname_generator')->ADnameToRekordName($u['name']);
            $import = $em->getRepository(ImportSekcjeUser::class)->findBy(['pracownik' => $name1]);
            if (count($import) == 0) {
                $import = $em->getRepository(ImportSekcjeUser::class)->findBy(['pracownik' => $name2]);
            }
            $imieNazwisko = $this->get('samaccountname_generator')->ADnameToRekordNameAsArray($u['name']);
            $danerekord = $em->getRepository(DaneRekord::class)->findOneBy(['imie' => $imieNazwisko[1], 'nazwisko' => $imieNazwisko[0]]);
            if (!$danerekord) {
                $bledy[] = [
                    'blad' => 'Nie znalazl danych w systemie rekord!!!',
                    'user' => $u['samaccountname'],
                    'name' => $u['name'],
                    'coMa' => $imieNazwisko[0].", ".$imieNazwisko[1],
                    'coPowinienMiec' => $imieNazwisko,
                    'info' => '',
                ];
                $brakRekord++;
            }
            if (count($import) == 0) {
                $bledy[] = [
                    'blad' => 'nie znalazl usera w imporcie!!!',
                    'user' => $u['samaccountname'],
                    'name' => $u['name'],
                    'coMa' => "'".$name1."'",
                    'coPowinienMiec' => "'".$name2."'",
                    'info' => '',
                ];
                $brakImport++;
            } elseif (count($import) > 1) {
                $bledy[] = [
                    'blad' => 'Znalazl za duzo wpisow: '.count($import).'!!!',
                    'user' => $u['samaccountname'],
                    'name' => $u['name'],
                    'coMa' => $name1,
                    'coPowinienMiec' => $name2,
                    'info' => '',
                ];
            } else {
                //jest ok znalazl
                $ok++;
                $coMa = [
                    'dn' => $this->get('samaccountname_generator')->standarizeString($this->getOUDNfromUserDN($u)),
                    'description' => $u['description'],//departament skrot
                    'department' => $u['department'],//pelna nazwa dep
                    'extensionAttribute14' => $u['extensionAttribute14'],//skrot db
                    'info' => $u['info'],//sekcja pelna nazwa
                    'division' => $u['division']//skrot sekcji
                ];
                $newDepartamentSkrot = $import[0]->getDepartamentSkrot();
                $newDepartamentNazwa = $import[0]->getDepartament();
                if ($danerekord) {
                    //wtedy bierzemy jednak z rekorda!!!
                    $departament = $em->getRepository(Departament::class)->findOneBy(['nameInRekord' => $danerekord->getDepartament(), 'nowaStruktura' => true]);
                    if (!$departament && $danerekord->getDepartament() > 500) {
                        die("Nie mam departamentu ".$danerekord->getDepartament());
                    }
                    if ($departament) {
                        $newDepartamentSkrot = $departament->getShortname();
                        $newDepartamentNazwa = $departament->getName();
                    }
                }
                $coPowinienMiec = [
                    'dn' => $this->get('samaccountname_generator')->standarizeString('OU=' . $newDepartamentSkrot . ',OU=Zespoly_2016,OU=PARP Pracownicy,DC=' . $tab[0] . ',DC=' . $tab[1]),
                    'description' => $newDepartamentSkrot,//departament skrot
                    'department' => $newDepartamentNazwa,//pelna nazwa dep
                    'extensionAttribute14' => $newDepartamentSkrot,//skrot db
                    'info' => $import[0]->getSekcja(),//sekcja pelna nazwa
                    'division' => $import[0]->getSekcjaSkrot()//skrot sekcji
                ];
                $roznica = array_diff_assoc($coPowinienMiec, $coMa);
                //czasem to wylaczam bo za duzo bledow
                if (count($roznica) > 0) {
                    //var_dump($coPowinienMiec, $coMa, $roznica); die();
                    $bledy[] = [
                        'blad' => 'Nie wszystko sie zgadza w AD!!!',
                        'user' => $u['samaccountname'],
                        'name' => $u['name'],
                        'coMa' => $coMa,
                        'coPowinienMiec' => $coPowinienMiec,
                        'info' => $roznica,
                    ];
                    $roznicaDn = isset($roznica['dn']) ? $roznica['dn'] : false;
                    if (isset($roznica['dn'])) {
                        unset($roznica['dn']);
                    }

                    if ($roznicaDn && $zmieniajOU) {
                        //zmiana ou
                        $b = $ldapAdmin->ldapRename($ldapconn, $u['distinguishedname'], "CN=" . $u['name'], $roznicaDn, true);
                        $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                        //var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                        echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapRename $ldapstatus ".$u['distinguishedname']."</span> \r\n<br>";
                    }
                    if (count($roznica) > 0 && $zmieniajSekcjewIDescriptionAD) {
                        foreach ($roznica as $k => $v) {
                            if ($v == "") {
                                unset($roznica[$k]);
                            }
                        }
                        //zmiana danych
                        //unset($roznica['dn']);
                        $res = $ldapAdmin->ldapModify($ldapconn, $u['distinguishedname'], $roznica);
                        $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                        //var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                        echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModify $ldapstatus ".$u['distinguishedname']."</span> \r\n<br>";
                    }
                    if ($zmieniajGrupy) {
                        $grupy = $this->get('ldap_service')->getGrupyUsera($u, $newDepartamentSkrot, $import[0]->getSekcjaSkrot());
                        foreach ($grupy as $g) {
                            $dn = $u['distinguishedname'];
                            $grupa = $ldapAdmin->getGrupa($g);
                            $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                            //var_dump($g, $addtogroup, array('member' => $dn ));
                            $ldapAdmin->ldapModAdd($ldapconn, $addtogroup, array('member' => $dn ));
                            $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                            echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModAdd $ldapstatus dla osoby ".$addtogroup." ".$dn."</span>\r\n<br>";
                        }
                    }
                } else {
                    $okad++;
                }
            }
        }
        $bledy[] = [
            'blad' => 'Przetworzone rekordy '.count($users),
            'user' => 'Wpisow ktore maja rekordy w imporcie sekcji '.$ok,
            'name' => 'Wpisow ktore nie maja rekordu w imporcie '.$brakImport,
            'coMa' => 'Wpisow z bledami '.count($bledy),
            'coPowinienMiec' => 'Zgadza sie w AD '.$okad,
            'info' => 'Braki w rekord '.$brakRekord,
        ];
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $bledy]);
    }
    /**
     * Lists all Klaster entities.
     *
     * @Route("/nadajUprawnieniaPoczatkoweIzmienOU", name="nadajUprawnieniaPoczatkoweIzmienOU", defaults={})
     * @Method("GET")
     */
    public function nadajUprawnieniaPoczatkoweIzmienOUAction()
    {
        $zmieniajOU = false;
        $zmieniajGrupy = false;
        $zmieniajSekcjewIDescriptionAD = false;

        $nieMialemWExeluSekcji = [];
        $mapowanieDep = [
            '522' => '523'
        ];

        $tylkoTychUserow = [
            //'piotr_zerhau',
            //'artur_marszalek',
            //'tomasz_swiercz',
            'kamil_jakacki',
            //'martyna_aleksjew'
        ];

        $tylkoTychUserow = [];//teraz jedzie wszystkich
        $tylkoTeBD = 0;//519;//tylko ten db , chyba ze 0


        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_admin_service');
        $ldap->output = $this;
        $ldapconn = $ldap->prepareConnection();
        $c = new ImportRekordDaneController();
        $c->setContainer($this->container);
        $sql = $c->getSqlDoImportu();
        $rows = $c->executeQuery($sql);

        $noweDepartamenty = [];
        $tab = explode(".", $this->container->getParameter('ad_domain'));

        foreach ($rows as $row) {
            if (isset($mapowanieDep[$row['DEPARTAMENT']])) {
                $row['DEPARTAMENT'] = $mapowanieDep[$row['DEPARTAMENT']];
            }
            if ($row['DEPARTAMENT'] > 500 && ($tylkoTeBD == "" || $tylkoTeBD == $row['DEPARTAMENT'])) {
                //$login = $this->get('samaccountname_generator')->generateSamaccountname($c->parseValue($row['IMIE']), $c->parseValue($row['NAZWISKO']));
                $danerekord = $em->getRepository(DaneRekord::class)->findOneBySymbolRekordId($c->parseValue($row['SYMBOL']));
                if (!$danerekord) {
                    die("Nie moge znalezc osoby !!! ".trim($row['NAZWISKO'])." ".trim($row['IMIE'])." - ".$row['SYMBOL']);
                }
                $departament = $em->getRepository(Departament::class)->findOneBy(['nameInRekord' => $c->parseValue($row['DEPARTAMENT']), 'nowaStruktura' => true]);
                $prac = mb_strtoupper($danerekord->getNazwisko()." ".$danerekord->getImie());//$c->parseValue($row['NAZWISKO'], false)." ".trim($c->parseValue['IMIE'], false);
                $sekcja = $em->getRepository(ImportSekcjeUser::class)->findOneBy(['pracownik' => $prac]);
                $login = $danerekord->getLogin();
                if (count($tylkoTychUserow) == 0 || in_array($login, $tylkoTychUserow)) {
                    $aduser = $ldap->getUserFromAD($login);
                    $sekcjaName = "ND";
                    if (!$sekcja) {
                        $nieMialemWExeluSekcji[$login] = $prac;
                        //die("Nie mam sekcji dla usera $login '".$prac."'");
                    } else {
                        //TODO: Nadawac sekcje w polu division !!!
                        //oraz dorzucac w uprawnieniach
                        $sekcjaName = $sekcja->getSekcjaSkrot();

                        if ($zmieniajSekcjewIDescriptionAD) {
                            $zmiana = [
                                //'info' => $sekcja->getSekcja(),
                                //'division' => $sekcja->getSekcjaSkrot(),
                                'description' => $departament->getShortname(),
                                'department' => $departament->getName(),
                                'extensionAttribute14' => $departament->getShortname(),
                                //'extensionAttribute15' => ''//stanowisko //$departament->getShortname(),
                            ];

                            if ($sekcjaName != "") {
                                $zmiana['info'] =  $sekcja->getSekcja();
                                $zmiana['division'] =  $sekcja->getSekcjaSkrot();
                            }
                            //$zmiana['info'] = '';

                            //die($aduser[0]['distinguishedname']);
                            $res = $ldap->ldapModify($ldapconn, $aduser[0]['distinguishedname'], $zmiana);
                            $ldapstatus = $ldap->ldapError($ldapconn);
                            echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModify $ldapstatus dla osoby ".$aduser[0]['distinguishedname']."</span> \r\n<br>";
                        }
                    }
                    if (!$departament) {
                        echo "<pre>";
                        print_r($aduser[0]);
                        die("Nie mam departamentu dla osoby !!!");
                    }
                    $grupy = $this->get('ldap_service')->getGrupyUsera($aduser[0], $departament->getShortname(), $sekcjaName);
                    if (count($aduser) > 0) {
                        unset($aduser[0]['thumbnailphoto']);
                    }
                    $noweDepartamenty[] = [
                    'row' => $row,
                    'login' => $login,
                    'aduser' => count($aduser) > 0 ? $aduser[0] : [],
                    'sekcja' => ($sekcja ? $sekcja->getSekcjaSkrot() : "ND"),
                    'entry' => [
                    'departament' => $departament->getName(), //'', //nowy departament nazwa
                    'distinguishedname' => $aduser[0]['distinguishedname'],
                    'fromWhen' => new \Datetime(),
                    'samaccountname' => $login,
                    'grupy' => $grupy
                    ]
                    ];
                    $e = new \ParpV1\MainBundle\Entity\Entry($this->getUser()->getUsername());
                    $e->setFromWhen(new \Datetime());
                    $e->setSamaccountname($login);
                    $e->setDistinguishedname($aduser[0]['distinguishedname']);
                    $e->setDepartment($departament->getName());
                    $em->persist($e);

                    //CN=Dyrektor Aktywny,OU=BI,OU=Zespoly,OU=PARP Pracownicy,DC=AD,DC=TEST




                    if ($zmieniajGrupy) {
                        foreach ($grupy as $g) {
                            $dn = $aduser[0]['distinguishedname'];
                            $grupa = $ldap->getGrupa($g);
                            $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                            //var_dump($g, $addtogroup, array('member' => $dn ));
                            $ldap->ldapModAdd($ldapconn, $addtogroup, array('member' => $dn ));
                            $ldapstatus = $ldap->ldapError($ldapconn);
                            echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModAdd $ldapstatus dla osoby ".$addtogroup." ".$dn."</span>\r\n<br>";
                        }
                    }

                    $parent = 'OU=' . $departament->getShortname() . ',OU=Zespoly_2016,OU=PARP Pracownicy,DC=' . $tab[0] . ',DC=' . $tab[1];

                    $cn = $aduser[0]['name'];
                    if ($zmieniajOU) {
                        //zmieniam OU !!!!!
                        $b = $ldap->ldapRename($ldapconn, $aduser[0]['distinguishedname'], "CN=" . $cn, $parent, true);
                        $ldapstatus = $ldap->ldapError($ldapconn);
                        //var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                        echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapRename $ldapstatus ".$aduser[0]['distinguishedname']."</span> \r\n<br>";
                    }
                }
            }
        }
        echo "<pre>";
        print_r($noweDepartamenty);
        die();
        //$em->flush();//nie zapisuje tego
    }
    public function writeln($txt)
    {
        echo "<br>".$txt."<br>";
    }

    /**
     * @Route("/importujSekcje", name="importujSekcje")
     */
    public function importujSekcjeAction(Request $request)
    {

        $form = $this->createFormBuilder()->add('plik', FileType::class, array(
                    'required' => false,
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array('class' => 'filestyle',
                        'data-buttonBefore' => 'false',
                        'data-buttonText' => 'Wybierz plik',
                        'data-iconName' => 'fas fa-file-excel-o',
                    ),
                    'constraints' => array(
                        new NotBlank(array('message' => 'Nie wybrano pliku')),
                        new File(array(
                            'maxSize' => 1024 * 1024 * 10,
                            'maxSizeMessage' => 'Przekroczono rozmiar wczytywanego pliku',
                            'mimeTypes' => array('text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/msexcel', 'application/xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                            'mimeTypesMessage' => 'Niewłaściwy typ plku. Proszę wczytac plik z rozszerzeniem csv'
                                )),
                    ),
                    'mapped' => false,
                ))
                ->add('wczytaj', SubmitType::class, array('attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                )))
                ->add('typPliku', ChoiceType::class, ['choices' => ['sekcje' => 'sekcje', 'zasoby' => 'zasoby']])
                ->getForm();

        $form->handleRequest($request);
        if ($request->getMethod() == 'POST') {
            if ($form->isValid()) {
                $file = $form->get('plik')->getData();
                $name = $file->getClientOriginalName();

                //$path = $file->getClientPathName();
                //var_dump($file->getPathname());
                // var_dump($name);
                switch ($form->get('typPliku')->getData()) {
                    case "sekcje":
                        $ret = $this->wczytajPlikSekcje($file);
                        break;
                    case "zasoby":
                        $ret = $this->wczytajPlikZasoby($file);
                        break;
                }
                if ($form->get('typPliku')->getData() == "zasoby") {
                    return $ret;
                }
                if ($ret) {
                    $msg = 'Plik został wczytany poprawnie.';
                    $this->addFlash('warning', $msg);
                    return $this->redirect($this->generateUrl('importujSekcje'));
                }
            }
        }

        return $this->render('ParpMainBundle:ImportSekcjeUser:importujSekcje.html.twig', array('form' => $form->createView()));
    }

    protected function wczytajPlikZasoby($fileObject)
    {
        $pomijajZasoby = ["INT-PIN"];
        //die('wczytajPlikZasoby');
        $zrobione = [];
        $mapowanie = [
            //'B' => 'staraNazwa',
            'C' => 'nazwa',
            'D' => 'wlascicielZasobu',
            'E' => 'administratorZasobu',
            'F' => 'administratorTechnicznyZasobu',
            'G' => 'uzytkownicy',
            'H' => 'daneOsobowe',
            'I' => 'komorkaOrgazniacyjna',
            'J' => 'miejsceInstalacji',
            'K' => 'opisZasobu',
            'L' => 'dataZakonczeniaWdrozenia',
            'M' => 'wykonawca',
            'N' => 'nazwaWykonawcy',
            'O' => 'asystaTechniczna',
            'P' => 'dataWygasnieciaAsystyTechnicznej',
            'Q' => 'dokumentacjaFormalna',
            'R' => 'dokumentacjaProjektowoTechniczna',
            'S' => 'technologia',
            'T' => 'testyBezpieczenstwa',
            'U' => 'testyWydajnosciowe',
        ];

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('DELETE ParpMainBundle:ImportSekcjeUser');
        $query->execute();
        $file = $fileObject->getPathname();
        $phpExcelObject = new \PHPExcel(); //$this->get('phpexcel')->createPHPExcelObject();
        //$file = $this->get('kernel')->getRootDir()."/../web/uploads/import/membres.xlsx";
        if (!file_exists($file)) {
            //exit("Please run 05featuredemo.php first." );
            die('nie ma pliku');
        }
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        //$EOL = "\r\n";
        //echo date('H:i:s') , " Iterate worksheets" , $EOL;
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

        $userdane = array();

        //var_dump($sheetData); die();
        $i = 0;
        foreach ($sheetData as $row) {
            //pomijamy pierwszy rzad
            if ($i > 2) {
                if (!in_array($row['B'], $pomijajZasoby) && trim($row['C']) != "") {
                    $zasob = $em->getRepository(Zasoby::class)->findOneByNazwa($row['B']);
                    if (!$zasob) {
                        $zrobione[] = [
                            'rzad' => $i,
                            'staraNazwa' => $row['B'],
                            'nowaNazwa' => $row['C'],
                            'zmiany' => '',
                            'info' => 'NIE ZNALAZL ZASOBU!!!!'
                        ];
                        $zasob = new \ParpV1\MainBundle\Entity\Zasoby();
                        $em->persist($zasob);
                    }

                    {
                        $zmiany = [];
                    foreach ($mapowanie as $k => $v) {
                        $getter = "get".ucfirst($v);
                        $setter = "set".ucfirst($v);
                        $vold = $zasob->{$getter}();
                        $value = $row[$k];
                        if (strstr($setter, "Data") !== false) {
                            //echo " <br>.".$value['dane'][1]." ".$value['dane'][2]." ".$v.".";
                            $value = \DateTime::createFromFormat('D M d H:i:s e Y', $v);
                            //print_r($v);
                            //die();
                        }


                        if ($v != "" && $value != $vold) {
                            $zasob->{$setter}($value);
                            $zmiany[$v] = $value;
                        }
                    }
                        $zrobione[] = [
                            'rzad' => $i,
                            'staraNazwa' => $row['B'],
                            'nowaNazwa' => $row['C'],
                            'zmiany' => $zmiany,
                            'info' => 'Wszystko OK!'
                        ];
                        $zasob->setPublished(1);
                        }
                }
            }
            $i++;
        }
        //echo "<pre>"; print_r($zrobione); echo "</pre>"; die();
        $em->flush();
        //return true;
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $zrobione]);
    }

    protected function wczytajPlikSekcje($fileObject)
    {
        $mapowanie = [
            'A' => '',
            'B' => '',
            'C' => 'departament',
            'D' => 'departamentSkrot',
            'E' => 'pracownik',
            'F' => 'sekcja',
            'G' => 'sekcjaSkrot',
            'H' => 'stanowisko',
            'I' => 'dataZakonczenia',
            'J' => 'typPracownika',
        ];

        $em = $this->getDoctrine()->getManager();
        //$query = $em->createQuery('DELETE ParpMainBundle:ImportSekcjeUser ');
        //$query->execute();
        //$em->flush();
        $file = $fileObject->getPathname();
        $phpExcelObject = new \PHPExcel(); //$this->get('phpexcel')->createPHPExcelObject();
        //$file = $this->get('kernel')->getRootDir()."/../web/uploads/import/membres.xlsx";
        if (!file_exists($file)) {
            //exit("Please run 05featuredemo.php first." );
            die('nie ma pliku');
        }
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        //$EOL = "\r\n";
        //echo date('H:i:s') , " Iterate worksheets" , $EOL;
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

        $userdane = array();

        //var_dump($sheetData); die();
        $i = 0;
        foreach ($sheetData as $row) {
            //pomijamy pierwszy rzad
            if ($i > 1 && $row['D'] != "" && $row['E'] != "") {
                $importSekcjeArr = $em->getRepository(ImportSekcjeUser::class)->findBy(['pracownik' => $row['E'], 'departament' =>$row['C']]);
                if (count($importSekcjeArr) == 0) {
                    $importSekcje = new \ParpV1\MainBundle\Entity\ImportSekcjeUser();
                } else {
                    $importSekcje = $importSekcjeArr[0];
                }
                foreach ($mapowanie as $k => $v) {
                    if ($v != "") {
                        $setter = "set".ucfirst($v);
                        $importSekcje->{$setter}($row[$k]);
                        $em->persist($importSekcje);
                    }
                }
            }
            $i++;
        }
        $em->flush();
        return true;
    }





    /**
     * Lists all Klaster entities.
     *
     * @Route("/audytUprawnienPoczatkowych", name="audytUprawnienPoczatkowych", defaults={})
     * @Method("GET")
     */
    public function audytUprawnienPoczatkowychAction()
    {
        $em = $this->getDoctrine()->getManager();
        $nadawaj = false;
        $ldap = $this->get('ldap_service');
        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        $userowBierz = "ldap";
        $ret = [];

        if ($userowBierz == "ldap") {
            $users = $this->get('ldap_service')->getAllFromAD(false, false);
            //sprawdza ktore grupy powinien miec user jako poczatkowe i sprawdza czy je ma
            foreach ($users as $u) {
                $sekcja = $em->getRepository(ImportSekcjeUser::class)->findBy([
                    'pracownik' => strtoupper($u['name'])
                ]);
                $section = $u['division'];
                if (count($sekcja) > 0) {
                    $section = $sekcja[0]->getSekcjaSkrot();
                }
                $gr = $this->get('ldap_service')->getGrupyUsera($u, $this->get('ldap_service')->getOUfromDN($u), $section);

                $diff = array_diff($gr, $u['memberOf']);

                $msg = "";
                if (count($diff) > 0 && $nadawaj) {
                    //die();
                    foreach ($diff as $g) {
                        $dn = $u['distinguishedname'];
                        $grupa = $ldapAdmin->getGrupa($g);
                        $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                        //echo "<pre>"; var_dump($g, $addtogroup); echo "</pre>";
                        //var_dump($g, $addtogroup, array('member' => $dn ));
                        $ldapAdmin->ldapModAdd($ldapconn, $addtogroup, array('member' => $dn ));

                        $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                        $msg = "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModAdd $ldapstatus dla osoby ".$addtogroup." ".$dn." ".$g."</span>\r\n<br>";
                    }
                }

                $ret[] = [
                    'samaccountname' => $u['samaccountname'],
                    'memberOf' => $u['memberOf'],
                    'powinienMiec' => $gr,
                    'roznica' => $diff,
                    'sekcjaNaPodstawieAD' => (count($sekcja) == 0 ? "TAK" : "NIE"),
                    'title' => $u['title'],
                    'msg' => $msg
                ];
            }
        } else {
            //sprawdza ktore grupy powinien miec user jako poczatkowe i sprawdza czy je ma
            $isu = $em->getRepository(ImportSekcjeUser::class)->findAll();
            $ret = [];
            foreach ($isu as $u) {
                $ret[] = [
                    'samaccountname' => $u['samaccountname'],
                    '' => $this->get('samaccountname_generator')->rekordNameToADname($i->getPracownik())
                ];
            }
        }


        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $ret]);
    }

    /**
     * Lists all Klaster entities.
     *
     * @Route("/audytUprawnienWszystkich", name="audytUprawnienWszystkich", defaults={})
     * @Method("GET")
     */
    public function audytUprawnienWszystkichAction()
    {
        //$user = $this->get('ldap_service')->getUserFromAD('kamil_jakacki');
        //$ret[] = $this->audytUprawnienUsera($user[0]);
        $ret = [];
        $users = $this->get('ldap_service')->getAllFromAD(false, false);
        foreach ($users as $user) {
            $ret[] = $this->audytUprawnienUsera($user);
        }

        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $ret]);
    }
    public function audytUprawnienUsera($user)
    {
        $powinienMiecGrupy = $this->wyliczGrupyUsera($user);
        $maGrupy = $user['memberOf'];
        $diff1 = array_diff($powinienMiecGrupy, $maGrupy);
        $diff2 = array_diff($maGrupy, $powinienMiecGrupy);

        $ret = [
            'osoba' => $user['samaccountname'],
            'maGrupy' => $maGrupy,
            'powinienMiec' => $powinienMiecGrupy,
            'dodac' => $diff1,
            'zdjac' => $diff2
        ];
        return $ret;


        //var_dump($maGrupy, $powinienMiecGrupy, $diff1, $diff2); die();
    }
    public function wyliczGrupyUsera($user)
    {
        $em = $this->getDoctrine()->getManager();
        $userzasoby = $em->getRepository(UserZasoby::class)->findAktywneDlaOsoby($user['samaccountname']);
        //$ret = [];
        $ret = $this->get('ldap_service')->getGrupyUsera($user, $this->get('ldap_service')->getOUfromDN($user), $user['division']);

        foreach ($userzasoby as $uz) {
            $z = $em->getRepository(Zasoby::class)->find($uz->getZasobId());
            if ($z->getGrupyAD() &&
                $uz->getPoziomDostepu() != "nie dotyczy" &&
                $uz->getPoziomDostepu() != "do wypełnienia przez właściciela zasobu"
            ) {
                $ret[] = $this->znajdzGrupeAD($uz, $z);
                /*
                $ret[] = [
                    'id' => $uz->getId(),
                    'zasobId' => $uz->getZasobId(),
                    'zasob' => $z->getNazwa(),
                    'grupyAd' => $z->getGrupyAD(),
                    'poziomyDostepu' => $z->getPoziomDostepu(),
                    'poziomDostepu' => $uz->getPoziomDostepu(),
                    'poziom' => $this->znajdzGrupeAD($uz, $z)
                ];*/
            }
        }


        return $ret;
    }
    protected function znajdzGrupeAD($uz, $z)
    {
        $grupy = explode(";", $z->getGrupyAD());
        $poziomy = explode(";", $z->getPoziomDostepu());
        $ktoryPoziom = $this->znajdzPoziom($poziomy, $uz->getPoziomDostepu());
        return $ktoryPoziom >= 0 && $ktoryPoziom < count($grupy) ? $grupy[$ktoryPoziom] : "!!!blad $ktoryPoziom ".count($grupy)."!!!";
    }
    protected function znajdzPoziom($poziomy, $poziom)
    {
        $i = -1;
        for ($i = 0; $i < count($poziomy); $i++) {
            if (trim($poziomy[$i]) == trim($poziom) || strstr(trim($poziomy[$i]), trim($poziom)) !== false) {
                return $i;
            }
        }
        return $i;
    }

    /**
     * @Route("/updateManagersAndTitles", name="updateManagersAndTitles")
     */
    public function updateManagersAndTitlesAction()
    {
        //ustawia managera, stanowisko na podstawie sekcji z division, departamentu z OU oraz wpisanego kierownika sekcji w tabeli section
        $ldap = $this->get('ldap_admin_service');
        $ldap->output = $this;
        $ldapconn = $ldap->prepareConnection();

        $em = $this->getDoctrine()->getManager();
        $pomijac = ["n/d","ND"];
        //$name = "Boceńska (Burakowska) Iwona";
        //die("tylko_nowe_nazwisko");
        $zrobieni = [];
        $pominieci = [];
        $users = $this->get('ldap_service')->getAllFromAD(false, false);
        foreach ($users as &$u) {
            unset($u['thumbnailphoto']);
            if ($this->get('ldap_service')->getOUfromDN($u) != "BZK") {
                $pominal = true;
                $manager = "";
                $title = "";
                if ($u['description'] != "" && $u['division'] != "" && !in_array($u['division'], $pomijac)) {
                    $departament = $em->getRepository(Departament::class)->findBy(['shortname' => $this->get('ldap_service')->getOUfromDN($u), 'nowaStruktura' => 1]);

                    if (count($departament) == 0) {
                        die("Nie mam departaMENTU ".$this->get('ldap_service')->getOUfromDN($u)." ".$u['description']);
                    }

                    $section = $em->getRepository(Section::class)->findBy(['departament' => $departament[0], 'shortname' => $u['division']]);
                    if (count($section) > 0) {
                        $manager = $section[0]->getKierownikDN();
                        $pominal = false;
                    } else {
                        echo ( "<br> szuka ".$u['distinguishedname']." ".$u['samaccountname']." ".$u['description']." ".$u['division']." ".count($section)." ".($departament[0] ? $departament[0]->getName() : "brak"));
                    }
                }
                //szukam tytulu osoby
                $tytul = $em->getRepository(ImportSekcjeUser::class)->findOneByPracownik(strtoupper($u['name']));
                if ($tytul) {
                    $title = strtolower($tytul->getStanowisko());
                    $pominal = false;
                }
                if ($pominal) {
                    $pominieci[] = $u;
                } else {
                    $entry = [];
                    if ($tytul != "") {
                        $entry['title'] = $title;
                        if (strstr($title, "kierownik") !== false) {
                            $manager = $departament[0]->getDyrektorDN();
                        }
                    }
                    if ($manager != "") {
                        $entry['manager'] = $manager;
                    }
                    $res = $ldap->ldapModify($ldapconn, $u['distinguishedname'], $entry);
                    $ldapstatus = $ldap->ldapError($ldapconn);
                    echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModify $ldapstatus dla osoby ".$u[0]['distinguishedname']."</span> \r\n<br>";

                    $zrobieni[$u['samaccountname']] = $entry;
                }
            }
        }
        print_r($zrobieni);
        print_r($pominieci);
        die();
    }
    protected function getOUDNfromUserDN($u)
    {
        $cz = explode(",", $u['distinguishedname']);
        $ret = [];
        for ($i = 0; $i < count($cz); $i++) {
            if ($i > 0) {
                $ret[] = $cz[$i];
            }
        }
        return implode(",", $ret);
    }


    /**
     * @Route("/nadajUprawnieniaDyrektoromDoSekcji", name="nadajUprawnieniaDyrektoromDoSekcji")
     */
    public function nadajUprawnieniaDyrektoromDoSekcjiAction()
    {

        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        $ret = [];
        $ldap = $this->get('ldap_service');
        $dyrs = $ldap->getDyrektorow();
        foreach ($dyrs as $d) {
            $gs = $this->getGrupyDepartamentu($d['description']);
            $errors = [];

            foreach ($gs as $g) {
                $dn = $d['distinguishedname'];
                $grupa = $ldapAdmin->getGrupa($g);
                $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                //var_dump($g, $addtogroup, array('member' => $dn ));
                $ldapAdmin->ldapModAdd($ldapconn, $addtogroup, array('member' => $dn ));
                $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                $errors[] = "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldapModAdd $ldapstatus dla osoby ".$addtogroup." ".$dn."</span>\r\n<br>";
            }


            //var_dump($gs);
            $ret[] = [
                'departament' => $d['department'],
                'skrot' => $d['description'],
                'user' => $d['name'],
                'user' => $d['samaccountname'],
                'grupy' => $gs,
                'errors' => $errors
            ];
        }


        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $ret]);
    }

    protected function getGrupyDepartamentu($dep)
    {
        $g = $this->get('ldap_service')->getGroupsFromAD("SGG-".$dep, "*");

        $ret = [];
        foreach ($g as $k => $r) {
            //var_dump(substr($r['name'][0], strlen($r['name'][0]) - 3, 3));
            if (substr($r['name'][0], strlen($r['name'][0]) - 3, 3) == "-RW") {
                $ret[] = $r['name'][0];
            }
        }
        return $ret;
    }



    /**
     * Lists all Klaster entities.
     *
     * @Route("/audytDepartamentowSekcjiStanowisko", name="audytDepartamentowSekcjiStanowisko", defaults={})
     * @Method("GET")
     */
    public function audytDepartamentowSekcjiStanowiskoAction()
    {
        $pomijaj = ["ndes-user"];
        $em = $this->getDoctrine()->getManager();


        $ldap = $this->get('ldap_service');
        $users = $ldap->getAllFromAD();

        $ret = [];

        foreach ($users as $u) {
            if (!in_array($u['samaccountname'], $pomijaj)) {
                $rname = $this->get('samaccountname_generator')->ADnameToRekordNameAsArray($u['name']);
                //$rname[0] = mb_strtoupper($rname[0]);
                //$rname[1] = mb_strtoupper($rname[1]);
                $dane = [
                    'name' => $u['name'],
                    'login' => $u['samaccountname'],
                    'ADdepartament' => $u['department'],
                    'ADdepartamentSkrot' => $u['description'],
                    'ADstanowisko' => $u['title'],
                    'ADsekcja' => $u['info'],
                    'ADsekcjaSkrot' => $u['division'],
                    'REKORDdepartament' => '----brak danych-----',
                    'REKORDstanowisko' => '----brak danych-----',
                    'EXCELsekcja' => '----brak danych-----',
                    'EXCELsekcjaSkrot' => '----brak danych-----',
                    'jestWrekordzie' => 'NIE',
                    'jestWimporcieSekcji' => 'NIE',
                    'imieDoSzukaniaWrekord' => $rname[1],
                    'nazwiskoDoSzukaniaWrekord' => $rname[0],
                ];
                //var_dump($u, $rname);
                $daneRekord = $this->getDoctrine()->getManager()->getRepository(DaneRekord::class)->findOneBy([
                    'imie' => trim($rname[1]),
                    'nazwisko' => trim($rname[0]),
                ]);
                if ($daneRekord) {
                    $dane['jestWrekordzie'] = 'TAK';
                    $dane['REKORDstanowisko'] = $daneRekord->getStanowisko();

                    $departament = $this->getDoctrine()->getManager()->getRepository(Departament::class)->findOneByNameInRekord($daneRekord->getDepartament());
                    if ($departament) {
                        $dane['REKORDdepartament'] = $departament->getSkroconaNazwaRekord();
                    } else {
                        //nie mam departmentu
                        $dane['REKORDdepartament'] = "!!!nie ma departamentu w bazie!!!";
                    }
                }
                $daneImportSekcje = $this->getDoctrine()->getManager()->getRepository(ImportSekcjeUser::class)->findOneBy([
                    'pracownik' => $rname[0]." ".$rname[1],
                ]);
                if ($daneImportSekcje) {
                    $dane['jestWimporcieSekcji'] = 'TAK';
                    $dane['EXCELsekcja'] = $daneImportSekcje->getSekcja();
                    $dane['EXCELsekcjaSkrot'] = $daneImportSekcje->getSekcjaSkrot();
                }
                $ret[] = $dane;
            }
        }

        //return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $ret]);
        return $this->get('excel_service')->generateExcel($ret);
    }

    /**
     * Lists all Klaster entities.
     *
     * @Route("/bierzSlownikSekcji", name="bierzSlownikSekcji", defaults={})
     * @Method("GET")
     */
    public function bierzSlownikSekcjiAction()
    {
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        $users = $ldap->getAllFromAD();
        $data = [];
        $sekcje = [];
        $i = 0;
        foreach ($users as $u) {
            if (!isset($sekcje[$u['info']])) {
                $sekcje[$u['info']] = $i;
                $section = $em->getRepository(Section::class)->findOneBy(['name' => $u['info']]);
                $data[$i++] = [
                    'info' => $u['info'],
                    'division' => $u['division'],
                    'istniejeWbazie' => ($section ? "TAK" : "NIE"),
                    'users' => [$u['samaccountname']]
                ];
            } else {
                $data[$sekcje[$u['info']]]['users'][] = $u['samaccountname'];
            }
        }

        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $data]);
    }




    /**
     * Lists all Klaster entities.
     *
     * @Route("/bierzSlownikDapartamentow", name="bierzSlownikDapartamentow", defaults={})
     * @Method("GET")
     */
    public function bierzSlownikDapartamentowAction()
    {
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        $users = $ldap->getAllFromAD();
        $data = [];
        $sekcje = [];
        $i = 0;
        foreach ($users as $u) {
            if (!isset($sekcje[$u['department']])) {
                $sekcje[$u['department']] = $i;
                $section = $em->getRepository(Departament::class)->findOneBy(['name' => $u['department']]);
                $data[$i++] = [
                    'department' => "'".$u['department']."'",
                    'description' => "'".$u['description']."'",
                    'istniejeWbazie' => ($section ? "TAK" : "NIE"),
                    'users' => [$u['samaccountname']]
                ];
            } else {
                $data[$sekcje[$u['department']]]['users'][] = $u['samaccountname'];
            }
        }

        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $data]);
    }

    /**
     * Lists all Klaster entities.
     *
     * @Route("/poprawSlownikDapartamentow", name="poprawSlownikDapartamentow", defaults={})
     * @Method("GET")
     */
    public function poprawSlownikDapartamentowAction()
    {
        $mapujDepartamenty = [
            'Departament Internacjonalizacji Przedsiębiorstw ' => ['name' => 'Departament Internacjonalizacji Przedsiębiorstw', 'shortname' => 'DIP'],
            'Dep.wdrożeń innowacji w przedsiębiorstwach' => ['name' => 'Departament Wdrożeń Innowacji w Przedsiębiorstwach', 'shortname' => 'DWI'],
            'Dep.finansowo-księgowy' => ['name' => 'Departament Finansowo-Księgowy', 'shortname' => 'DFK'],
        ];

        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        $users = $ldap->getAllFromAD();
        $data = [];
        foreach ($users as $u) {
            if (isset($mapujDepartamenty[$u['department']]) || $u['department'] != trim($u['department'])) {
                if (isset($mapujDepartamenty[$u['department']])) {
                    $newDep = $mapujDepartamenty[$u['department']]['name'];
                    $newDepShort = $mapujDepartamenty[$u['department']]['shortname'];
                } else {
                    $newDep = trim($u['department']);
                    $newDepShort = "DPI";
                }

                //mamy osobe do poprawy
                $entry = new \ParpV1\MainBundle\Entity\Entry();
                $entry->setSamaccountname($u['samaccountname']);
                $entry->setDistinguishedname($u['distinguishedname']);
                $entry->setFromWhen(new \Datetime());
                $entry->setIsImplemented(0);
                $entry->setDepartment($newDep);
                $entryAD = [
                    'department' => $newDep,
                    'description' => $newDepShort,
                    'extensionAttribute14' =>  $newDepShort
                ];
                $ldapAdmin->ldapModify($ldapconn, $u['distinguishedname'], $entryAD);
                $ldapstatus = $ldapAdmin->ldapError($ldapconn);
                $data[] = [
                    'samaccountname' => $u['samaccountname'],
                    'department' => $u['department'],
                    'description' => $u['description'],
                    'departmentNew' => $newDep,
                    'descriptionNew' => $newDepShort,
                    'ldapstatus' => $ldapstatus
                ];
            }
        }
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $data]);
    }


    protected function poprawSekcje($sekcja)
    {

        $mapSekcji = [
            'n/d' => '',
            'N/d' => '',
            'N/D' => '',
            '#N/A' => '',
            '' => '',
            '' => '',
        ];

        $specjalneWyrazySekcji = [
            'it' => 'IT',
            'rur' => 'RUR',
            'rif' => 'RIF'
        ];

        if (isset($mapSekcji[$sekcja])) {
            $sekcja = $mapSekcji[$sekcja];
        }


        $findy = ["Ss ds.", "Ss ds", "Ss do spraw", "sekcja ", "Sekcja", "  ", " , ", "/Sekcja Rad do spraw Kompetencji"];
        $replacy = ["Samodzielne stanowisko do spraw", "Samodzielne stanowisko do spraw", "Samodzielne stanowisko do spraw", "", "", " ", ", ", ""];


        $sekcja = ucfirst(strtolower($sekcja));
        $sekcja = str_replace($findy, $replacy, $sekcja);
        if (strstr($sekcja, "Sekcja") === false && strlen($sekcja) > 0 && strstr($sekcja, "Samodzielne stanowisko do spraw") === false) {
            $wyrazy = explode(" ", strtolower($sekcja));
            $wyrazy2 = [];
            $i = 0;
            foreach ($wyrazy as $w) {
                $w = isset($specjalneWyrazySekcji[$w]) ? $specjalneWyrazySekcji[$w] : $w;
                if ($i == count($wyrazy) - 1 && ($w == 'i' || $w == 'ii')) {
                    $w = strtoupper($w);
                } elseif ($w == 'i' || $w == 'ds.') {
                    $w = $w;
                } else {
                    $w = ucfirst($w);
                }
                $wyrazy2[] = ($w);
                $i++;
            }


            $sekcja = "Sekcja ".implode(" ", $wyrazy2);
        } else {
            //samodzielne stanowisko
            $sekcja = str_replace(" it", " IT", $sekcja);
        }
        $sekcja = trim($sekcja);
        $mapaSekcji2 = [
            'Samodzielne stanowisko do spraw audytu wewnetrznego' => 'Samodzielne stanowisko do spraw audytu wewnętrznego',
            'Samodzielne stanowisko do spraw kontroli' => 'Samodzielne stanowisko do spraw koordynacji kontroli',
            'Samodzielne stanowisko do spraw obsługi administarcyjnej' => 'Samodzielne stanowisko do spraw administracyjnych',
            'Samodzielne stanowisko do spraw obsługi administracyjnej' => 'Samodzielne stanowisko do spraw administracyjnych',
            'Samodzielne stanowisko do spraw. administracyjnych' => 'Samodzielne stanowisko do spraw administracyjnych',
            'Sekcja Akredytacji RUR' => 'Sekcja Akredytacji Rejestru Usług Rozwojowych',
            'Sekcja Kontraktacji Usług i Dostaw' => 'Sekcja Kontraktowania Usług i Dostaw',
            'Sekcja Monitorowania Programów Po Ir i Po Pw' => 'Sekcja Monitorowania Programów PO IR oraz PO PW',
            'Sekcja Obsługi Klienta RUR' => 'Sekcja Obsługi Klienta Rejestru Usług Rozwojowych',
            'Sekcja Obsługi Prawnej i Spraw Sądowych, Administracyjnych i Egzekucyjnych' => 'Sekcja Obsługi Prawnej Spraw Sądowych, Administracyjnych i Egzekucyjnych',
            'Sekcja Rad Do Spraw Kompetencji' => 'Sekcja Rad do spraw Kompetencji',
            'Sekcja Rozwoju RUR' => 'Sekcja Rozwoju Rejestru Usług Rozwojowych',
            'Sekcja Rzecznika Beneficjenta Parp' => 'Sekcja Rzecznika Beneficjenta PARP',
            'Sekcja Samodzielne Stanowisko ds. Administracyjnych' => 'Samodzielne stanowisko do spraw administracyjnych',
            'Sekcja Samodzielne Stanowisko ds. Bhp' => 'Samodzielne stanowisko do spraw bezpieczeństwa i higieny pracy',
            'Sekcja Samodzielne Stanowisko ds. Budżetu i Wsparcia Sekcji' => 'Samodzielne stanowisko do spraw budżetu i wsparcia sekcji',
            'Sekcja Samodzielne Stanowisko ds. Wsparcia Zarzadzania Projektami' => 'Samodzielne stanowisko do spraw wsparcia zarządzania projektami',
            'Sekcja Samodzielne Stanowisko ds. Zarządzania Procedurami' => 'Samodzielne stanowisko do spraw zarządzania procedurami',
            'Sekcja Samodzielne Stanowisko ds. Zasad Naboru Projektów i Współpracy Z Ekspertami Zewnętrznymi' => 'Samodzielne stanowisko do spraw zasad naboru projektów i współpracy z ekspertami zewnętrznymi',
            'Sekcja Samodzielne Stanowsiko Ds.administracyjnych' => 'Samodzielne stanowisko do spraw administracyjnych',
            'Sekcja Systemów Informatycznych RUR i Zrk' => 'Sekcja Systemów Informatycznych Rejestru Usług Rozwojowych i Zintegrowanego Rejestru Kwalifikacji',
            'Sekcja Wdrażania i Monitorowania Programu Po Wer' => 'Sekcja Wdrażania i Monitorowania Programu PO WER',
            'Sekcja Współpracy Z RIF' => 'Sekcja Współpracy z RIF',
            'Sekcja Zleceniobiorca' => '',
            'Sekcja Nd' => '',
            '"SS DS programowania\n /Sekcja Rad do spraw Kompetencji"' => 'Samodzielne stanowisko do spraw programowania',
            //'Samodzielne stanowisko do spraw ewaluacji i analiz rozwoju przedsiębiorstw' => '',
            'Samodzielne stanowisko do spraw programowania /rad do spraw kompetencji' => 'Samodzielne stanowisko do spraw programowania',
            'Samodzielne stanowisko do spraw programowania /rad do spraw kompetencji' => 'Samodzielne stanowisko do spraw programowania'
        ];
        $sekcja = isset($mapaSekcji2[$sekcja]) ? $mapaSekcji2[$sekcja] : $sekcja;

        return $sekcja;
    }

    /**
     *
     * @Route("/wczytajExcelSekcjePrzelozeni", name="wczytajExcelSekcjePrzelozeni", defaults={})
     * @Method("GET")
     */
    public function wczytajExcelSekcjePrzelozeniAction()
    {

        //21.11.2016
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();

        $mapDep = ['DEPARTAMENT USŁUG ROZWOJOWYCH' => 'DEPARTAMENT  USŁUG ROZWOJOWYCH',
        'DEPARTAMENT KOMUNIKACJI I MARKETINGU - EXPO' => 'Departament Komunikacji i Marketingu – EXPO'];
        $mapManagers = [
            'Dyląg - Sajór Monika' => 'Dyląg-Sajór Monika',
            "Skubiszewska-Nietz Aleksandra" => 'Skubiszewska-Nietz (Skubiszewska-Nietz) Aleksandra',
            "SKUBISZEWSKA-NIETZ ALEKSANDRA" => 'Skubiszewska-Nietz (Skubiszewska-Nietz) Aleksandra', //'Skubiszewska-Nietz Aleksandra',
            'Nawrocka Monika' => 'Nawrocka (Trzcińska) Monika',
            'Kieruczenko-Adamczyk Katarzyna' => 'Kieruczenko-Adamczyk  Katarzyna',
            'Gniadek Mariola' => 'Gniadek (Jechna) Mariola',
            'Świebocka-Nerbowska Anna' => 'Świebocka-Nerkowska Anna',
            'Laskowski Robert' => 'Laskowski Robert',
            'SKRZYŃSKA JOANNA' => "Skrzyńska Joanna",
            'Skrzyńska Joanna' => "joanna_skrzynska",
            'CZUPRYŃSKA-FRĄCZEK KATARZYNA' => "Czupryńska Katarzyna",
            'CHABROSZEWSKA KAROLINA' => 'Gromulska Karolina',
            'KŁOS-TYBUROWSKA MARTA' => 'Kłos Marta',
            'MACHOWIAK-KRZYSZTAŁOWICZ BEATA' => 'Machowiak Beata',
            'SŁUGOCKA-MORAWSKA DARIA' => 'Morawska Daria',
            'ŻEBER-SIKORSKA MILENA' => 'Żeber Milena',
            'N/d' => ''
        ];

        //$file = "/media/parp/pracownicy.xls";
        $file = "/tmp/pracownicy2.xlsx";
        $phpExcelObject = new \PHPExcel();
        if (!file_exists($file)) {
            die('nie ma pliku');
        }
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        $objPHPExcel->setActiveSheetIndex(0);
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

        $rowStart = 1;
        $mapowanie = [
            'C' => 'name',
            'H' => 'name2',
            'D' => 'department',
            'F' => 'info',
            'G' => 'manager'
        ];
        $wynik = [];

        $braki = [
            'bez dep' => 0,
            'bez sekcji' => 0,
            'bez managera' => 0,
        ];

        $managerzyProblem = [];
        $sekcjeProblem = [];

        $sekcje = [];
        $sekcjeSa = [];

        $noweDane = [];
        //var_dump($sheetData); die();
        $i = 0;
        foreach ($sheetData as $row) {
            if ($i > $rowStart && $row['C'] != "") {
                $o = ['akcja' => 'nic'];
                foreach ($mapowanie as $k => $v) {
                    $o[$v] = trim($row[$k]);
                }
                $o['info'] = $this->poprawSekcje($o['info']);
                if (isset($mapDep[$o['department']])) {
                    $o['department'] = $mapDep[$o['department']];
                }
                if (isset($mapManagers[$o['manager']])) {
                    $o['manager'] = $mapManagers[$o['manager']];
                }
                if (isset($mapManagers[$o['name']])) {
                    $o['name'] = $mapManagers[$o['name']];
                }

                $departament = $em->getRepository(Departament::class)->findOneBy(
                    ['name' => $o['department'], 'nowaStruktura' => 1]
                );
                $o['dbDep'] = $departament ? $departament->getId() : 0;

                $searchName = (isset($mapManagers[$o['name']]) ? $mapManagers[$o['name']] : $o['name']);
                $aduser = $ldap->getUserFromAD(null, $searchName);
                $o['disabled'] = 0;
                if (count($aduser) == 0 && $o['name'] != "") {
                    $im = explode(" ", $o['name']);
                    $searchName = $im[0]."*".$im[1];
                    //echo "SZukam $searchName ";
                    $aduser = $ldap->getUserFromAD(null, $searchName, null, "wszyscyWszyscy");
                    $o['disabled'] = 1;
                    if (count($aduser) == 0) {
                        echo ("BLAD!!! Nie mam osoby $searchName");
                    }
                }


                $o['samaccountname'] = count($aduser) > 0 ? $aduser[0]['samaccountname'] : "!!!";


/*
                $daneRekord = $em->getRepository(DaneRekord::class)->findOneBy(
                    ['samaccountname' => $o['department'], 'nowaStruktura' => 1]
                );
*/


                if ($departament == null) {
                    $braki['bez dep']++;
                }

                $sekcja = $em->getRepository(Section::class)->findOneBy(
                    ['name' => $o['info']]
                );
                $o['dbSekcja'] = $sekcja ? $sekcja->getId() : 0;
                if ($o['info'] != "" && !isset($sekcjeSa[$o['info'].$o['department']])) {
                    $sekcje[] = ['info' => $o['info'], 'departament' => $o['department'], 'dbSekcja' => $o['dbSekcja'], 'dbDep' => $o['dbDep']];
                    $sekcjeSa[$o['info'].$o['department']] = 1;
                }

                if ($sekcja == null) {
                    $braki['bez sekcji']++;
                    $sekcjeProblem[$o['info']] = $o['info'];
                }

                //managera dodac
                if (strstr($o['manager'], "_") !== false) {
                    $manager = $ldap->getUserFromAD($o['manager'], null, null, 'wszyscyWszyscy');
                    $manager = count($manager) == 1 ? $manager[0] : null;
                } else {
                    $manager = $ldap->getUserFromAD(null, $o['manager'], null, 'wszyscyWszyscy');
                    $manager = count($manager) == 1 ? $manager[0] : null;
                }

                $o['adManager'] = $manager ? $manager['samaccountname'] : '';

                if ($manager == null) {
                    $braki['bez managera']++;
                    $managerzyProblem[$o['manager']] = $o['manager'];
                }

                $wynik[] = $o;
            }
            $i++;
        }
        $this->robAkcje($wynik);


        //$this->utworzSekcje($sekcje);
        //ksort($sekcjeProblem);
        //echo "<pre>"; print_r($sekcjeProblem); die();
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $wynik]);
        //var_dump($wynik); die();
    }
    protected function robAkcje($wyniki)
    {
        $pominLudzi = ["czeslaw_testowy", "andrzej_stefanski"];
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        //zapodaje akcje
        foreach ($wyniki as $w) {
            //var_dump($w);
            if ($w['name'] != ''  && $w['department'] != "#N/A" && $w['department'] != "#REF!" && !in_array($w['samaccountname'], $pominLudzi)) {
                $aduser = $ldap->getUserFromAD($w['samaccountname'], null, null, "wszyscyWszyscy");
                if (count($aduser) > 0 && $w['samaccountname'] != "!!!") {
                    $zmian = 0;
                    echo "<br>zmiany dla ".$w['name']." ".$w['samaccountname']."<br>";
                    $zmiany = new Entry();
                    $sekcja = $em->getRepository(Section::class)->findOneBy(
                        ['name' => $w['info']]
                    );

                    $departament = $em->getRepository(Departament::class)->findOneBy(
                        ['name' => $w['department'], 'nowaStruktura' => 1]
                    );
                    if ($sekcja) {
                        if ($aduser[0]['info'] != $sekcja->getName()) {
                            $zmiany->setInfo($sekcja->getName());
                            echo "          zmiana nazwy sekcji ".$w['name']." ".$w['samaccountname']." z ".$aduser[0]['info'] ." na ".$sekcja->getName()."<br>";
                            $zmian++;
                        }
                        if ($aduser[0]['division'] != $sekcja->getShortname()) {
                            $zmiany->setDivision($sekcja->getShortname());
                            echo "          zmiana skrotu sekcji ".$w['name']." ".$w['samaccountname']." z ".$aduser[0]['division'] ." na ".$sekcja->getShortname()."<br>";
                            $zmian++;
                        }
                    } else {
                        if ($aduser[0]['info'] != '') {
                            $zmiany->setInfo("BRAK");
                            echo "          czyszczenie sekcji ".$w['name']." ".$w['samaccountname']." z ".$aduser[0]['info'] ." <br>";
                            $zmian++;
                        }
                        if ($aduser[0]['division'] != '') {
                            $zmiany->setDivision("BRAK");
                            echo "          czyszczenie skrotu sekcji ".$w['name']." ".$w['samaccountname']." z ".$aduser[0]['division'] ." <br>";
                            $zmian++;
                        }
                    }
                    if ($departament != null && $aduser[0]['department'] != $departament->getName()) {
                        //$zmiany->setDepartment($departament->getName());
                        echo "          zmiana nazwy department ".$w['name']." ".$w['samaccountname']." z ".$aduser[0]['department'] ." na ".$departament->getName()."<br>";
                    }
                    if ($departament != null && $aduser[0]['description'] != $departament->getShortname() && !strstr("Konto wy", $aduser[0]['description']) === false) {
                        //$zmiany->setDescription($departament->getShortname());
                        echo "          zmiana skrotu department ".$w['name']." ".$w['samaccountname']." z ".$aduser[0]['description'] ." na ".$departament->getShortname()."<br>";
                    }
                    $manager = $ldap->getUserFromAD($w['adManager']);
                    if (count($manager) > 0) {
                        if ($manager[0]['distinguishedname'] != $aduser[0]['manager']) {
                            echo "          zmiana managera ".$w['name']." ".$w['samaccountname']." z ".$aduser[0]['manager'] ." na ".$manager[0]['distinguishedname']."<br>";
                            $zmian++;
                            $zmiany->setManager($manager[0]['distinguishedname']);
                        }
                    } else {
                        echo "BLAD nie znalazl managera !!!! ".$w['name']." ".$w['samaccountname']." ".$w['manager']."<br>";
                    }
                    if ($zmian > 0) {
                        echo "Robie zmiany ";
                        $zmiany->setFromWhen(new \Datetime());
                        $zmiany->setCreatedBy($this->getUser()->getUsername());
                        $zmiany->setSamaccountname($w['samaccountname']);
                        $em->persist($zmiany);
                    }
                    /*
                    if($aduser[0]['manager'] != $departament->getShortname()){
                        $zmiany->setDescription($departament->getShortname());
                        echo "          zmiana skrotu department ".$w['name']." ".$w['samaccountname']." z ".$aduser[0]['description'] ." na ".$departament->getShortname()."<br>";
                    }*/
                } else {
                    echo "BLAD nie znalazl !!!! ".$w['name']." ".$w['samaccountname']."<br>";
                }
            } else {
                echo "kasuje ".$w['samaccountname']."<br>";
            }
        }
        ///$em->flush();
        //die();
    }
    protected function utworzSekcje($data)
    {

        $em = $this->getDoctrine()->getManager();
        foreach ($data as $d) {
            if ($d['info'] != '') {
                $dep = $em->getRepository(Departament::class)->find($d['dbDep']);
                if ($d['dbSekcja'] == 0) {
                    $short = $this->getSekcjaShortname($d['info']);
                    echo "<br>tworze sekcje ".$d['info']." ze skrotem $short<br>";
                    $s = new \ParpV1\MainBundle\Entity\Section();

                    $s->setShortname($short);
                    $s->setName($d['info']);
                    $s->setDepartament($dep);
                    $em->persist($s);
                } else {
                    $s = $em->getRepository(Section::class)->find($d['dbSekcja']);
                    echo "<br>update sekcji ".$d['info']." ".$d['dbSekcja']."<br>";
                    if ($d['dbDep'] != $dep->getId()) {
                        echo "<br>!!!!!!!!!!!!!!! nie zgadza sie dep !!!!<br>";
                    }
                }
            }
        }
        $em->flush();
        die();
    }
    protected function getSekcjaShortname($name)
    {
        $ws = explode(" ", $name);
        $r = [];
        foreach ($ws as $w) {
            $r[] = strtoupper(substr($w, 0, 1));
        }
        return implode("", $r);
    }
}
