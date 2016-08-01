<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Klaster controller.
 *
 * @Route("/reorganizacja")
 */
class ReorganizacjaParpController extends Controller
{
    
    protected function getGrupyUsera($user, $depshortname, $sekcja){
        $pomijajSekcje = ["ND", "", "n/d", ""];
        $grupy = ['SGG-(skrót D/B)-Wewn-Wsp-RW'
            //, 'SGG-(skrót D/B)-Public-RO'
        ];
        if(!in_array($sekcja, $pomijajSekcje)){
            $grupy[] =  'SGG-(skrót D/B)-Wewn-(skrót sekcji)-RW';
        }
        switch(strtolower($user['title'])){
            case "dyrektor":
            case "dyrektor (p.o.)":
            case "zastępca dyrektora":
            case "zastępca dyrektora (p.o.)":
            case "prezes":
            case "zastępca prezesa":
            case "zastępca prezesa (p.o.)":
                $grupy[] = 'SGG-(skrót D/B)-Olimp-RW';
                $grupy[] = 'SGG-(skrót D/B)-Public-RW';
                break;
        }
        for($i = 0; $i < count($grupy); $i++){
            $grupy[$i] = str_replace("(skrót sekcji)", $sekcja, str_replace("(skrót D/B)", $depshortname, $grupy[$i]));
        }
        return $grupy;
    }
    
    
    /**
     * Lists all Klaster entities.
     *
     * @Route("/nadajUprawnieniaPoczatkoweIzmienOUnaPodstawieAD", name="nadajUprawnieniaPoczatkoweIzmienOUnaPodstawieAD", defaults={})
     * @Method("GET")
     */
    public function nadajUprawnieniaPoczatkoweIzmienOUnaPodstawieADAction()
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
        foreach($users as $u){
            $name1 = trim(mb_strtoupper($u['name']));
            $name2 = $this->get('samaccountname_generator')->ADnameToRekordName($u['name']);
            $import = $em->getRepository('ParpMainBundle:ImportSekcjeUser')->findBy(['pracownik' => $name1]);
            if(count($import) == 0){
                $import = $em->getRepository('ParpMainBundle:ImportSekcjeUser')->findBy(['pracownik' => $name2]);
            }
            $imieNazwisko = $this->get('samaccountname_generator')->ADnameToRekordNameAsArray($u['name']);
            $danerekord = $em->getRepository('ParpMainBundle:DaneRekord')->findOneBy(['imie' => $imieNazwisko[1], 'nazwisko' => $imieNazwisko[0]]);
            if(!$danerekord){
                
                $bledy[] = [
                    'blad' => 'Nie znalazl danych w systemie rekord!!!',
                    'user' => $u['samaccountname'],
                    'name' => $u['name'],
                    'szukal1' => $imieNazwisko[0].", ".$imieNazwisko[1],
                    'szukal2' => $imieNazwisko,
                    'info' => '',
                ];
                $brakRekord++;
            }    
            if(count($import) == 0){
                $bledy[] = [
                    'blad' => 'nie znalazl usera w imporcie!!!',
                    'user' => $u['samaccountname'],
                    'name' => $u['name'],
                    'szukal1' => "'".$name1."'",
                    'szukal2' => "'".$name2."'",
                    'info' => '',
                ];
                $brakImport++;
            }elseif(count($import) > 1){
                
                $bledy[] = [
                    'blad' => 'Znalazl za duzo wpisow: '.count($import).'!!!',
                    'user' => $u['samaccountname'],
                    'name' => $u['name'],
                    'szukal1' => $name1,
                    'szukal2' => $name2,
                    'info' => '',
                ];
            }else{
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
                if($danerekord){
                    //wtedy bierzemy jednak z rekorda!!!
                    $departament = $em->getRepository('ParpMainBundle:Departament')->findOneBy(['nameInRekord' => $danerekord->getDepartament(), 'nowaStruktura' => true]);
                    if(!$departament && $danerekord->getDepartament() > 500){
                        die("Nie mam departamentu ".$danerekord->getDepartament());
                    }
                    if($departament){
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
                if(count($roznica) > 0){
                    //var_dump($coPowinienMiec, $coMa, $roznica); die();
                    $bledy[] = [
                        'blad' => 'Nie wszystko sie zgadza w AD!!!',
                        'user' => $u['samaccountname'],
                        'name' => $u['name'],
                        'szukal1' => $coMa,
                        'szukal2' => $coPowinienMiec,
                        'info' => $roznica,
                    ];
                    $roznicaDn = isset($roznica['dn']) ? $roznica['dn'] : false;
                    if(isset($roznica['dn']))
                        unset($roznica['dn']);
                        
                    if($roznicaDn && $zmieniajOU){
                        //zmiana ou
                        $b = $ldapAdmin->ldap_rename($ldapconn, $u['distinguishedname'], "CN=" . $u['name'], $roznicaDn, TRUE);                
                        $ldapstatus = $ldapAdmin->ldap_error($ldapconn);
                        //var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                        echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldap_rename $ldapstatus ".$u['distinguishedname']."</span> \r\n<br>";
                    
                        
                        
                    }
                    if(count($roznica) > 0 && $zmieniajSekcjewIDescriptionAD){
                        foreach($roznica as $k => $v){
                            if($v == ""){
                                unset($roznica[$k]);
                            }
                        }
                        //zmiana danych
                        //unset($roznica['dn']);
                        $res = $ldapAdmin->ldap_modify($ldapconn, $u['distinguishedname'], $roznica);
                        $ldapstatus = $ldapAdmin->ldap_error($ldapconn);
                        //var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                        echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldap_modify $ldapstatus ".$u['distinguishedname']."</span> \r\n<br>";
                    }
                    if($zmieniajGrupy){
                        
                        $grupy = $this->getGrupyUsera($u, $newDepartamentSkrot, $import[0]->getSekcjaSkrot());
                        foreach($grupy as $g){
                            $dn = $u['distinguishedname'];
                            $grupa = $ldapAdmin->getGrupa($g);
                            $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                            //var_dump($g, $addtogroup, array('member' => $dn ));
                            $ldapAdmin->ldap_mod_add($ldapconn, $addtogroup, array('member' => $dn ));
                            $ldapstatus = $ldapAdmin->ldap_error($ldapconn);
                            echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldap_mod_add $ldapstatus dla osoby ".$addtogroup." ".$dn."</span>\r\n<br>";
                        }
                    }
                    
                    
                }else{
                    $okad++;
                }
                
                
            }
        }
        $bledy[] = [
            'blad' => 'Przetworzone rekordy '.count($users),
            'user' => 'Wpisow ktore maja rekordy w imporcie sekcji '.$ok,
            'name' => 'Wpisow ktore nie maja rekordu w imporcie '.$brakImport,
            'szukal1' => 'Wpisow z bledami '.count($bledy),
            'szukal2' => 'Zgadza sie w AD '.$okad,
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
        $zmieniajOU = true;
        $zmieniajGrupy = true;
        $zmieniajSekcjewIDescriptionAD = true;
        
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
        
        foreach($rows as $row){
            if(isset($mapowanieDep[$row['DEPARTAMENT']])){
                $row['DEPARTAMENT'] = $mapowanieDep[$row['DEPARTAMENT']];
            }
            if($row['DEPARTAMENT'] > 500 && ($tylkoTeBD == "" || $tylkoTeBD == $row['DEPARTAMENT'])){
                //$login = $this->get('samaccountname_generator')->generateSamaccountname($c->parseValue($row['IMIE']), $c->parseValue($row['NAZWISKO']));
                $danerekord = $em->getRepository('ParpMainBundle:DaneRekord')->findOneBySymbolRekordId($c->parseValue($row['SYMBOL']));
                if(!$danerekord){
                    die("Nie moge znalezc osoby !!! ".trim($row['NAZWISKO'])." ".trim($row['IMIE'])." - ".$row['SYMBOL']);
                }
                $departament = $em->getRepository('ParpMainBundle:Departament')->findOneBy(['nameInRekord' => $c->parseValue($row['DEPARTAMENT']), 'nowaStruktura' => true]);
                $prac = mb_strtoupper($danerekord->getNazwisko()." ".$danerekord->getImie());//$c->parseValue($row['NAZWISKO'], false)." ".trim($c->parseValue['IMIE'], false);
                $sekcja = $em->getRepository('ParpMainBundle:ImportSekcjeUser')->findOneBy(['pracownik' => $prac]);
                $login = $danerekord->getLogin();
                    if(count($tylkoTychUserow) == 0 || in_array($login, $tylkoTychUserow)){
                    $aduser = $ldap->getUserFromAD($login);
                    $sekcjaName = "ND";
                    if(!$sekcja){
                        $nieMialemWExeluSekcji[$login] = $prac;
                        //die("Nie mam sekcji dla usera $login '".$prac."'");
                    }else{
                        //TODO: Nadawac sekcje w polu division !!!
                        //oraz dorzucac w uprawnieniach
                        $sekcjaName = $sekcja->getSekcjaSkrot();
                        
                        if($zmieniajSekcjewIDescriptionAD){
                            $zmiana = [
                                //'info' => $sekcja->getSekcja(),
                                //'division' => $sekcja->getSekcjaSkrot(),
                                'description' => $departament->getShortname(),
                                'department' => $departament->getName(),
                                'extensionAttribute14' => $departament->getShortname(),
                                //'extensionAttribute15' => ''//stanowisko //$departament->getShortname(),
                            ];
                            
                            if($sekcjaName != ""){
                                $zmiana['info'] =  $sekcja->getSekcja();
                                $zmiana['division'] =  $sekcja->getSekcjaSkrot();
                            }
                            //$zmiana['info'] = '';
                            
                            //die($aduser[0]['distinguishedname']);
                            $res = $ldap->ldap_modify($ldapconn, $aduser[0]['distinguishedname'], $zmiana);
                            $ldapstatus = $ldap->ldap_error($ldapconn);
                            echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldap_modify $ldapstatus dla osoby ".$aduser[0]['distinguishedname']."</span> \r\n<br>";
                        }
                    }
                    if(!$departament){
                        echo "<pre>"; print_r($aduser[0]); die("Nie mam departamentu dla osoby !!!");
                    }
                    $grupy = $this->getGrupyUsera($aduser[0], $departament->getShortname(), $sekcjaName);
                    if(count($aduser) > 0)
                        unset($aduser[0]['thumbnailphoto']);
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
                    $e = new \Parp\MainBundle\Entity\Entry();
                    $e->setFromWhen(new \Datetime());
                    $e->setSamaccountname($login);
                    $e->setDistinguishedname($aduser[0]['distinguishedname']);
                    $e->setDepartment($departament->getName());
                    $em->persist($e);
                    
                    //CN=Dyrektor Aktywny,OU=BI,OU=Zespoly,OU=PARP Pracownicy,DC=AD,DC=TEST
                    
                    
                    
                    
                    if($zmieniajGrupy){
                        foreach($grupy as $g){
                            $dn = $aduser[0]['distinguishedname'];
                            $grupa = $ldap->getGrupa($g);
                            $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                            //var_dump($g, $addtogroup, array('member' => $dn ));
                            $ldap->ldap_mod_add($ldapconn, $addtogroup, array('member' => $dn ));
                            $ldapstatus = $ldap->ldap_error($ldapconn);
                            echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldap_mod_add $ldapstatus dla osoby ".$addtogroup." ".$dn."</span>\r\n<br>";
                        }
                    }
                    
                    $parent = 'OU=' . $departament->getShortname() . ',OU=Zespoly_2016,OU=PARP Pracownicy,DC=' . $tab[0] . ',DC=' . $tab[1];
                                
                    $cn = $aduser[0]['name'];
                    if($zmieniajOU){
                        //zmieniam OU !!!!!
                        $b = $ldap->ldap_rename($ldapconn, $aduser[0]['distinguishedname'], "CN=" . $cn, $parent, TRUE);                
                        $ldapstatus = $ldap->ldap_error($ldapconn);
                        //var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                        echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldap_rename $ldapstatus ".$aduser[0]['distinguishedname']."</span> \r\n<br>";
                    }
                }
            }
        }
        echo "<pre>"; print_r($noweDepartamenty); die();
        //$em->flush();//nie zapisuje tego
    }
    public function writeln($txt){
        echo "<br>".$txt."<br>";
    }
    
    /**
     * @Route("/importujSekcje", name="importujSekcje")
     */
    public function importujSekcjeAction(Request $request)
    {

        $form = $this->createFormBuilder()->add('plik', 'file', array(
                    'required' => false,
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array('class' => 'filestyle',
                        'data-buttonBefore' => 'false',
                        'data-buttonText' => 'Wybierz plik',
                        'data-iconName' => 'fa fa-file-excel-o',
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
                ->add('wczytaj', 'submit', array('attr' => array(
                        'class' => 'btn btn-success col-sm-12',
            )))
                ->getForm();

        $form->handleRequest($request);
        if ($request->getMethod() == 'POST') {
            if ($form->isValid()) {

                $file = $form->get('plik')->getData();
                $name = $file->getClientOriginalName();

                //$path = $file->getClientPathName();
                //var_dump($file->getPathname());
                // var_dump($name);
                $ret = $this->wczytajPlik($file);
                if ($ret) {
                    
                    $msg = 'Plik został wczytany poprawnie.';
                    $this->get('session')->getFlashBag()->set('warning', $msg);
                    return $this->redirect($this->generateUrl('importujSekcje'));
                }
            }
        }

        return $this->render('ParpMainBundle:ImportSekcjeUser:importujSekcje.html.twig', array('form' => $form->createView()));
    }
    
    protected function wczytajPlik($fileObject)
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
            'I' => 'typPracownika',
            'J' => 'dataZakonczenia',
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
        foreach($sheetData as $row){
            //pomijamy pierwszy rzad
            if($i > 1 && $row['D'] != "" && $row['E'] != ""){
                $importSekcjeArr = $em->getRepository('ParpMainBundle:ImportSekcjeUser')->findBy(['pracownik' => $row['E'], 'departament' =>$row['C']]);
                if(count($importSekcjeArr) == 0){
                    $importSekcje = new \Parp\MainBundle\Entity\ImportSekcjeUser();
                }else{
                    $importSekcje = $importSekcjeArr[0];
                }
                foreach($mapowanie as $k => $v){
                    if($v != ""){
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
        $nadawaj = true;
        $ldap = $this->get('ldap_service');
        $ldapAdmin = $this->get('ldap_admin_service');
        $ldapAdmin->output = $this;
        $ldapconn = $ldapAdmin->prepareConnection();
        $userowBierz = "ldap";
        $ret = [];
        
        if($userowBierz == "ldap"){                    
            $users = $this->get('ldap_service')->getAllFromAD(false, false);
            //sprawdza ktore grupy powinien miec user jako poczatkowe i sprawdza czy je ma
            foreach($users as $u){
                $sekcja = $em->getRepository('ParpMainBundle:ImportSekcjeUser')->findBy([
                    'pracownik' => strtoupper($u['name'])
                ]);
                $section = $u['division'];
                if(count($sekcja) > 0){
                    $section = $sekcja[0]->getSekcjaSkrot();
                }
                $gr = $this->getGrupyUsera($u, $this->getOUfromDN($u), $section);
                
                $diff = array_diff($gr, $u['memberOf']);
                
                $msg = "";
                if(count($diff) > 0 && $nadawaj){
                    //die();
                    foreach($diff as $g){
                        $dn = $u['distinguishedname'];
                        $grupa = $ldapAdmin->getGrupa($g);
                        $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                        //echo "<pre>"; var_dump($g, $addtogroup); echo "</pre>";
                        //var_dump($g, $addtogroup, array('member' => $dn ));
                        $ldapAdmin->ldap_mod_add($ldapconn, $addtogroup, array('member' => $dn ));
                        
                        $ldapstatus = $ldapAdmin->ldap_error($ldapconn);
                        $msg = "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldap_mod_add $ldapstatus dla osoby ".$addtogroup." ".$dn." ".$g."</span>\r\n<br>";
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
        }else{
            //sprawdza ktore grupy powinien miec user jako poczatkowe i sprawdza czy je ma
            $isu = $em->getRepository('ParpMainBundle:ImportSekcjeUser')->findAll();
            $ret = [];
            foreach($isu as $u){
                $ret[] = [
                    'samaccountname' => $u['samaccountname'],
                    '' => $this->get('samaccountname_generator')->rekordNameToADname($i->getPracownik())
                ];
            }
        }
        
        
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $ret]);
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
        
        $em = $this->getDoctrine()->getEntityManager();
        $pomijac = ["n/d","ND"];
        //$name = "Boceńska (Burakowska) Iwona";
        //die("tylko_nowe_nazwisko");
        $zrobieni = [];
        $pominieci = [];
        $users = $this->get('ldap_service')->getAllFromAD(false, false);
        foreach($users as &$u){
            unset($u['thumbnailphoto']);
            if($this->getOUfromDN($u) != "BZK" ){
                $pominal = true;
                $manager = "";
                $title = "";
                if($u['description'] != "" && $u['division'] != "" && !in_array($u['division'], $pomijac)){
                    $departament = $em->getRepository('ParpMainBundle:Departament')->findBy(['shortname' => $this->getOUfromDN($u), 'nowaStruktura' => 1]);
                    
                    if(count($departament) == 0){
                        die("Nie mam departaMENTU ".$this->getOUfromDN($u)." ".$u['description']);
                    }
                                    
                    $section = $em->getRepository('ParpMainBundle:Section')->findBy(['departament' => $departament[0], 'shortname' => $u['division']]);
                    if(count($section) > 0){
                        $manager = $section[0]->getKierownikDN();
                        $pominal = false;
                    }else{
                        echo ( "<br> szuka ".$u['distinguishedname']." ".$u['samaccountname']." ".$u['description']." ".$u['division']." ".count($section)." ".($departament[0] ? $departament[0]->getName() : "brak"));    
                    }
                    
                }
                //szukam tytulu osoby
                $tytul = $em->getRepository('ParpMainBundle:ImportSekcjeUser')->findOneByPracownik(strtoupper($u['name']));
                if($tytul){
                    $title = strtolower($tytul->getStanowisko());
                    $pominal = false;
                }
                if($pominal){
                    $pominieci[] = $u;
                }else{
                    $entry = [];
                    if($tytul != ""){
                        $entry['title'] = $title;
                        if(strstr($title, "kierownik") !== false){
                            $manager = $departament[0]->getDyrektorDN();
                        }
                    }
                    if($manager != ""){
                        $entry['manager'] = $manager;
                    }
                    $res = $ldap->ldap_modify($ldapconn, $u['distinguishedname'], $entry);
                    $ldapstatus = $ldap->ldap_error($ldapconn);
                    echo "<span style='color:".($ldapstatus == "Success" ? "green" : "red")."'>ldap_modify $ldapstatus dla osoby ".$u[0]['distinguishedname']."</span> \r\n<br>";
                            
                    $zrobieni[$u['samaccountname']] = $entry;
                }
            }
        }
        print_r($zrobieni);
        print_r($pominieci);
        die();
    }
    protected function getOUfromDN($u){
        $cz = explode(",", $u['distinguishedname']);
        $ou = str_replace("OU=", "", $cz[1]);
        return $ou;
        
    }
    protected function getOUDNfromUserDN($u){
        $cz = explode(",", $u['distinguishedname']);
        $ret = [];
        for($i = 0; $i < count($cz); $i++){
            if($i > 0){
                $ret[] = $cz[$i];
            }
        }
        return implode(",", $ret);
        
    }
}