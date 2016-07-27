<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/testff", name="testff", defaults={})
     * @Method("GET")
     */
    public function testffAction()
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
                $login = $danerekord->getLogin();
                $aduser = $ldap->getUserFromAD($login);
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
        //$em->flush();
    }
    public function writeln($txt){
        echo $txt;
    }
    
}