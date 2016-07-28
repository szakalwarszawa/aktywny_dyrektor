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
        switch($user['title']){
            case "Dyrektor":
            case "Dyrektor (p.o.)":
            case "Zastępca Dyrektora":
            case "Zastępca Dyrektora (p.o.)":
            case "Prezes":
            case "Zastępca Prezesa":
            case "Zastępca Prezesa (p.o.)":
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
        ];
        
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('DELETE ParpMainBundle:ImportSekcjeUser ');
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
        foreach($sheetData as $row){
            //pomijamy pierwszy rzad
            if($i > 2){
                $importSekcje = new \Parp\MainBundle\Entity\ImportSekcjeUser();
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
    
}