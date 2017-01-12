<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use APY\DataGridBundle\APYDataGridBundle;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;

use Parp\MainBundle\Entity\DaneRekord;
use Parp\MainBundle\Entity\Entry;
use Parp\MainBundle\Form\DaneRekordType;

/**
 * Wiadomosc controller.
 *
 * @Route("/")
 */
class OdebranieUprawnienController extends Controller
{
    /**
     * @Security("has_role('PARP_ADMIN')")
     * @Route("/odebranie_uprawnien/{samaccountname}", name="oderbanie_uprawnien", defaults={"samaccountname" : ""})
     * @Template()
     */
     public function odebranieUprawnienAction($samaccountname){
         
//         !!!!!!!!!!!!!!!!!! zarzadowi nie odbierac!!!! sobie tez nie bo jak mnie wypnie z VPN...
         $pominOsoby = ['kamil_jakacki', 'patrycja_klarecka', 'nina_dobrzynska', 'marcin_szygula', 'czeslaw_testowy'];
        $em = $this->getDoctrine()->getManager();
        $sams = $em->getRepository('ParpMainBundle:Entry')->findOsobyKtoreJuzPrzetworzylPrzyOdbieraniu(['odbieranie_uprawnien']);
        $pominOsoby = array_merge($pominOsoby, $sams);
        //print_r($pominOsoby); die();
        $ldap = $this->get('ldap_service');
        if($samaccountname == ""){
            $ADUsers = $ldap->getAllFromAD();
        }else{
            $ADUsers = $this->get('ldap_service')->getUserFromAD($samaccountname);
        }
        $dt = new \Datetime();
        $dane = [];
        foreach($ADUsers as $user){
            if($user['description'] == 'BI'){
                //nam  nie odbierac na razie!!!
            }else{
                if(!in_array($user['samaccountname'], $pominOsoby)){
                    $uprawnienia = $this->audytUprawnienUsera($user);
                    $wpis = ['osoba' => $user['name'], 'samaccountname' => $user['samaccountname'], 'zdjac' => [], 'dodac' => []];
                    foreach($uprawnienia['zdjac'] as $zdjac){
                        $e = $this->zrobEntry($dt, $user, "-".$zdjac['grupaAD']);
                        $em->persist($e);
                        $wpis['zdjac'][] = $zdjac['grupaAD'];
                    }
                    foreach($uprawnienia['dodac'] as $dodac){
                        $e = $this->zrobEntry($dt, $user, "+".$dodac);
                        $em->persist($e);
                        $wpis['dodac'][] = $dodac;
                    }
                    $dane[] = $wpis;
                    //$em->flush();
                }
            }
        }
        
        
        return $this->render('ParpMainBundle:Dev:showData.html.twig', ['data' => $dane]);
//        var_dump($dane);
        
     }
     protected function zrobEntry($dt, $user, $grupa){
         $e = new Entry();
        $e->setFromWhen($dt);
        $e->setSamaccountname($user['samaccountname']);
        $e->setCreatedBy('odbieranie_uprawnien');
        //$zd = "-".implode(",-", $zdjac);
        //$zd = substr($zd, 0, strlen($zd) - 2);

        $e->setMemberOf($grupa);
        return $e;
     }

    /**
     *
     * @Security("has_role('PARP_ADMIN')")
     * @Route("/wiadomosc/powitalna/{samaccountname}", name="powitalna", defaults={"samaccountname" : ""})
     * @Template()
     */
    public function powitalnaAction($samaccountname)
    {
        
        $em = $this->getDoctrine()->getManager();
        if($samaccountname == ""){
            $log = new \Parp\MainBundle\Entity\Log();
            $log->setLogin($this->getUser()->getUsername());
            $log->setUrl("/powitalna");
            $log->setDescription("Odczytano wiadomość powitalną.");
            
            $em->persist($log);
            $em->flush();
            
            
            $user = $this->get('ldap_service')->getUserFromAD($this->getUser()->getUsername());
        }else{
            $user = $this->get('ldap_service')->getUserFromAD($samaccountname);
        }
        
        
        $uprawnienia = $this->audytUprawnienUsera($user[0]);
        
        //var_dump($uprawnienia); die();
        
        return ['uprawnienia' => $uprawnienia];
    }
    public function audytUprawnienUsera($user){
        
        

        $NIE_ODBIERAC_TYCH_GRUP = ['APP-V Acces 97', 'APP-V Access 2010', 'App-V Default Users'];//tu beda grupy accessowe
        
        
        $em = $this->getDoctrine()->getManager();
        $powinienMiecGrupy = $this->wyliczGrupyUsera($user);
        //var_dump($powinienMiecGrupy);
        $maGrupy = $user['memberOf'];
        $diff1 = array_udiff($powinienMiecGrupy['grupyAD'], $maGrupy, 'strcasecmp');
        $diff2 = array_udiff($maGrupy, $powinienMiecGrupy['grupyAD'], 'strcasecmp');
        $zdejmowaneGrupy = [];
        $zasobyId = [];
        foreach($diff2 as $zdejmowanaGrupa){
            $zasob = $em->getRepository('ParpMainBundle:Zasoby')->findByGrupaAD($zdejmowanaGrupa);
            if($zasob){
                $userzasob = $em->getRepository('ParpMainBundle:UserZasoby')->findBy([
                    'samaccountname' => $this->getUser()->getUsername(),
                    'zasobId' => $zasob->getId()
                ]);
                if(count($userzasob) > 0){
                    $zasob = null;
                }else{
                
                    $zasobyId[] = $zasob->getId();
                }
                
                
                
            }
            if(!in_array($zdejmowanaGrupa, $NIE_ODBIERAC_TYCH_GRUP)){
                $zdejmowaneGrupy[] = [
                    'grupaAD' =>   $zdejmowanaGrupa,
                    'zasob' => $zasob
                ];
            }else{
                $powinienMiecGrupy['grupyAD'][] = $zdejmowanaGrupa;
            }
        }
        
        
        $ret = [
            'osoba' => $user['samaccountname'],
            'maGrupy' => $maGrupy,
            'powinienMiec' => $powinienMiecGrupy,
            'dodac' => $diff1,
            'zdjac' => $zdejmowaneGrupy,
            'zasobyId' => implode(",", $zasobyId)
        ];
        return $ret;
        
        
        //var_dump($maGrupy, $powinienMiecGrupy, $diff1, $diff2); die();
    }
    public function wyliczGrupyUsera($user){
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();
        $userzasoby = $em->getRepository("ParpMainBundle:UserZasoby")->findAktywneDlaOsoby($user['samaccountname']);
        $ret = ['grupyAD' => [], 'zasobyBezGrupAD' => [], 'sumaZWnioskow' => []];
        $ret['grupyAD'] = $this->get('ldap_service')->getGrupyUsera($user, $this->get('ldap_service')->getOUfromDN($user), $user['division']);

        foreach($userzasoby as $uz){
            $z = $em->getRepository("ParpMainBundle:Zasoby")->find($uz->getZasobId());
            //var_dump($z->getGrupyAD());
            if(
                $z->getGrupyAD() 
                || $z->getId() == 4311
                //&& 
                //$uz->getPoziomDostepu() != "nie dotyczy" && 
                //$uz->getPoziomDostepu() != "do wypełnienia przez właściciela zasobu"
            ){
                $grupa = $this->znajdzGrupeAD($uz, $z);
                if($grupa != ''){
                    
                    
                    $grupawAD = $ldap->getGrupa($grupa);
                    if($grupawAD){
                        $ret['grupyAD'][] = $grupa;
                        $ret['sumaZWnioskow'][] = [
                            'grupa' => $grupa,
                            'jestWAD' => true,
                            'zasob' => $z->getNazwa(),
                            'zasobId' => $z->getId()
                        ];
                    }else{
                        $ret['sumaZWnioskow'][] = [
                            'grupa' => $grupa,
                            'jestWAD' => false,
                            'zasob' => $z->getNazwa(),
                            'zasobId' => $z->getId()
                        ];
                    }
                }
                
                //echo "<br>".$z->getId()." ".$uz->getKanalDostepu() ."<br>";
                //VPN 
                if($z->getId() == 4311 && in_array($uz->getKanalDostepu() , ['DZ_O', 'DZ_P'])){
                    $ret['grupyAD'][] = "SG-BI-VPN-Access";
                }
                
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
            }else{
                $ret['sumaZWnioskow'][] = [
                    'grupa' => 'brak',
                    'jestWAD' => false,
                    'zasob' => $z->getNazwa(),
                    'zasobId' => $z->getId()
                ];
            }
        }
        //var_dump($ret); die();
        return $ret;
    }
    
    protected function znajdzGrupeAD($uz, $z){
        $grupy = explode(";", $z->getGrupyAD());
        $poziomy = explode(";", $z->getPoziomDostepu());
        $ktoryPoziom = $this->znajdzPoziom($poziomy, $uz->getPoziomDostepu());
        
        if(!($ktoryPoziom >= 0 && $ktoryPoziom < count($grupy))){
            //var_dump($grupy, $poziomy, $ktoryPoziom);
        }
        
        //$uz->getId()." ".$z->getId()." ".
        return  ($ktoryPoziom >= 0 && $ktoryPoziom < count($grupy) ? $grupy[$ktoryPoziom] : '"'.$z->getNazwa().'" ('.$grupy[0].')') ; //$grupy[0];
    }
    protected function znajdzPoziom($poziomy, $poziom){
        $i = -1;
        for($i = 0; $i < count($poziomy); $i++){
            if(trim($poziomy[$i]) == trim($poziom) || strstr(trim($poziomy[$i]), trim($poziom)) !== false){
                return $i;
            }
        }
        return $i;
    }
    
    /**
     * @Security("has_role('PARP_ADMIN')")
     * @Route("/wykluczBI", name="wykluczBI")
     * @Template()
     */
    public function wykluczBIAction(){
        $em = $this->getDoctrine()->getManager();
        $adusers = $em->getRepository('ParpSoapBundle:ADUser')->findPrzezDescription("BI");
        $sams = [];
        foreach($adusers as $u){
            $sams[] = $u['samaccountname'];
        }
        $entries = $em->getRepository('ParpMainBundle:Entry')->findBy(['samaccountname' => $sams, 'createdBy' => 'odbieranie_uprawnien']);
        $ids = [];
        
        //
        foreach($entries as $e){
            $ids[ ] = $e->getId();
            //$e->setIsImplemented(7);
            //$em->flush();
        }
        var_export($ids); die();
        
    }
    
    /**
     * @Security("has_role('PARP_ADMIN')")
     * @Route("/uprawnienia_przed_odebraniem/{login}/{data}", name="uprawnienia_przed_odebraniem", defaults={"login" : "", "data" : ""})
     * @Template()
     */
    public function uprawnieniaPrzedOdebraniemAction($login = "", $data = ""){
        $now = new \Datetime('2017-01-10');
        $data = $data == "" ? $now->format("Y-m-d") : $data;
        $adusers = $this->getDoctrine()->getManager()->getRepository('ParpSoapBundle:ADUser')->findPrzedOdebraniem($login, $data);
        //var_dump($adusers);
        
        return $this->render('ParpMainBundle:OdebranieUprawnien:przywracanie.html.twig', ['data' => $adusers]);
    }
    
}