<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\APYDataGridBundle;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;

use Parp\MainBundle\Entity\DaneRekord;
use Parp\MainBundle\Form\DaneRekordType;

/**
 * Wiadomosc controller.
 *
 * @Route("/wiadomosc")
 */
class WiadomoscController extends Controller
{

    /**
     *
     * @Route("/powitalna/{samaccountname}", name="powitalna", defaults={"samaccountname" : ""})
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
        $em = $this->getDoctrine()->getManager();
        $powinienMiecGrupy = $this->wyliczGrupyUsera($user);
        //var_dump($powinienMiecGrupy);
        $maGrupy = $user['memberOf'];
        $diff1 = array_diff($powinienMiecGrupy, $maGrupy);
        $diff2 = array_diff($maGrupy, $powinienMiecGrupy);
        $zdejmowaneGrupy = [];
        $zasobyId = [];
        foreach($diff2 as $zdejmowanaGrupa){
            $zasob = $em->getRepository('ParpMainBundle:Zasoby')->findByGrupaAD($zdejmowanaGrupa);
            if($zasob && $zasob->getPublished() == 1){
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
            $zdejmowaneGrupy[] = [
                'grupaAD' =>   $zdejmowanaGrupa,
                'zasob' => ($zasob && $zasob->getPublished() == 1 ? $zasob : null)
            ];
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
            $z = $em->getRepository("ParpMainBundle:Zasoby")->findOneBy(['id' => $uz->getZasobId(), 'published' => 1]);
            
            //var_dump($z->getGrupyAD());
            if(
                $z != null  && $z->getGrupyAD() //&& 
                //$uz->getPoziomDostepu() != "nie dotyczy" && 
                //$uz->getPoziomDostepu() != "do wypełnienia przez właściciela zasobu"
            ){
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