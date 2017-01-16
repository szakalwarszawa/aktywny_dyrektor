<?php

namespace Parp\MainBundle\Controller;

use Parp\MainBundle\Entity\Engagement;
use Parp\MainBundle\Entity\Entry;
use Parp\MainBundle\Entity\UserEngagement;
use Parp\MainBundle\Entity\UserUprawnienia;
use Parp\MainBundle\Form\EngagementType;
use Parp\MainBundle\Form\UserEngagementType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\APYDataGridBundle;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use APY\DataGridBundle\Grid\Action\MassAction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Parp\MainBundle\Entity\UserZasoby;
use Parp\MainBundle\Form\UserZasobyType;
use Parp\MainBundle\Entity\Zasoby;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use APY\DataGridBundle\Grid\Column\TextColumn;
use Parp\MainBundle\Exception\SecurityTestException;

class NadawanieUprawnienZasobowController extends Controller
{

    protected function generateRemoveAccessChoices($sams){
        //tu zwracamy labelki z separatorem @@@ ktory pokazuje kolejne kolumny (osoba, od, do , modul, funkcja)
        $choices = [];
        $userzasoby = array();
        $userzasobyOpisy = array();
        $zasobyOpisy = array();
        $ids = array();
        $uzs = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findBy(array('samaccountname' => $sams, 'czyAktywne' => true, 'wniosekOdebranie' => null, 'czyOdebrane' => false /*  'czyNadane' => true */));
        // tu trzeba przerobic y kluczem byl id UserZasoby a nie Zasoby bo jeden user moze miec kilka pozopmiw dostepu i kazdy mozemy odebrac oddzielnie
        foreach($uzs as $uu){
            if(!in_array($uu->getZasobId(), $ids))
                $ids[] = $uu->getZasobId();
            $userzasoby[$uu->getId()] = $uu;
            $userzasobyOpisy[$uu->getId()] = $uu->getOpisHtml();//nieuzywane 
        }
        $chs = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->findById($ids);
        foreach($chs as $ch){
            $zasobyOpisy[$ch->getId()] = $ch;
        }
        foreach($userzasoby as $uzid => $uz){
            $moduly = explode(";", $uz->getModul());
            foreach($moduly as $modul){
                $poziomy = explode(";", $uz->getPoziomDostepu());
                foreach($poziomy as $poziom){
                    $data = [
                        $zasobyOpisy[$uz->getZasobId()], 
                        $uz->getSamaccountname(), 
                        $uz->getAktywneOd()->format("Y-m-d")." - ".($uz->getAktywneDo() ? $uz->getAktywneDo()->format("Y-m-d") : "*"), 
                        $modul,
                        $poziom
                    ];
                    $klucz = $uzid.";".$modul.";".$poziom;
                    $choices[$klucz] = implode("@@@", $data);
                }
            }
        }
        
        
        return $choices;
    }
    /**
     * @param $samaccountName
     * @Route("/addRemoveAccessToUsersAction/{action}/{wniosekId}", name="addRemoveAccessToUsersAction", defaults={"wniosekId" : 0})
     */
    public function addRemoveAccessToUsersAction(Request $request, $action, $wniosekId = 0)
    {
        $zasobyId = "";
        if($request->getMethod() == "POST"){
            //\Doctrine\Common\Util\Debug::dump($request->get('form')['samaccountnames']);die();    
            $samaccountnames = $request->get('form')['samaccountnames'];
        }else{        
            $samaccountnames = $request->get('samaccountnames');
            $zasobyId = $request->get('zasobyId');
        }
        //var_dump($samaccountnames); die('addRemoveAccessToUsersAction - mam tamten controller');
        $wniosek = $this->getDoctrine()->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($wniosekId);
        $samt = json_decode($samaccountnames);
        //print_r($samaccountnames);
        if($samt == ""){
            $this->get('session')->getFlashBag()->set('warning', 'Nie można znaleźć wybranych użytkowników!');
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow', array()));    
        }
        //print_r($samt); die();
        $sams = array();
        foreach($samt as $k=>$v){
            if($v == 1){
                
                $sams[] = $k;
            }
        }
        
        
        switch($action){
            case "addResources":
                $title = "Wybierz zasoby do dodania";
                $userzasoby = array();
                $userzasobyOpisy = array();
                $ids = array();
                $uzs = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findBy(array('samaccountname' => $sams, 'czyAktywne' => true, 'czyNadane' => true));
                foreach($uzs as $uu){
                    if(!in_array($uu->getZasobId(), $ids))
                        $ids[] = $uu->getZasobId();
                    $userzasoby[$uu->getZasobId()][] = $uu->getSamaccountname();
                    $userzasobyOpisy[$uu->getZasobId()][$uu->getSamaccountname()] = $uu->getOpisHtml();
                }
                $chsTemp = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->findByPublished(1);
                if(!in_array("PARP_ADMIN2", $this->getUser()->getRoles())){
                    $chs = [];
                    $login = $this->getUser()->getUsername();
                    foreach($chsTemp as $zasob){
                        $admini = explode(",", $zasob->getAdministratorZasobu());
                        //echo ".".$zasob->getAdministratorZasobu().".";
                        if(in_array($login, $admini)){
                            $chs[] = $zasob;
                        }
                    }
                }else{
                    $chs = $chsTemp;
                }
                //var_dump($chs); die();
                break;
            case "removeResources":
                $title = "Odbierz zasoby";
                $choices = $this->generateRemoveAccessChoices($sams);
                //$chs = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->findBySamaccountnames($sams);
                break;
            case "editResources":
                //tu pobierze userzasobId wczyta go i postem odbije 
                $uzid = $request->get('uzid');
                $uz = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->find($uzid);              
//                 Array ( [samaccountnames] => {"adam_gregier":1,"andrzej_trocewicz":1,"kamil_jakacki":1} [wniosekId] => 128 [action] => addResources [fromWhen] => 23-06-2016 [powod] => fdsfds [nazwafiltr] => [grupy] => [access] => Array ( [0] => 2170 ) )
//                 Array ( [samaccountnames] => {"adam_gregier" : 1} [wniosekId] => 128 [action] => editResources [fromWhen] => DateTime Object ( [date] => 2016-06-23 00:00:00 [timezone_type] => 3 [timezone] => Europe/Berlin ) [powod] => vcxvcxv [nazwafiltr] => [grupy] => [access] => Array ( [0] => 1157 ) )
                
                $ndata = array(
                    'samaccountnames' => $uz->getSamaccountnames(),
                    'wniosekId' => $wniosekId,
                    'action' => 'editResources',
                    'fromWhen' => $uz->getAktywneOd()->format('Y-m-d'),
                    'powod' => $uz->getPowodNadania(),
                    'nazwafiltr' => '',
                    'grupy' => '',
                    'access' => array($uz->getZasobId()),
                );
                //print_r($ndata);
                return $this->addResourcesToUsersAction($request, $ndata, $wniosekId, $uzid, $uz);   
                
                break;
            case "addPrivileges":
                $title = "Wybierz uprawnienia do dodania";
                
                $uzs = $this->getDoctrine()->getRepository('ParpMainBundle:UserUprawnienia')->findBy(array('samaccountname' => $sams, 'czyAktywne' => true));
                $ids = array();
                $useruprawnienia = array();
                foreach($uzs as $uu){
                    if(!in_array($uu->getUprawnienieId(), $ids))
                        $ids[] = $uu->getUprawnienieId();
                    $useruprawnienia[$uu->getUprawnienieId()][] = $uu->getSamaccountname();
                }
                $chs = $this->getDoctrine()->getRepository('ParpMainBundle:Uprawnienia')->findall();//ById($ids);
                break;
            case "removePrivileges":
                $title = "Wybierz uprawnienia do odebrania";
                $uzs = $this->getDoctrine()->getRepository('ParpMainBundle:UserUprawnienia')->findBy(array('samaccountname' => $sams, 'czyAktywne' => true));
                $ids = array();
                $useruprawnienia = array();
                foreach($uzs as $uu){
                    if(!in_array($uu->getUprawnienieId(), $ids))
                        $ids[] = $uu->getUprawnienieId();
                    $useruprawnienia[$uu->getUprawnienieId()][] = $uu->getSamaccountname();
                }
                $chs = $this->getDoctrine()->getRepository('ParpMainBundle:Uprawnienia')->findById($ids);
                break;
        }
        
        $choicesDescription = array();//niwuzywane 
          
        if($action != "removeResources"){ 
            $choices = array();    
            foreach($chs as $ch){
                if($action == "addResources"){
                    $info = count($sams) > 1 ? "Nie posiadają" : "Nie posiada";
                    if(isset($userzasoby[$ch->getId()]) && count($userzasoby[$ch->getId()]) > 0){
                        $ret = array();
                        foreach($userzasoby[$ch->getId()] as $u){
                            $ret[] = $u;//"<span data-toggle='popover' data-content='".$userzasobyOpisy[$ch->getId()][$u]."'>".$u."</span>";
                        }
                        
                        
                        //$uss = implode(",", $userzasoby[$ch->getId()]);
                        $info = (count($userzasoby[$ch->getId()]) > 1 ? "Posiadają : " : "Posiada : ").implode(" ", $ret);
                    }
                    
                    if($wniosekId == 0){
                        
                        $choices[$ch->getId()] = $ch->getNazwa();//."@@@".$info;
                    }else{
                        if($wniosek->getWniosek()->getStatus()->getNazwaSystemowa() == "00_TWORZONY"){                    
                            $choices[$ch->getId()] = $ch->getNazwa();//."@@@".$info;
                        }else{
                            //tylko jesli juz jest we wniosku
                            $jest = false;
                            foreach($wniosek->getUserZasoby() as $uz){
                                if($uz->getZasobId() == $ch->getId())
                                    $jest = true;
                            }
                            if($jest || $wniosek->getZasobId() == $ch->getId())
                                $choices[$ch->getId()] = $ch->getNazwa();//."@@@".$info;
                        }
                    }
                    
                }            
                elseif($action == "addPrivileges" || $action == "removePrivileges"){
                    //die(".".count($sams));
                    $info = count($sams) > 1 ? "Nie posiadają" : "Nie posiada";
                    if(isset($useruprawnienia[$ch->getId()]) && count($useruprawnienia[$ch->getId()]) > 0){
                        $uss = implode(",", $useruprawnienia[$ch->getId()]);
                        $info = count($sams) > 1 ? "Posiadają : " . (count($useruprawnienia[$ch->getId()]) == count($sams) ? "WSZYSCY" : $uss) : "Posiada";
                    }
                    $gids = array();
                    foreach($ch->getGrupy() as $g){
                        $gids[] = $g->getId();
                    }
                    //print_r($gids); die();
                    $choices[$ch->getId()] = $ch->getOpis();//."@@@".$info."@@@".implode(",", $gids);
                }
            }
        }
        return $this->addRemoveAccessToUsers($request, $samaccountnames, $choices, $title, $action, $wniosekId, $zasobyId);    
    }
    
    protected function addRemoveAccessToUsers(Request $request, $samaccountnames, $choices, $title, $action, $wniosekId = 0, $zasobyId = "")
    {
        $wniosek = $this->getDoctrine()->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($wniosekId);
        //print_r($samaccountnames);
        $ldap = $this->get('ldap_service');
        $samaccountnames = json_decode($samaccountnames);
        $users = array();
        
        foreach($samaccountnames as $sam => $v){
            if($v){
                //echo " $sam ";
                if($wniosek && $wniosek->getPracownikSpozaParp()){
                    //$ADUser = $ldap->getUserFromAD($sam);
                    $users[] = array(
                        'samaccountname' => $sam,
                        'name' => $sam
                    );
                }else{
                    $ADUser = $ldap->getUserFromAD($sam);
                    $users[] = $ADUser[0];
                }
            }
        }
        $grupys = $this->getDoctrine()->getRepository('ParpMainBundle:GrupyUprawnien')->findAll();
        $grupy = array();
        foreach($grupys as $g){
            $grupy[$g->getId()] = $g->getOpis();
        }
        $now = new \Datetime();
        
        //echo "<pre>"; print_r($choices); die();
        
        $builder = $this->createFormBuilder();
        $form = $builder
                ->add('samaccountnames', 'hidden', array(
                    'data' => $samaccountnames
                ))
                ->add('wniosekId', 'hidden', array(
                    'data' => $wniosekId
                ))
                ->add('action', 'hidden', array(
                    'data' => $action
                ))
                ->add('samaccountnames', 'hidden', array(
                    'required' => false,
                    'read_only' => true,
                    'label' => 'Nazwa kont',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'data' => json_encode($samaccountnames)
                ))
                ->add('fromWhen', 'text', array(
                    'attr' => array(
                        'class' => 'form-control datepicker',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zmiany',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label ',
                    ),
                    'required' => false,
                    'data' => $now->format("Y-m-d")
                ))
                ->add('powod', 'textarea', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'label' => 'Cel nadania/odebrania',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => true
                ))
                ->add('grupy', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => "Filtruj po grupie uprawnień",
                    'label_attr' => array(
                        'class' => 'col-sm-12 control-label text-left '.($action == "addResources" || $action == "removeResources" ? "hidden" : ""),
                    ),
                    'attr' => array(
                        'class' => 'ays-ignore '.($action == "addResources" || $action == "removeResources" ? "hidden" : ""),
                    ),
                    'choices' => $grupy,
                    'multiple' => false,
                    'expanded' => false
                ))
                ->add('buttonzaznacz', 'button', array(
                    //'label' =>  false,
                    'attr' => array(
                        'class' => 'btn btn-info col-sm-12',
                    ),
                    'label' => 'Zaznacz wszystkie widoczne'
                ))
                ->add('buttonodznacz', 'button', array(
                    //'label' =>  false,
                    'attr' => array(
                        'class' => 'btn btn-info col-sm-12',
                    ),
                    'label' => 'Odznacz wszystkie'
                ))
                ->add('wybraneZasoby', 'textarea', array('mapped' => false, 'attr' => ["readonly" => true]))
                
                ->add('nazwafiltr', 'text', array(
                    'label_attr' => array(
                        'class' => 'col-sm-12 control-label text-left ',
                    ),
                    'label' => 'Filtruj po nazwie',
                    'attr' => array(
                        'class' => 'ays-ignore ',
                    ),
                    'required' => false
                ))
                ->add('access', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => $title,
                    'label_attr' => array(
                        'class' => 'col-sm-12 control-label text-left uprawnienieRow',
                    ),
                    'attr' => array(
                        'class' => '',
                    ),
                    'choices' => $choices,
                    'multiple' => true,
                    'expanded' => true
                ))

                ->add('zapisz2', 'submit', array(
                    'attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                    ),
                    'label' => 'Dalej'
                ))
                ->add('zapisz', 'submit', array(
                    'attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                    ),
                    'label' => 'Dalej'
                ))
                ->setAction($this->generateUrl('addRemoveAccessToUsersAction', array('wniosekId' => $wniosekId, 'action' => $action)))
                ->setMethod('POST')
                ->getForm();
        
        $form->handleRequest($request);

        if ($form->isValid()) {
            
    
            $ndata = $form->getData();
            $sams = array();
            $s1 = json_decode($ndata['samaccountnames']);
            foreach($s1 as $k => $v){
                if($v){
                    $sams[] = $k;                    
                    $this->get('adcheck_service')->checkIfUserCanBeEdited($k);
                }
            }
            
            
            
            switch($ndata['action']){
                case "addResources":
                    //check privileges - czy jest adminem zasobow
                    if(!$wniosekId){
                        //jesli bez wniosku sprawdzamy czy jest PARP_ADMIN albo PARP_ADMIN_ZASOBU dla swoich zasobow
                        $this->sprawdzCzyMozeDodawacOdbieracUprawnieniaBezWniosku($ndata['access']);
                    }
                    
                    
                    return $this->addResourcesToUsersAction($request, $ndata, $wniosekId);        
                    break;
                
                case "removeResources":
                    //check privileges - czy jest adminem zasobow!!!
                    if(!$wniosekId){
                        //jesli bez wniosku sprawdzamy czy jest PARP_ADMIN albo PARP_ADMIN_ZASOBU dla swoich zasobow
                        $ids = [];
                        foreach($ndata['access'] as $a){
                            $ps = explode(";", $a);
                            $userZasob = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserZasoby')->find($ps[0]);

                            $ids[] = $userZasob->getZasobId();
                        }
                        $this->sprawdzCzyMozeDodawacOdbieracUprawnieniaBezWniosku($ids);
                    }
                
                    return $this->removeResourcesToUsersAction($request, $ndata, $wniosekId);  
                    
                    break;
                case "addPrivileges":
                    $powod = $ndata['powod'];
                    //print_r($ndata); die();
                    foreach($ndata['access'] as $z){
                        
                        foreach($sams as $currentsam){
                            $zmianaupr = array();
                            $suz = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserUprawnienia')->findOneBy(array('samaccountname' => $currentsam, 'uprawnienie_id' => $z));
                            
                            $u = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Uprawnienia')->find($z);
                            if($suz){
                                $msg = "NIE nadaje userowi ".$currentsam." uprawnienia  '".$u->getOpis()."' bo je ma !";
                                $this->addFlash('notice', $msg);
                                
                            }else{
                                $suz = new UserUprawnienia();
                                $suz->setSamaccountname($currentsam);
                                $suz->setOpis($u->getOpis());
                                $suz->setDataNadania(new \Datetime($ndata['fromWhen']));
                                $suz->setDataOdebrania(null);
                                $suz->setCzyAktywne(true);
                                $suz->setUprawnienieId($z);
                                $suz->setPowodNadania($powod);
                                $this->getDoctrine()->getManager()->persist($suz);
                                //$this->getDoctrine()->getManager()->remove($suz);
                                $msg = "Nadaje userowi ".$currentsam." uprawnienia  '".$u->getOpis()."' bo ich nie ma";
                                if($u->getGrupaAd() != ""){
                                    $aduser = $this->get('ldap_service')->getUserFromAD($currentsam);
                                    $msg .= "I dodaje do grupy AD : ".$u->getGrupaAd();
                                    $entry = new Entry($this->getUser()->getUsername());
                                    $entry->setFromWhen(new \Datetime());
                                    $entry->setSamaccountname($currentsam);
                                    $entry->setMemberOf("+".$u->getGrupaAd());
                                    $entry->setIsImplemented(0);
                                    $entry->setDistinguishedName($aduser[0]["distinguishedname"]);
                                    $this->getDoctrine()->getManager()->persist($entry);
                                }
                                
                                
                                $this->addFlash('warning', $msg);
                                $zmianaupr[] = $u->getOpis();
                            }
                            
                            if(count($zmianaupr) > 0){
                                $this->get('uprawnieniaservice')->wyslij(array('cn' => '', 'samaccountname' => $currentsam, 'fromWhen' => new \Datetime()), array(), $zmianaupr);
                            }
                        }
                    }
                    
                    if($wniosek){
                        $wniosek->ustawPoleZasoby();
                    }
                    $this->getDoctrine()->getManager()->flush();
                    return $this->redirect($this->generateUrl('main'));
                    break;
                case "removePrivileges":
                    $powod = $ndata['powod'];
                    //print_r($ndata); die();
                    foreach($ndata['access'] as $z){
                        
                        foreach($sams as $currentsam){
                            $zmianaupr = array();
                            $suz = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserUprawnienia')->findOneBy(array('samaccountname' => $currentsam, 'uprawnienie_id' => $z));
                            if($suz){
                                
                                $u = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Uprawnienia')->find($z);
                                $suz->setDataOdebrania(new \Datetime($ndata['fromWhen']));
                                $suz->setCzyAktywne(false);
                                $suz->setPowodOdebrania($powod);
                                //$this->getDoctrine()->getManager()->persist($suz);
                                $this->getDoctrine()->getManager()->remove($suz);
                                $msg = "Odbieram userowi ".$currentsam." uprawnienia  '".$u->getOpis()."' bo je ma";
                                
                                if($u->getGrupaAd() != ""){
                                    $aduser = $this->get('ldap_service')->getUserFromAD($currentsam);
                                    $msg .= "I wyjmuje z grupy AD : ".$u->getGrupaAd();
                                    $entry = new Entry($this->getUser()->getUsername());
                                    $entry->setFromWhen(new \Datetime());
                                    $entry->setSamaccountname($currentsam);
                                    $entry->setMemberOf("-".$u->getGrupaAd());
                                    $entry->setIsImplemented(0);
                                    $entry->setDistinguishedName($aduser[0]["distinguishedname"]);
                                    $this->getDoctrine()->getManager()->persist($entry);
                                }
                                
                                $this->addFlash('warning', $msg);
                                $zmianaupr[] = $u->getOpis();
                            }else{
                                $msg = "NIE odbieram userowi ".$currentsam." uprawnienia  '".$u->getOpis()."' bo ich nie ma !";
                                $this->addFlash('notice', $msg);
                            }
                            
                            if(count($zmianaupr) > 0)
                                $this->get('uprawnieniaservice')->wyslij(array('cn' => '', 'samaccountname' => $currentsam, 'fromWhen' => new \Datetime()), $zmianaupr, array());
                        }
                    }
                    
                    if($wniosek){
                        $wniosek->ustawPoleZasoby();
                    }
                    $this->getDoctrine()->getManager()->flush();
                    return $this->redirect($this->generateUrl('main'));
                    break;
            }
            
        }
        //print_r($users);
        $tmpl = $wniosek ? 'ParpMainBundle:NadawanieUprawnienZasobow:addRemoveUserAccessByWniosek.html.twig' : 'ParpMainBundle:NadawanieUprawnienZasobow:addRemoveUserAccess.html.twig';
        return $this->render($tmpl, array(
            'wniosek' => $wniosek,
            'wniosekId' => $wniosekId,
            'included' => ($wniosek ? 1 : 0),
            'users' => $users,
            'form' => $form->createView() ,
            'title' => $title,
            'choicesDescription' => $choices,
            'action' => $action,
            'zasobyId' => $zasobyId
        ));
    }
    protected function sprawdzCzyMozeDodawacOdbieracUprawnieniaBezWniosku($zids){
        if(!in_array('PARP_AZ_UPRAWNIENIA_BEZ_WNIOSKOW', $this->getUser()->getRoles())){
            die("Nie masz uprawnien by wykonywac ta akcje, musisz byc administratorem zasobow ze specjalna rola!");
        }
        $jestAdminemWszystkichZasobow = true;
        $username = $this->getUser()->getUsername();
        $nieJestAdminem = [];
        $zasoby = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->findById($zids);
        foreach($zasoby as $zasob){
            $admini = explode(",", $zasob->getAdministratorZasobu());
            $jestAdminemWszystkichZasobow = $jestAdminemWszystkichZasobow && in_array($username, $admini);
            if(!in_array($username, $admini)){
                $nieJestAdminem[] = $zasob->getNazwa();
            }
        }
        
        if(!$jestAdminemWszystkichZasobow && !in_array("PARP_ADMIN", $this->getUser()->getRoles())){
            $msg = "Nie możesz dodawać/odejmować uprawnień do zasbów których nie jesteś administratorem!!!";
            $msg .= "\r\n<br>Nie jesteś administratorem tych zasobów: ".implode(", ", $nieJestAdminem);
            die($msg);
        }
    }
    
    protected function removeResourcesToUsersAction(Request $request, $ndata = null, $wniosekId = 0, $uzid = 0, $userzasob = null){
        
        $wniosek = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($wniosekId);
        $powod = $ndata['powod'];
        //print_r($ndata); die();
        //grupuje uprawnienia po uzid
        $daneDoZmiany = [];
        foreach($ndata['access'] as $z){
            $data = explode(";", $z);
            $id = $data[0];
            $modul = $data[1];
            $poziom = $data[2];
            $daneDoZmiany[$id]['dane'][] = [
                'modul' => $modul,
                'poziom' => $poziom,
                'odbierane' => 1
            ];
            $daneDoZmiany[$id]['moduly'][$modul] = $modul;
            $daneDoZmiany[$id]['poziomy'][$poziom] = $poziom;
            $daneDoZmiany[$id]['odbiera'][$modul.$poziom] = $modul.$poziom;
        }
        foreach($daneDoZmiany as $id => $dane){
            $uz = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserZasoby')->find($id);
            $uz->setWniosekOdebranie(null);
            $wynik = $uz->podzielUprawnieniaPrzyOdbieraniu($dane);
            
            //var_dump($dane, $wynik); die();
            
            if(count($wynik['modulyKtoreZostaja']) != 0 && count($wynik['modulyKtoreZostaja']) != 0){
                //ustawiamy te co zostaja jesli sa
                $uz->setModul($wynik['modulyKtoreZostaja']);
                $uz->setPoziomdostepu($wynik['poziomyKtoreZostaja']);
            }else{
                $this->getDoctrine()->getManager()->remove($uz);
            }
            
            foreach($wynik['nowe'] as $d){
                if(trim($d['modul']) != "" && trim($d['poziom']) != ""){
                    $noweUz = clone $uz;
                    $noweUz->setModul($d['modul']);
                    $noweUz->setPoziomdostepu($d['poziom']);
                    if($d['odbierane']){
                        $noweUz->setPowodOdebrania($powod);
                        $noweUz->setCzyAktywne(false);
                        if($wniosek){
                            $noweUz->setWniosekOdebranie($wniosek);
                            $noweUz->setDataOdebrania(new \Datetime($ndata['fromWhen']));
                        }else{
                            $noweUz->setKtoOdebral($this->getUser()->getUsername());
                            $noweUz->setCzyOdebrane(true);
                            $noweUz->setAktywneDo(new \Datetime($ndata['fromWhen']));
                            $noweUz->setDataOdebrania(new \Datetime($ndata['fromWhen']));
                        }
                    }else{
                        $noweUz->setWniosekOdebranie(null);
                    }
                    $this->getDoctrine()->getManager()->persist($noweUz);
                }
            }
            
            
            //var_dump($uz->getId(), $uz->getModul(), $uz->getPoziomdostepu(), $dane, $wynik);die();
            $zasob = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Zasoby')->find($uz->getZasobId());

            $zmianaupr[] = $zasob->getOpis();   
        }
        
        
        if($wniosek){
            $wniosek->ustawPoleZasoby();
        }
        
        $this->getDoctrine()->getManager()->flush();
        
        if($wniosekId != 0){
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array('id' => $wniosekId)));
        }else{
            return $this->redirect($this->generateUrl('main'));
        }
        
        //return $this->redirect($this->generateUrl('main'));
    }
    
    /**
     * @param $samaccountName
     * @Route("/addResourcesToUsers/", name="addResourcesToUsers")
     */
    
    public function addResourcesToUsersAction(Request $request, $ndata = null, $wniosekId = 0, $uzid = 0, $userzasob = null)
    {
        $wniosek = $this->getDoctrine()->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($wniosekId);
        //print_r($ndata); die();
        $action = $uzid == 0 ? "addResources" : "editResources";
        $samaccountnamesPars = array(
            'required' => false,
            'read_only' => true,
            'label' => 'Nazwa kont',
            'label_attr' => array(
                'class' => 'col-sm-4 control-label',
            ),
            'attr' => array(
                'class' => 'form-control',
            )
        );
        $fromWhenPars = array(
            'attr' => array(
                'class' => 'form-control',
            ),
//                'widget' => 'single_text',
            'label' => 'Data zmiany',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
            'label_attr' => array(
                'class' => 'col-sm-4 control-label',
            ),
            'required' => false
        );
        $powodPars = array(
            'attr' => array(
                'class' => 'form-control',
            ),
            'label' => 'Powód nadania/odebrania',
            'label_attr' => array(
                'class' => 'col-sm-4 control-label',
            ),
            'required' => true
        );
        $userzasoby = array();

        $choicesPoziomDostepu = array();
        $choicesModul = array();
        $now = new \Datetime();  
        if($ndata == null){
            //die('mam nulla');
            $zids = array();
            //print_r($_POST['form']['userzasoby']);
            //$ndata2 = $form->getData();
            foreach($_POST['form']['userzasoby'] as $v){
                $zids[] = $v['zasobId'];
            }
            $fromWhenPars['data'] = $now->format("Y-m-d");
        }else{
            $samaccountnames = json_decode($ndata['samaccountnames']);
            $ldap = $this->get('ldap_service');
            $users = array();
            foreach($samaccountnames as $sam => $v){
                if($v){
                    if($wniosek && $wniosek->getPracownikSpozaParp()){
                        //$ADUser = $ldap->getUserFromAD($sam);
                        $users[] = array(
                            'samaccountname' => $sam,
                            'name' => $sam
                        );
                    }else{
                        $ADUser = $ldap->getUserFromAD($sam);
                        $users[] = $ADUser[0];
                    }
                }
            }                          
            $samaccountnamesPars['data'] = json_encode($samaccountnames);
            $fromWhenPars['data'] = $ndata['fromWhen'];
            $zids = $ndata['access'];
            $powodPars['data'] = $ndata['powod'];
            //$userzasobyPars['data'] = array();//$userzasoby;    
        }
        $datauz = array(
            'aktywneOd' => $fromWhenPars['data'],
        );
        foreach($zids as $v){
            //print_r($v);
            $z = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->find($v);
            
            
            
            
            //echo ".".count($z->getUzytkownicy()).".";
            if($uzid == 0){
                $uz = new UserZasoby();
            }else{
                $uz = $userzasob;
                
                $datauz['aktywneDo'] = $userzasob->getAktywneDo();
                $datauz['modul'] = $userzasob->getModul();
                $datauz['poziomDostepu'] = $userzasob->getPoziomDostepu();
            }
            $uz->setZasobId($z->getId());
            $uz->setZasobOpis($z->getNazwa());
            $uz->setPoziomDostepu($z->getPoziomDostepu());
            $uz->setModul($z->getModulFunkcja());
            $c1 = explode(",", $z->getPoziomDostepu());
            foreach($c1 as $c){
                $c = trim($c);
                $choicesPoziomDostepu[$c] = $c;
            }
            $c2 = explode(",", $z->getModulFunkcja());
            foreach($c2 as $c){
                $c = trim($c);
                $choicesModul[$c] = $c;
            }
            
            $uz->setZasobNazwa($z->getNazwa());
            //$uz->setSamaccountname($z->getId());
            $userzasoby[] = $uz;
        }
        //print_r($fromWhenPars['data']);
        $form = $this->createFormBuilder()
            ->add('action', 'hidden', array(
                'data' => $action
            ))
            ->add('wniosekId', 'hidden', array(
                'data' => $wniosekId
            ))
            ->add('samaccountnames', 'hidden', $samaccountnamesPars)
            ->add('fromWhen', 'hidden', $fromWhenPars)
            ->add('powod', 'hidden', $powodPars)
        ->add('userzasoby','collection', array(
            'type' => new UserZasobyType($choicesModul, $choicesPoziomDostepu, true, $datauz),
            'allow_add'    => true,
            'allow_delete'    => true,
            'by_reference' => false,
            'label' => "Zasoby",
            'prototype' => true,
            'cascade_validation' => true,
            'data' => $userzasoby
        ))
            ->add('Dalej', 'submit', array(
                'attr' => array(
                    'onclick' => 'beforeSubmit(event)',
                    'class' => 'btn btn-success col-sm-12',
                ),
            ))
            ->setAction($this->generateUrl('addResourcesToUsers'))
            ->setMethod('POST')
            ->getForm();
            
        if($ndata == null){
            $form->handleRequest($request);

            if ($form->isValid()) {
                //die('temp blokuje by zbadac wnioski');
                $ndata = $form->getData();
                //print_r($ndata);
                //tworzy przypisania do zasobow
                $sams = array();
                $s1 = json_decode($ndata['samaccountnames']);
                foreach($s1 as $k => $v){
                    if($v && $v != "" && $v != "_empty_")
                        $sams[] = $k;
                }
                $msg = "";
                $msg2 = "";
                $powod = $ndata['powod'];
                $wniosekId = $ndata['wniosekId'];
                $wniosek = $this->getDoctrine()->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($wniosekId);
                //var_dump($ndata); die();
                foreach($ndata['userzasoby'] as $oz){
                    foreach($sams as $currentsam){
                        $zmianaupr = array();
                        
                        //tu szukal podobnych dla tego zasobu ale teraz po polaczeniu z wnioskiami i nieaktywnymi to trzeba by warunek zwiekszyc
                        //$suz = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserZasoby')->findOneBy(array('samaccountname' => $currentsam, 'zasobId' => $oz->getZasobId()));
                        $zasob = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Zasoby')->find($oz->getZasobId());
                        $admini = explode(",", $zasob->getAdministratorZasobu());
                        //var_dump($this->getUser()->getUsername(), $admini, $this->getUser()->getRoles());
                        if($wniosekId == 0 && !in_array($this->getUser()->getUsername(), $admini) && !in_array("PARP_ADMIN", $this->getUser()->getRoles())){
                            //jesli bez wniosku
                            //jesli nie admin_zasobu
                            //jesli nie parp_admin
                            //wtedy nie moze
                            throw new SecurityTestException("Tylko administrator zasobu (albo administrator AkD) może dodawać do swoich zasobów użytkowników bez wniosku!!!");
                        }
                        
                        //print_r($suz);
                        //if($suz == null){
                            if($oz->get_Idd() > 0){
                                //die(".".$oz->getIdd());
                                //$z2 = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserZasoby')->find($oz->get_Idd());
                                //$this->getDoctrine()->getManager()->remove($z2);
                                $z = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserZasoby')->find($oz->get_Idd());
                                $z->setModul($oz->getModul());
                                $z->setPoziomDostepu($oz->getPoziomDostepu());
                                $z->setSumowanieUprawnien($oz->getSumowanieUprawnien());
                                $z->setBezterminowo($oz->getBezterminowo());
                                $z->setAktywneOd(new \DateTime($oz->getAktywneOd()));
                                if($oz->getAktywneDo() == "" || $oz->getBezterminowo()){
                                    $z->setAktywneDo(null);
                                }else{
                                    $z->setAktywneDo(new \DateTime($oz->getAktywneDo()));
                                }
                                $z->setKanalDostepu($oz->getKanalDostepu());
                                $z->setUprawnieniaAdministracyjne($oz->getUprawnieniaAdministracyjne());
                                $z->setOdstepstwoOdProcedury($oz->getOdstepstwoOdProcedury());
                            }else{
                                $z = clone $oz;
                                $this->getDoctrine()->getManager()->persist($z);
                                $z->setAktywneOd(new \DateTime($z->getAktywneOd()));
                                if($oz->getAktywneDo() == "" || $oz->getBezterminowo()){
                                    $z->setAktywneDo(null);
                                }else{
                                    $z->setAktywneDo(new \DateTime($oz->getAktywneDo()));
                                }
                            }
                            //die(".".$oz->get_Idd());
                            $z->setCzyAktywne($wniosekId == 0);
                            $z->setCzyNadane(false);
                            $z->setWniosek($wniosek);
                            
                            $z->setPowodNadania($powod);
                            $z->setSamaccountname($currentsam);
                            
                            //\Doctrine\Common\Util\Debug::dump($z);die();
                            
                            $msg = "Dodaje usera ".$currentsam." do zasobu '".$this->get('renameService')->zasobNazwa($oz->getZasobId())."'.";//." bo go nie ma !";
                            if($wniosekId == 0)
                                $this->addFlash('warning', $msg);
                            $zmianaupr[] = $zasob->getOpis();
                            //print_r( );
/*
                        }
                        else{
                            $msg2 = ( "!!! pomijamy usera ".$currentsam." i zasob ".$oz->getZasobId()." bo juz go ma !");
                            $this->addFlash('notice', $msg2);
                            
                            //$this->get('session')->getFlashBag()->set('warning', $msg);
                        }
*/
                        if(count($zmianaupr) > 0 && $wniosekId == 0){
                            //var_dump($currentsam, $zmianaupr); die();
                            $this->get('uprawnieniaservice')->wyslij(array('cn' => '', 'samaccountname' => $currentsam, 'fromWhen' => new \Datetime()), $zmianaupr, array(), 'Zasoby', $oz->getZasobId(), $zasob->getAdministratorZasobu());
                        }
                    }
                }
                if($wniosek){
                    $wniosek->ustawPoleZasoby();
                }
                $this->getDoctrine()->getManager()->flush();
                
                if($wniosek){
                    $wniosek->ustawPoleZasoby();
                }
                $this->getDoctrine()->getManager()->flush();
                if($wniosekId != 0){
                    return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array('id' => $wniosekId)));
                }else{
                    return $this->redirect($this->generateUrl('main'));
                }
            }else{
                $ndata = $form->getData();
                print_r($ndata);
                $ee = array();
                foreach($form->getErrors() as $e)
                    $ee[] = $e->getMessage();
                
                print_r($ee);
                die('mam blad forma '.count($form->getErrors())." ".$form->getErrorsAsString());
            }
        }
        
        $tmpl = $wniosek ? 'ParpMainBundle:NadawanieUprawnienZasobow:addUserResourcesByWniosek.html.twig' : 'ParpMainBundle:NadawanieUprawnienZasobow:addUserResources.html.twig';
        
        return $this->render($tmpl, array(
            'wniosek' => $wniosek,
            'wniosekId' => $wniosekId,
            'users' => $users,
            'form' => $form->createView(),
            'action' => $action
        ));
        //print_r($ndata); die();
    }
    
        
}
