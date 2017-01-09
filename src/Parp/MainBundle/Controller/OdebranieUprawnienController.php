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
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        if($samaccountname == ""){
            $ADUsers = $ldap->getAllFromAD();
        }else{
            $ADUsers = $this->get('ldap_service')->getUserFromAD($samaccountname);
        }
        $dt = new \Datetime();
        $zdejme = [];
        foreach($ADUsers as $user){
            $uprawnienia = $this->audytUprawnienUsera($user);
            foreach($uprawnienia['zdjac'] as $zdjac){
                $e = new Entry();
                $e->setFromWhen($dt);
                $e->setSamaccountname($user['samaccountname']);
                //$zd = "-".implode(",-", $zdjac);
                //$zd = substr($zd, 0, strlen($zd) - 2);
                $zd = "-".$zdjac['grupaAD'];
                $e->setMemberOf($zd);
                $zdejme[] = $e;
                $em->persist($e);
            }
        }
        var_dump($zdejme);
        
        
        //$em->flush();
     }


    /**
     *
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
        
        $NIE_ODBIERAC_TYCH_GRUP = ['', '', ''];//tu beda grupy accessowe
        
        $em = $this->getDoctrine()->getManager();
        $powinienMiecGrupy = $this->wyliczGrupyUsera($user);
        //var_dump($powinienMiecGrupy);
        $maGrupy = $user['memberOf'];
        $diff1 = array_udiff($powinienMiecGrupy, $maGrupy, 'strcasecmp');
        $diff2 = array_udiff($maGrupy, $powinienMiecGrupy, 'strcasecmp');
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
        $em = $this->getDoctrine()->getManager();
        $userzasoby = $em->getRepository("ParpMainBundle:UserZasoby")->findAktywneDlaOsoby($user['samaccountname']);
        //$ret = [];
        $ret = $this->get('ldap_service')->getGrupyUsera($user, $this->get('ldap_service')->getOUfromDN($user), $user['division']);
//var_dump($userzasoby);
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
                $ret[] = $this->znajdzGrupeAD($uz, $z);
                
                //echo "<br>".$z->getId()." ".$uz->getKanalDostepu() ."<br>";
                //VPN 
                if($z->getId() == 4311 && in_array($uz->getKanalDostepu() , ['DZ_O', 'DZ_P'])){
                    $ret[] = "SG-BI-VPN-Access";
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
            }
        }
        
        
        
        
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
}