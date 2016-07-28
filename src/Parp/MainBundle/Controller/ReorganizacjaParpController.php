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
        $grupy = ['SGG-(skrót D/B)-Wewn-Wsp-RW', 'SGG-(skrót D/B)-Public-RO', 'SGG-(skrót D/B)-Wewn-(skrót sekcji)-RW'];
        switch($user['title']){
            case "Dyrektor":
            case "Dyrektor (p.o.)":
            case "Zastępca Dyrektora":
            case "Zastępca Dyrektora (p.o.)":
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
        $zmieniajOU = false;
        $zmieniajGrupy = true;
        
        
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_admin_service');
        $ldap->output = $this;
        $ldapconn = $ldap->prepareConnection();
        $c = new ImportRekordDaneController();
        $sql = $c->getSqlDoImportu();
        $rows = $c->executeQuery($sql);
        
        $noweDepartamenty = [];
        $tab = explode(".", $this->container->getParameter('ad_domain'));
        
        foreach($rows as $row){
            if($row['DEPARTAMENT'] > 500){
                //$login = $this->get('samaccountname_generator')->generateSamaccountname($c->parseValue($row['IMIE']), $c->parseValue($row['NAZWISKO']));
                $danerekord = $em->getRepository('ParpMainBundle:DaneRekord')->findOneBySymbolRekordId($c->parseValue($row['SYMBOL']));
                $departament = $em->getRepository('ParpMainBundle:Departament')->findOneBy(['nameInRekord' => $c->parseValue($row['DEPARTAMENT']), 'nowaStruktura' => true]);
                $sekcja = $em->getRepository('ParpMainBundle:ImportSekcjeUser')->findOneBy(['pracownik' => trim($row['NAZWISKO'])." ".trim($row['IMIE'])]);
                $login = $danerekord->getLogin();
                $aduser = $ldap->getUserFromAD($login);
                if(!$sekcja){
                    die("Nie mam sekcji dla usera $login '".trim($row['NAZWISKO'])." ".trim($row['IMIE'])."'");
                }else{
                    //TODO: Nadawac sekcje w polu division !!!
                }
                $grupy = $this->getGrupyUsera($aduser[0], $departament->getShortname(), "SEKCJA_NA_SZTYWNO");
                $noweDepartamenty[] = [
                    'row' => $row,
                    'login' => $login,
                    'aduser' => count($aduser) > 0 ? $aduser[0] : [],
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
                
                
                $parent = 'OU=' . $departament->getShortname() . ',OU=Zespoly 2016,OU=PARP Pracownicy,DC=' . $tab[0] . ',DC=' . $tab[1];
                            
                $cn = $aduser[0]['name'];
                if($zmieniajOU){
                    //zmieniam OU !!!!!
                    $b = $ldap->ldap_rename($ldapconn, $aduser[0]['distinguishedname'], "CN=" . $cn, $parent, TRUE);                
                    $ldapstatus = $ldap->ldap_error($ldapconn);
                    var_dump($aduser[0]['distinguishedname'], "CN=" . $cn, $parent);
                    echo "$ldapstatus \r\n<br>";
                }
                
                if($zmieniajGrupy){
                    foreach($grupy as $g){
                        $dn = $aduser[0]['distinguishedname'];
                        $grupa = $ldap->getGrupa($g);
                        $addtogroup = $grupa['distinguishedname'];//"CN=".$g.",OU=".$this->grupyOU."".$this->patch;
                        var_dump($g, $addtogroup, array('member' => $dn ));
                        $ldap->ldap_mod_add($ldapconn, $addtogroup, array('member' => $dn ));
                        $ldapstatus = $ldap->ldap_error($ldapconn);
                        echo "$ldapstatus \r\n<br>";
                    }
                }
                
            }
        }
        var_dump($noweDepartamenty); die();
        //$em->flush();//nie zapisuje tego
    }
    public function writeln($txt){
        echo $txt;
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