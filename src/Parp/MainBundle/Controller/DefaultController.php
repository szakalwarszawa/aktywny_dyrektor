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

class DefaultController extends Controller
{

    /**
     * @Route("/", name="main")
     * @Template()
     */
    public function indexAction()
    {
        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
//echo "<pre>"; print_r($ADUsers); die();
        $source = new Vector($ADUsers);

        $grid = $this->get('grid');

        $grid->setSource($source);

        $grid->hideColumns(array(
            'manager',
            //'info',
            'description',
            'division',
            //            'thumbnailphoto',
            'useraccountcontrol',
            'samaccountname',
            'initials'
        ));

        // Konfiguracja nazw kolumn

        $grid->getColumn('samaccountname')
                ->setTitle('Nazwa użytkownika')
                ->setOperators(array("like"))
                ->setOperatorsVisible(false)
                ->isPrimary();
        $grid->getColumn('name')
                ->setTitle("Nazwisko imię")
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('initials')
                ->setTitle("Inicjały")
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('title')
                ->setTitle("Stanowisko")
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('department')
                ->setTitle("Jednostka")
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('info')
                ->setTitle("Sekcja")
                ->setFilterType('select')
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('lastlogon')
                ->setTitle('Ostatnie logowanie')
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('accountexpires')
                ->setTitle('Umowa wygasa')
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('thumbnailphoto')
                ->setTitle('Zdj.')
                ->setFilterable(false);

        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);

        // Zdejmujemy filtr
        $grid->getColumn('akcje')
                ->setFilterable(false)
                ->setSafe(true);

        // Edycja konta
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'userEdit');
        $rowAction2->setColumn('akcje');
        $rowAction2->setRouteParameters(
                array('samaccountname')
        );
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-sitemap"></i> Struktura', 'structure');
        $rowAction3->setColumn('akcje');
        $rowAction3->setRouteParameters(
                array('samaccountname')
        );
        $rowAction3->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction4 = new RowAction('<i class="fa fa-database"></i> Zasoby', 'resources');
        $rowAction4->setColumn('akcje');
        $rowAction4->setRouteParameters(
                array('samaccountname')
        );
        $rowAction4->addAttribute('class', 'btn btn-success btn-xs');

//        $grid->addRowAction($rowAction1);
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
        $grid->addRowAction($rowAction4);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
        
        $massAction1 = new MassAction("Przypisz dodatkowe zasoby", 'ParpMainBundle:Default:processMassAction', false, array('action' => 'addResources'));
        $grid->addMassAction($massAction1);

        $massAction2 = new MassAction("Odbierz prawa do zasobów", 'ParpMainBundle:Default:processMassAction', false, array('action' => 'removeResources'));
        $grid->addMassAction($massAction2);     
        $massAction3 = new MassAction("Przypisz dodatkowe uprawnienia",'ParpMainBundle:Default:processMassAction', false, array('action' => 'addPrivileges'));
        $grid->addMassAction($massAction3);
        $massAction4 = new MassAction("Odbierz uprawnienia",'ParpMainBundle:Default:processMassAction', false, array('action' => 'removePrivileges'));
        $grid->addMassAction($massAction4);


        return $grid->getGridResponse();
    }
    /**
     * @return array
     * @Template();
     */
    public function processMassActionAction(Request $request, $action)
    {
        //print_r($action);
        //print_r($_POST);
        if (isset($_POST)) {
            $array = array_shift($_POST);
            if (isset($array['__action_id'])) {
                $action_id = $array['__action_id'];
            }
            if (isset($array['__action'])) {
                $actiond = $array['__action'];
            }
            $a = json_encode($actiond);
            return $this->redirect($this->generateUrl('addRemoveAccessToUsersAction', array('samaccountnames' => $a, 'action' => $action)));
        }
        die();    
    }
    
    /**
     * @param $samaccountName
     * @Route("/addRemoveAccessToUsersAction/{samaccountnames}/{action}", name="addRemoveAccessToUsersAction")
     */
    public function addRemoveAccessToUsersAction(Request $request, $samaccountnames, $action)
    {
        
        switch($action){
            case "addResources":
                $title = "Wybierz zasoby do dodania";
                $chs = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->findAll();
                break;
            case "removeResources":
                $title = "Odbierz zasoby";
                $chs = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->findAll();
                break;
            case "addPrivileges":
                $title = "Wybierz uprawnienia do dodania";
                $chs = $this->getDoctrine()->getRepository('ParpMainBundle:Uprawnienia')->findAll();
                break;
            case "removePrivileges":
                $title = "Wybierz uprawnienia do odebrania";
                $chs = $this->getDoctrine()->getRepository('ParpMainBundle:Uprawnienia')->findAll();
                break;
        }
        
        $choices = array();
        foreach($chs as $ch){
            if($action == "addResources" || $action == "removeResources")
                $choices[$ch->getId()] = $ch->getNazwa();
            if($action == "addPrivileges" || $action == "removePrivileges")
                $choices[$ch->getId()] = $ch->getOpis();
        }
        return $this->addRemoveAccessToUsers($request, $samaccountnames, $choices, $title, $action);    
    }
    
    protected function addRemoveAccessToUsers(Request $request, $samaccountnames, $choices, $title, $action)
    {
        //print_r($samaccountnames);
        $ldap = $this->get('ldap_service');
        $samaccountnames = json_decode($samaccountnames);
        $users = array();
        foreach($samaccountnames as $sam => $v){
            if($v){
                
                $ADUser = $ldap->getUserFromAD($sam);
                $users[] = $ADUser[0];
            }
        }
        
        $now = new \Datetime();
        
        $builder = $this->createFormBuilder();
        $form = $builder
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
                        'class' => 'form-control',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zmiany',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'data' => $now->format("d-m-Y")
                ))
                ->add('powod', 'textarea', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'label' => 'Powód nadania/odebrania',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => true
                ))

                ->add('access', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => $title,
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control select2',
                    ),
                    'choices' => $choices,
                    'multiple' => true,
                    'expanded' => false
                ))

                ->add('zapisz', 'submit', array(
                    'attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                    ),
                ))
                ->setMethod('POST')
                ->getForm();
        
        $form->handleRequest($request);

        if ($form->isValid()) {
            $ndata = $form->getData();
            switch($ndata['action']){
                case "addResources":
                    return $this->addResourcesToUsersAction($request, $ndata);        
                    break;
                
                case "removeResources":
                    $sams = array();
                    $s1 = json_decode($ndata['samaccountnames']);
                    foreach($s1 as $k => $v){
                        if($v)
                            $sams[] = $k;
                    }
                    $powod = $ndata['powod'];
                    //print_r($ndata); die();
                    foreach($ndata['access'] as $z){
                        
                        foreach($sams as $currentsam){
                            $suz = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserZasoby')->findOneBy(array('samaccountname' => $currentsam, 'zasobId' => $z));
                            if($suz){
                                $suz->setAktywneDo(new \Datetime($ndata['fromWhen']));
                                $suz->setCzyAktywne(false);
                                $suz->setPowodOdebrania($powod);
                                $this->getDoctrine()->getManager()->persist($suz);
                                //$this->getDoctrine()->getManager()->remove($suz);
                                $msg = "Zabiera userowi ".$currentsam." uprawnienia do zasobu ".$z." bo je ma";
                                $this->addFlash('warning', $msg);
                            }else{
                                
                                $msg = "NIE zabiera userowi ".$currentsam." uprawnienia do zasobu ".$z." bo ich nie ma !";
                                $this->addFlash('notice', $msg);
                            }
                            
                        }
                    }
                    $this->getDoctrine()->getManager()->flush();
                    return $this->redirect($this->generateUrl('main'));
                    break;
                case "addPrivileges":
                    $sams = array();
                    $s1 = json_decode($ndata['samaccountnames']);
                    foreach($s1 as $k => $v){
                        if($v)
                            $sams[] = $k;
                    }
                    $powod = $ndata['powod'];
                    //print_r($ndata); die();
                    foreach($ndata['access'] as $z){
                        
                        foreach($sams as $currentsam){
                            $suz = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserUprawnienia')->findOneBy(array('samaccountname' => $currentsam, 'uprawnienie_id' => $z));
                            if($suz){
                                $msg = "NIE nadaje userowi ".$currentsam." uprawnienia  ".$z." bo je ma !";
                                $this->addFlash('notice', $msg);
                                
                            }else{
                                $u = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Uprawnienia')->find($z);
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
                                $msg = "Nadaje userowi ".$currentsam." uprawnienia  ".$z." bo ich nie ma";
                                $this->addFlash('warning', $msg);
                            }
                            
                        }
                    }
                    $this->getDoctrine()->getManager()->flush();
                    return $this->redirect($this->generateUrl('main'));
                    break;
                case "removePrivileges":
                    $sams = array();
                    $s1 = json_decode($ndata['samaccountnames']);
                    foreach($s1 as $k => $v){
                        if($v)
                            $sams[] = $k;
                    }
                    $powod = $ndata['powod'];
                    //print_r($ndata); die();
                    foreach($ndata['access'] as $z){
                        
                        foreach($sams as $currentsam){
                            $suz = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserUprawnienia')->findOneBy(array('samaccountname' => $currentsam, 'uprawnienie_id' => $z));
                            if($suz){
                                
                                $u = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Uprawnienia')->find($z);
                                $suz->setDataOdebrania(new \Datetime($ndata['fromWhen']));
                                $suz->setCzyAktywne(false);
                                $suz->setPowodOdebrania($powod);
                                $this->getDoctrine()->getManager()->persist($suz);
                                //$this->getDoctrine()->getManager()->remove($suz);
                                $msg = "Odbieram userowi ".$currentsam." uprawnienia  ".$z." bo je ma";
                                $this->addFlash('warning', $msg);
                            }else{
                                $msg = "NIE odbieram userowi ".$currentsam." uprawnienia  ".$z." bo ich nie ma !";
                                $this->addFlash('notice', $msg);
                            }
                            
                        }
                    }
                    $this->getDoctrine()->getManager()->flush();
                    return $this->redirect($this->generateUrl('main'));
                    break;
            }
            
        }
        //print_r($users);
        return $this->render('ParpMainBundle:Default:addRemoveUserAccess.html.twig', array(
            'users' => $users,
            'form' => $form->createView() ,
            'title' => $title  
        ));
    }
    
    /**
     * @param $samaccountName
     * @Route("/addResourcesToUsers/", name="addResourcesToUsers")
     */
    
    public function addResourcesToUsersAction(Request $request, $ndata = null)
    {
        //print_r($ndata); die();
        $action = "addResources";
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
        if($ndata == null){
            //die('mam nulla');
            $zids = array();
            //print_r($_POST['form']['userzasoby']);
            //$ndata2 = $form->getData();
            foreach($_POST['form']['userzasoby'] as $v){
                $zids[] = $v['zasobId'];
            }
            
        }else{
            $samaccountnames = json_decode($ndata['samaccountnames']);
            $ldap = $this->get('ldap_service');
            $users = array();
            foreach($samaccountnames as $sam => $v){
                if($v){
                    
                    $ADUser = $ldap->getUserFromAD($sam);
                    $users[] = $ADUser[0];
                }
            }
            $now = new \Datetime();                            
            $samaccountnamesPars['data'] = json_encode($samaccountnames);
            $fromWhenPars['data'] = $now->format("d-m-Y");
            $zids = $ndata['access'];
            $powodPars['data'] = $ndata['powod'];
            //$userzasobyPars['data'] = array();//$userzasoby;    
        }
        foreach($zids as $v){
            $z = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->find($v);
            //echo ".".count($z->getUzytkownicy()).".";
            $uz = new UserZasoby();
            $uz->setZasobId($z->getId());
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
        
        
        //print_r($userzasoby);
        $form = $this->createFormBuilder()
            ->add('action', 'hidden', array(
                'data' => $action
            ))
            ->add('samaccountnames', 'hidden', $samaccountnamesPars)
            ->add('fromWhen', 'hidden', $fromWhenPars)
            ->add('powod', 'hidden', $powodPars)
        ->add('userzasoby','collection', array(
            'type' => new UserZasobyType($choicesModul, $choicesPoziomDostepu),
            'allow_add'    => true,
            'allow_delete'    => true,
            'by_reference' => false,
            'label' => "Enrolments",
            'prototype' => true,
            'cascade_validation' => true,
            'data' => $userzasoby
        ))
            ->add('zapisz', 'submit', array(
                'attr' => array(
                    'class' => 'btn btn-success col-sm-12',
                ),
            ))
            ->setAction($this->generateUrl('addResourcesToUsers'))
            ->setMethod('POST')
            ->getForm();
            
        if($ndata == null){
            $form->handleRequest($request);

            if ($form->isValid()) {
                $ndata = $form->getData();
                //print_r($ndata);
                //tworzy przypisania do zasobow
                $sams = array();
                $s1 = json_decode($ndata['samaccountnames']);
                foreach($s1 as $k => $v){
                    if($v)
                        $sams[] = $k;
                }
                $msg = "";
                $msg2 = "";
                $powod = $ndata['powod'];
                foreach($ndata['userzasoby'] as $oz){
                    foreach($sams as $currentsam){
                        $suz = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserZasoby')->findOneBy(array('samaccountname' => $currentsam, 'zasobId' => $oz->getZasobId()));
                        //print_r($suz);
                        if($suz == null){
                            $z = clone $oz;
                            $z->setCzyAktywne(true);
                            $z->setAktywneOd(new \DateTime($z->getAktywneOd()));
                            $z->setAktywneDo(new \DateTime($z->getAktywneDo()));
                            
                            $z->setPowodNadania($powod);
                            $z->setSamaccountname($currentsam);
                            $this->getDoctrine()->getManager()->persist($z);
                            $msg = "Dodaje usera ".$currentsam." i zasob ".$oz->getZasobId()." bo go nie ma !";
                            $this->addFlash('warning', $msg);
                            //print_r( );
                        }
                        else{
                            $msg2 = ( "!!! pomijamy usera ".$currentsam." i zasob ".$oz->getZasobId()." bo juz go ma !");
                            $this->addFlash('notice', $msg2);
                            
                            //$this->get('session')->getFlashBag()->set('warning', $msg);
                        }
                    }
                }
                
                $this->getDoctrine()->getManager()->flush();
            
                return $this->redirect($this->generateUrl('main'));
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
        return $this->render('ParpMainBundle:Default:addUserResources.html.twig', array(
            'users' => $users,
            'form' => $form->createView()
        ));
        //print_r($ndata); die();
    }
    
    
    

    /**
     * @param $samaccountName
     * @Route("/user/{samaccountname}/getphoto", name="userGetPhoto")
     */
    public function photoGetAction($samaccountname)
    {
        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);

        $picture = $ADUser[0]["thumbnailphoto"];

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'image/jpg');
        $response->headers->set('Content-Length', strlen($picture));
        $response->setContent($picture);
        return $response;
    }

    /**
     * @param $samaccountName
     * @Route("/user/{samaccountname}/photo", name="userPhoto")
     * @Template();
     */
    public function photoAction($samaccountname)
    {
        return array(
            'account' => $samaccountname,
        );
    }

    /**
     * @Route("/user/{samaccountname}/edit", name="userEdit");
     * @Template();
     */
    function editAction($samaccountname, Request $request)
    {
        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);

        $ADManager = $ldap->getUserFromAD(null, substr($ADUser[0]['manager'], 0, stripos($ADUser[0]['manager'], ',')));

        // wyciagnij imie i nazwisko managera z nazwy domenowej
        $ADUser[0]['manager'] = mb_substr($ADUser[0]['manager'], mb_stripos($ADUser[0]['manager'], '=') + 1, (mb_stripos($ADUser[0]['manager'], ',OU')) - (mb_stripos($ADUser[0]['manager'], '=') + 1));

        $defaultData = $ADUser[0];

        // pobierz uprawnienia poczatkowe
        $initialrights = $this->getDoctrine()->getRepository('ParpMainBundle:UserGrupa')->findBy(array('samaccountname' => $ADUser[0]['samaccountname']));
        if (!empty($initialrights)) {
            foreach($initialrights as $initialright)
                $defaultData['initialrights'][] = $initialright->getGrupa();
                
            //$defaultData['initialrights'] = implode(",", $defaultData['initialrights']);
        }else{
            $defaultData['initialrights'] = null;
        }
        $previousData = $defaultData;

        // Pobieramy listę stanowisk
        $titlesEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Position')->findBy(array(), array('name' => 'asc'));
        $titles = array();
        foreach ($titlesEntity as $tmp) {
            $titles[$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Biur i Departamentów
        $departmentsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findBy(array(), array('name' => 'asc'));
        $departments = array();
        foreach ($departmentsEntity as $tmp) {
            $departments[$tmp->getName()] = $tmp->getName();
        }
        // Pobieramy listę Sekcji
        $sectionsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Section')->findBy(array(), array('name' => 'asc'));
        $sections = array();
        foreach ($sectionsEntity as $tmp) {
            $sections[$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Uprawnien
        $rightsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:GrupyUprawnien')->findBy(array(), array('opis' => 'asc'));
        $rights = array();
        foreach ($rightsEntity as $tmp) {
            $rights[$tmp->getKod()] = $tmp->getOpis();
        }
        $now = new \Datetime();
        $zasoby = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findNameByAccountname($samaccountname);
        for($i = 0; $i < count($zasoby); $i++){
            $uz = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->find($zasoby[$i]['id']);
            $html = "";
            if($uz->getLoginDoZasobu() != "")
                $html .= "<b>Login:</b> ".$uz->getLoginDoZasobu()."<br>";
            if($uz->getModul() != "")
                $html .= "<b>Moduł:</b> ".$uz->getModul()."<br>";
            if($uz->getPoziomDostepu() != "")
                $html .= "<b>Poziom dostępu:</b> ".$uz->getPoziomDostepu()."<br>";
            if($uz->getAktywneOd() != "")
                $html .= "<b>Aktywne od:</b> ".$uz->getAktywneOd()->format("Y-m-d")."<br>";
            if($uz->getAktywneDo() != "")
                $html .= "<b>Aktywne do:</b> ".$uz->getAktywneDo()->format("Y-m-d")." ".($uz->getBezterminowo() ? "(bezterminowo)" : "")."<br>";
            if($uz->getKanalDostepu() != "")
                $html .= "<b>Kanał dostępu:</b> ".$uz->getKanalDostepu()."<br>";
            if($uz->getUprawnieniaAdministracyjne() != "")
                $html .= "<b>Uprawnienia Administracyjne:</b> TAK<br>";
            $zasoby[$i]['opisHtml'] = $html;
            $zasoby[$i]['modul'] = $uz->getModul();
            $zasoby[$i]['loginDoZasobu'] = $uz->getLoginDoZasobu();
            $zasoby[$i]['poziomDostepu'] = $uz->getPoziomDostepu();
            $zasoby[$i]['aktywneOd'] = $uz->getAktywneOd()->format("Y-m-d");
            $zasoby[$i]['aktywneDo'] = $uz->getAktywneDo()->format("Y-m-d");
            $zasoby[$i]['kanalDostepu'] = $uz->getKanalDostepu();
        }
        
        $form = $this->createFormBuilder($defaultData)
                ->add('samaccountname', 'text', array(
                    'required' => false,
                    'read_only' => true,
                    'label' => 'Nazwa konta',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))
                ->add('name', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Imię i nazwisko',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))
                ->add('initials', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Inicjały',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))
                ->add('title', 'choice', array(
//                'class' => 'ParpMainBundle:Position',
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Stanowisko',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'data' => $defaultData["title"],
                    'choices' => $titles,
//                'mapped'=>false,
                ))
                ->add('info', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Sekcja',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $sections,
                    'data' => $defaultData['info'],
                ))
                ->add('department', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Biuro / Departament',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $departments,
                    'data' => $defaultData["department"],
                ))
                ->add('zapisz', 'submit', array(
                    'attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                    ),
                ))
                ->add('manager', 'text', array(
                    'required' => false,
                    'read_only' => true,
                    'label' => 'Przełożony',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'data' => $defaultData['manager']
                ))
                ->add('fromWhen', 'text', array(
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
                    'required' => false,
                    'data' => $now->format("d-m-Y")
                ))
                ->add('initialrights', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Uprawnienia początkowe',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $rights,
                    'data' => ($defaultData["initialrights"]),
                    'multiple' => true,
                    'expanded' => false
                ))
                ->setMethod('POST')
                ->getForm();



        $form->handleRequest($request);

        if ($form->isValid()) {
            $ndata = $form->getData();
            $newrights = $ndata['initialrights'];
            $odata = $previousData;
            $roznicauprawnien = (($ndata['initialrights'] != $odata['initialrights']));
            unset($ndata['initialrights']);
            unset($odata['initialrights']);
            //$df = array_diff($form->getData(), $previousData);
            //echo "<pre>"; print_r($previousData); print_r($form->getData()); die();
            if (0 < count(array_diff($ndata, $odata)) || $roznicauprawnien) {
                //  Mamy zmianę, teraz trzeba wyodrebnić co to za zmiana
                // Tworzymy nowy wpis w bazie danych
                $entry = new Entry();
                $entry->setSamaccountname($samaccountname);
                $entry->setDistinguishedName($previousData["distinguishedname"]);
                $newData = array_diff($ndata, $odata);
                if(($roznicauprawnien)){
                    $value = implode(",", $newrights);
                    $entry->setInitialrights($value);
                }
                foreach ($newData as $key => $value) {
                    switch ($key) {
                        case "name":
                            $entry->setCn($value);
                            break;
                        case "initials":
                            $entry->setInitials($value);
                            break;
                        case "accountexpires":
                            $entry->setAccountexpires($value);
                            break;
                        case "title":
                            $entry->setTitle($value);
                            break;
                        case "info":
                            $entry->setInfo($value);
                            break;
                        case "department":
                            $entry->setDepartment($value);
                            break;
                        case "manager":
                            $entry->setManager($value);
                            break;
                        case "fromWhen":
                            $entry->setFromWhen(new \DateTime($value));
                            break;
                        case "initialrights"://nieuzywane bo teraz jako array idzie
                            $value = implode(",", $value);
                            $entry->setInitialrights($value);
                            break;
                    }
                }
                if (!$entry->getFromWhen())
                    $entry->setFromWhen(new \DateTime('tomorrow'));

                $this->getDoctrine()->getManager()->persist($entry);
                $this->getDoctrine()->getManager()->flush();

                return $this->redirect($this->generateUrl('main'));
            }
        }
        $uprawnienia = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserUprawnienia')->findBy(array('samaccountname' => $samaccountname, 'czyAktywne' => true));


        return array(
            'user' => $ADUser[0],
            'form' => $form->createView(),
            'zasoby' => $zasoby,
            'uprawnienia' => $uprawnienia
                //'manager' => isset($ADManager[0]) ? $ADManager[0] : "",
        );
    }

    /**
     * @Route("/user/add", name="userAdd")
     * @Template()
     */
    public function addAction(Request $request)
    {
        // Sięgamy do AD:
        // $ldap = $this->get('ldap_service');
        // $ADUser = $ldap->getUserFromAD($samaccountname);
        //$ADManager = $ldap->getUserFromAD(null, substr($ADUser[0]['manager'], 0, stripos($ADUser[0]['manager'], ',')));
        //$defaultData = $ADUser[0];
        //$previousData = $defaultData;
        $em = $this->getDoctrine()->getManager();

        // Pobieramy listę stanowisk
        $titlesEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Position')->findBy(array(), array('name' => 'asc'));
        $titles = array();
        foreach ($titlesEntity as $tmp) {
            $titles[$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Biur i Departamentów
        $departmentsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findBy(array(), array('name' => 'asc'));
        $departments = array();
        foreach ($departmentsEntity as $tmp) {
            $departments[$tmp->getName()] = $tmp->getName();
        }
        // Pobieramy listę Sekcji
        $sectionsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Section')->findBy(array(), array('name' => 'asc'));
        $sections = array();
        foreach ($sectionsEntity as $tmp) {
            $sections[$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Uprawnien
        $rightsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:GrupyUprawnien')->findBy(array(), array('opis' => 'asc'));
        $rights = array();
        foreach ($rightsEntity as $tmp) {
            $rights[$tmp->getKod()] = $tmp->getOpis();
        }

        $entry = new Entry();
        $form = $this->createFormBuilder($entry)
                ->add('samaccountname', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Nazwa konta',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    )
                ))->add('cn', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Imię i nazwisko',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))->add('department', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Biuro / Departament',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $departments,
                        //'data' => $defaultData["department"],
                ))->add('initials', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Inicjały',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))->add('title', 'choice', array(
//                'class' => 'ParpMainBundle:Position',
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Stanowisko',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    //'data' => $defaultData["title"],
                    'choices' => $titles,
//                'mapped'=>false,
                ))
                ->add('info', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Sekcja',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $sections,
                        //'data' => $defaultData['info'],
                ))
                ->add('manager', 'text', array(
                    'required' => false,
                    'read_only' => true,
                    'label' => 'Przełożony',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))
                ->add('accountExpires', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'widget' => 'single_text',
                    'label' => 'Data wygaśnięcia konta',
                    'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-lg-4 control-label',
                    ),
                    'required' => false,
                ))
                ->add('fromWhen', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'widget' => 'single_text',
                    'label' => 'Data zmiany',
                    'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-lg-4 control-label',
                    ),
                    'required' => false,
                ))
                /* ->add('rights', 'choice', array(
                  'constraints' => array(
                  new NotBlank(array('message' => 'Nie wybrano uprawnień początkowych'))),
                  'mapped' => false,
                  'required' => false,
                  'read_only' => false,
                  'label' => 'Uprawnienia początkowe',
                  'label_attr' => array(
                  'class' => 'col-sm-4 control-label',
                  ),
                  'attr' => array(
                  'class' => 'form-control',
                  ),
                  'choices' => $rights,
                  )) */
                ->add('initialrights', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Uprawnienia początkowe',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $rights,
                ))
                ->add('zapisz', 'submit', array(
                    'attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                    ),
                ))->setMethod('POST')
                ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // perform some action, such as saving the task to the database
            // utworz distinguishedname
            $tab = explode(".", $this->container->getParameter('ad_domain'));
            $ou = explode(".", $this->container->getParameter('ad_ou'));
            $department = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findOneByName($entry->getDepartment());

            $distinguishedname = "CN=" . $entry->getCn() . ", OU=" . $department->getShortname() . ",".$ou.", DC=" . $tab[0] . ",DC=" . $tab[1];

            $entry->setDistinguishedName($distinguishedname);
            $em->persist($entry);
            $em->flush();
            return $this->redirect($this->generateUrl('main'));
        }

        return $this->render('ParpMainBundle:Default:add.html.twig', array('form' => $form->createView()));
    }

    /**
     * @Route("/structure/{samaccountname}", name="structure")
     * @Template()
     */
    public function structureAction($samaccountname)
    {
        $ldap = $this->get('ldap_service');
        // Pobieramy naszego pracownika
        $ADUser = $ldap->getUserFromAD($samaccountname);

        // Pobieramy naszego przełożonego
        $mancn = str_replace("CN=", "", substr($ADUser[0]['manager'], 0, stripos($ADUser[0]['manager'], ',')));
        $ADManager = $ldap->getUserFromAD(null, $mancn);

        // Pobieramy wszystkich jego pracowników (w których występuje jako przełożony)
        $ADWorkers = $ldap->getUserFromAD(null, null, "manager=" . $ADUser[0]["distinguishedname"]."");

        //echo "<pre>";print_r($ADManager); print_r($ADUser); print_r($ADWorkers);die();

        return array(
            'przelozony' => isset($ADManager[0]) ? $ADManager[0] : "",
            'pracownik' => $ADUser[0],
            'pracownicy' => $ADWorkers,
        );
    }

    /**
     * @Route("/engage/{samaccountname}/{rok}", name="engageUser")
     * @Route("/engage/{samaccountname}", name="engageUser")
     * @Template()
     */
    public function engagementAction($samaccountname, $rok = null, Request $request)
    {
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);
        $engagements = $this->getDoctrine()->getRepository('ParpMainBundle:Engagement')->findAll();
        $userEngagements = $this->getDoctrine()->getRepository('ParpMainBundle:UserEngagement')->findBy(array('samaccountname' => $samaccountname));

        $em = $this->getDoctrine()->getManager();

        $year = $request->query->get('year');

        if (empty($year)) {
            $date = new \DateTime();
            $year = $date->format("Y");
        }

        if ($request->getMethod() == "POST") {

            $dane = $this->get('request')->request->all();
            //var_dump($dane);
            $year = !empty($dane['year']) ? $dane['year'] : $year;

            foreach ($dane['angaz'] as $key_angaz => $value_angaz) {
                // var_dump($key_angaz);                
                $engagement = $em->getRepository('ParpMainBundle:Engagement')->findOneBy(array('name' => $key_angaz));

                $last_value_angaz = "";
                $last_month = null;
                //petla po miesiacach
                foreach ($value_angaz as $key_month => $value_month) {

                    $userEngagement = $em->getRepository('ParpMainBundle:UserEngagement')->findOneByCryteria($samaccountname, $engagement->getId(), $this->getMonthFromStr($key_month), $year);

                    // obsluga atomatycznego uzupełniania
                    // czysc przy nowym zaangazowaniu
                    if ($last_value_angaz !== $value_angaz) {
                        $last_value_angaz = $value_angaz;
                        $last_month = null;
                    }
                    $last_month = !empty($value_month) ? $value_month : $last_month;

                    if (empty($userEngagement)) {
                        $userEngagement = new UserEngagement();
                        $userEngagement->setSamaccountname($samaccountname);
                        $userEngagement->setEngagement($engagement);
                        $userEngagement->setYear($year);
                        $userEngagement->setMonth($this->getMonthFromStr($key_month));
                        //$percent = (!empty($value_month)) ? $value_month : null;
                        //$userEngagement->setPercent($percent);
                        $userEngagement->setPercent($last_month);

                        $em->persist($userEngagement);
                        $em->flush();
                    } else {
                        //$percent = (!empty($value_month)) ? $value_month : null;
                        //$userEngagement->setPercent($percent);
                        $userEngagement->setPercent($last_month);
                        $em->persist($userEngagement);
                        $em->flush();
                    }
                }
            }
            return $this->redirect($this->generateUrl('engageUser', array('samaccountname' => $samaccountname, 'year' => $year)));
        }

        $userEngagements = $em->getRepository('ParpMainBundle:UserEngagement')->findBySamaccountnameAndYear($samaccountname, $year);

        $dane = array();
        foreach ($userEngagements as $userEngagement) {
            //echo $userEngagement->getSamaccountname() . ' ' . $userEngagement->getEngagement() . ' ' . $userEngagement->getPercent() . ' ' . $userEngagement->getMonth() . ' ' . $userEngagement->getYear() . "<br>";
            // zbuduj tablice z danymi
            $engagement = (string) $userEngagement->getEngagement();
            $month = $this->getStrFromMonth($userEngagement->getMonth());
            $percent = $userEngagement->getPercent();
            $dane[$engagement][$month] = $percent;
        }

        // policz sumy
        $sumy = array(
            'sumSty' => 0,
            'sumLut' => 0,
            'sumMar' => 0,
            'sumKwi' => 0,
            'sumMaj' => 0,
            'sumCze' => 0,
            'sumLip' => 0,
            'sumSie' => 0,
            'sumWrz' => 0,
            'sumPaz' => 0,
            'sumLis' => 0,
            'sumGru' => 0,
        );

        foreach ($userEngagements as $userEngagement) {

            $month = $this->getStrFromMonth($userEngagement->getMonth());
            $percent = $userEngagement->getPercent();
            $percent = !empty($percent) ? $percent : 0;

            switch ($month) {
                case "sty":
                    $sumy['sumSty'] += $percent;
                    break;
                case "lut":
                    $sumy['sumLut'] += $percent;
                    break;
                case "mar":
                    $sumy['sumMar'] += $percent;
                    break;
                case "kwi":
                    $sumy['sumKwi'] += $percent;
                    break;
                case "maj":
                    $sumy['sumMaj'] += $percent;
                    break;
                case "cze":
                    $sumy['sumCze'] += $percent;
                    break;
                case "lip":
                    $sumy['sumLip'] += $percent;
                    break;
                case "sie":
                    $sumy['sumSie'] += $percent;
                    break;
                case "wrz":
                    $sumy['sumWrz'] += $percent;
                    break;
                case "paz":
                    $sumy['sumPaz'] += $percent;
                    break;
                case "lis":
                    $sumy['sumLis'] += $percent;
                    break;
                case "gru":
                    $sumy['sumGru'] += $percent;
                    break;
            }
        }

//        if($form->isValid()){
//            $realEngagment = $this->getDoctrine()
//                ->getRepository('ParpMainBundle:UserEngagement')
//                ->findOneBy(array(
//                    'samaccountname'=>$samaccountname,
//                    'engagement'=>$userEngagement->getEngagement()));
//            if(!$realEngagment)
//                $realEngagment = new UserEngagement();
//            $realEngagment->setSamaccountname($samaccountname);
//            $realEngagment->setPercent($userEngagement->getPercent());
//            $realEngagment->setEngagement($userEngagement->getEngagement());
//            $this->getDoctrine()->getManager()->persist($realEngagment);
//            $this->getDoctrine()->getManager()->flush();
//
//            return $this->redirect($this->generateUrl('engageUser',array('samaccountname'=>$samaccountname)));
//        }

        return array(
            'engagements' => $engagements,
            'userEngagements' => $userEngagements,
            'user' => $ADUser[0],
            'dane' => $dane,
            'year' => $year,
            'sumy' => $sumy,
//            'form' => $form->createView(),
        );
    }

    protected function getMonthFromStr($month)
    {
        $tab = array('sty' => 1,
            'lut' => 2,
            'mar' => 3,
            'kwi' => 4,
            'maj' => 5,
            'cze' => 6,
            'lip' => 7,
            'sie' => 8,
            'wrz' => 9,
            'paz' => 10,
            'lis' => 11,
            'gru' => 12);
        return $tab[$month];
    }

    protected function getStrFromMonth($month)
    {
        $tab = array(
            1 => 'sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'paz', 'lis', 'gru'
        );
        return $tab[$month];
    }

    /**
     * @Route("/suggestinitials", name="suggest_initials")
     * 
     */
    public function ajaxSuggestInitials(Request $request)
    {
        $post = ($request->getMethod() == 'POST');
        $ajax = $request->isXMLHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
        if ((!$ajax) OR ( !$post)) {
            return null;
        }

        $samaccountname = $request->get('samaccountname', null);
        if (empty($samaccountname)) {
            throw new \Exception('Nie przekazano nazwy konta!');
        }
        $department = $request->get('department', null);
        $cn = $request->get('cn', null);

        $ldap = $this->get('ldap_service');

        if (empty($department)) {
            $ADUser = $ldap->getUserFromAD($samaccountname);
            // jezeli juz ma to zwrćc ma
            if (!empty($ADUser[0]['initials'])) {
                $initials = $ADUser[0]['initials'];
                return $this->render('ParpMainBundle:Default:suggestinitials.html.twig', array('initials' => $initials));
            }
            $description = $ADUser[0]['description'];
        } else {
            $description = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findOneByName($department)->getShortname();
        }

        //pobierz userow z biura
        $users = $ldap->getUsersFromOU($description);

        $temp0 = !empty($ADUser[0]['name']) ? $ADUser[0]['name'] : $cn;
        // rozbijaj imie i nazwisko na 2 lub 3 części
        $temp1 = split(" ", $temp0);
        $words = array();
        $temp2 = "";
        if (strpos($temp1[0], '-') !== false) {
            $temp2 = split('-', $temp1[0]);
            $words[1] = $temp2[0];
            $words[2] = $temp2[1];
            $words[0] = $temp1[1];
        } else {
            $words[0] = $temp1[1];
            $words[1] = $temp1[0];
        }

        $j = 1;
        $initials = "";
        $czy_znaleziono = false;
        while (true) {
            // zeby nie zablokować skryptu po 100 iteracji skonczymy
            if ($j > 100)
                break;
            // stworz inicjały
            $initials = "";
            $czy_znaleziono = false;
            for ($k = 0; $k < count($words); $k++) {
                if ($k == 1) {
                    $letter = mb_substr($words[$k], 0, $j, 'UTF-8');
                    $initials .= $letter;
                } else {
                    $letter = mb_substr($words[$k], 0, 1, 'UTF-8');
                    $initials .= $letter;
                }
            }

            // sprawdz czy isnieje w tablicy z inicjałami z biura
            foreach ($users as $user) {
                if ($user['initials'] == $initials) {
                    $czy_znaleziono = true;
                    break;
                }
            }

            // jezeli nie znaleziono wyjdz z petli
            if ($czy_znaleziono == false) {
                break;
            }
            $j++;
        }
        $initials = mb_strtoupper($initials, 'UTF-8');

        return $this->render('ParpMainBundle:Default:suggestinitials.html.twig', array('initials' => $initials));
    }

    /**
     * @Route("/findmanager", name="find_manager")
     * 
     */
    public function ajaxFindManager(Request $request)
    {

        $post = ($request->getMethod() == 'POST');
        $ajax = $request->isXMLHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
        if ((!$ajax) OR ( !$post)) {
            return null;
        }

        $imienazwisko = $request->get('imienazwisko', null);
        if (empty($imienazwisko)) {
            throw new \Exception('Nie przekazano imieni i nazwiska!');
        }

        $ldap = $this->get('ldap_service');

        $ADUsers = $ldap->getAllFromAD();

        $dane = array();
        $i = 0;
        foreach ($ADUsers as $user) {

            if (mb_stripos($user['name'], $imienazwisko, 0, 'UTF-8') !== FALSE) {
                $dane[$i] = $user['name'];
                $i++;
            }
        }
        return $this->render('ParpMainBundle:Default:findmanager.html.twig', array('dane' => $dane));
    }

    /**
     * @Route("/file_ecm", name="form_file_ecm")
     * @Template()
     */
    public function formFileEcmAction(Request $request)
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
                            'mimeTypes' => array('text/csv', 'text/plain'),
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

                if ($this->wczytajPlik($file)) {
                    $this->get('session')->getFlashBag()->set('warning', 'Plik został wczytany poprawnie.');
                    return $this->redirect($this->generateUrl('main'));
                }
            }
        }

        return $this->render('ParpMainBundle:Default:formfileecm.html.twig', array('form' => $form->createView()));
    }

    protected function ldap_escape($subject, $dn = FALSE, $ignore = NULL)
    {

        // The base array of characters to escape
        // Flip to keys for easy use of unset()
        $search = array_flip($dn ? array('\\', ',', '=', '+', '<', '>', ';', '"', '#') : array('\\', '*', '(', ')', "\x00"));

        // Process characters to ignore
        if (is_array($ignore)) {
            $ignore = array_values($ignore);
        }
        for ($char = 0; isset($ignore[$char]); $char++) {
            unset($search[$ignore[$char]]);
        }

        // Flip $search back to values and build $replace array
        $search = array_keys($search);
        $replace = array();
        foreach ($search as $char) {
            $replace[] = sprintf('\\%02x', ord($char));
        }

        // Do the main replacement
        $result = str_replace($search, $replace, $subject);

        // Encode leading/trailing spaces in DN values
        if ($dn) {
            if ($result[0] == ' ') {
                $result = '\\20' . substr($result, 1);
            }
            if ($result[strlen($result) - 1] == ' ') {
                $result = substr($result, 0, -1) . '\\20';
            }
        }

        return $result;
    }

    protected function wczytajPlik($file)
    {
        $dane = file_get_contents($file->getPathname());
        // $xxx = iconv('windows-1250', 'utf-8', $dane );

        $list = explode("\n", $dane);
        $ldap = $this->get('ldap_service');

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('delete from Parp\MainBundle\Entity\UserZasoby');
        $numDeleted = $query->execute();
        $wiersz2getter = array(
            3 => "LoginDoZasobu",
            4 => "Modul",
            5 => "PoziomDostepu",
            6 => "AktywneOd",
            7 => "Bezterminowo",
            8 => "AktywneDo",
            9 => "KanalDostepu",
            10 => "UprawnieniaAdministracyjne",
            11 => "OdstepstwoOdProcedury",
        );
        $pierwszyWiersz = explode(";", $list[0]);
        $komorka = $pierwszyWiersz[0];
        if($komorka == "Nazwa zasobu"){
            $this->wczytajPlikZasoby($file);
        }else{
            $this->wczytajPlikZasobyUser($file);
        }
        return true;
    }
    protected function wczytajPlikZasoby($file)
    {
        //$dane = file_get_contents($file->getPathname());

        $handle = fopen($file->getPathname(),'r');
        $ldap = $this->get('ldap_service');

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('delete from Parp\MainBundle\Entity\UserZasoby');
        $numDeleted = $query->execute();
        $wiersz2getter = array(
            1 => "WlascicielZasobu",
            2 => "AdministratorZasobu",
            3 => "AdministratorTechnicznyZasobu",
            4 => "Uzytkownicy",
            5 => "DaneOsobowe",
            6 => "KomorkaOrgazniacyjna",
            7 => "MiejsceInstalacji",
            8 => "OpisZasobu",
            9 => "ModulFunkcja",
            10 => "PoziomDostepu",
            11 => "DataZakonczeniaWdrozenia",
            12 => "Wykonawca",
            13 => "NazwaWykonawcy",
            14 => "AsystaTechniczna",
            15 => "DataWygasnieciaAsystyTechnicznej",
            16 => "DokumentacjaFormalna",
            17 => "DokumentacjaProjektowoTechniczna",
            18 => "Technologia",
            19 => "TestyBezpieczenstwa",
            20 => "TestyWydajnosciowe",
            21 => "DataZleceniaOstatniegoPrzegladuUprawnien",
            22 => "InterwalPrzegladuUprawnien",
            23 => "DataZleceniaOstatniegoPrzegladuAktywnosci",
            24 => "InterwalPrzegladuAktywnosci",
            25 => "DataOstatniejZmianyHaselKontAdministracyjnychISerwisowych",
            26 => "InterwalZmianyHaselKontaAdministracyjnychISerwisowych"
        );
        $tablica = array();
        $out = $this->poprawPlikCsv($file);
        $out = iconv('windows-1250', 'utf-8', $out );
        $list = explode("\n", $out);
        foreach ($list as $wiersz) {
        //while ( ($wiersz = fgetcsv($handle, 0, ";", '"') ) !== FALSE ) {
            // ostatni wiersz w pliku może być pusty!
/*
            if($wiersz[0] == "CMS-EXPO"){
                print_r($wiersz); die();
                }
*/
            if (!empty($wiersz[0])) {
                //echo $wiersz ."\n";

                //$wiersz = $wiersz[0];//$wiersz = iconv('cp1250', 'utf-8//IGNORE', $wiersz);
                //print_r($wiersz); 
                $dane = explode(";", $wiersz);//$wiersz;//
                //print_r($dane); die();
                if ($dane[1] != "" && $dane[1] != "") {
                    // znajdz zasob                    
                    $zasob = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->findOneByNazwa(trim($dane[0]));
                    if (!$zasob) {
                        //echo "nie znaleziono $dane[2] " . "<br>";
                        //nie rób nic na razie
                        $zasob = new Zasoby();
                        $zasob->setOpis(trim($dane[8]));
                        $zasob->setBiuro(trim($dane[6]));
                        $zasob->setNazwa(trim($dane[0]));
                    }
                    foreach($dane as $k => $v){
                        $v = trim($v);
                        //echo ".".$v.".";
                        if($k >= 1 && $v != "" && $k < 27){
                            $setter = $wiersz2getter[$k];
                            if(strstr($setter, "Data") !== false){
                                //echo " <br>.".$value['dane'][1]." ".$value['dane'][2]." ".$v.".";
                                $v = \DateTime::createFromFormat('D M d H:i:s e Y', $v);
                                //print_r($v);
                                //die();
                            }
                            if($v)
                                $zasob->{"set".$setter}($v);                        
                        }                    
                    }
                    $em->persist($zasob);
                    
                }
            }
        }

        $em->flush();

        return true;
    }
    
    
    protected function wczytajPlikZasobyUser($file)
    {
        $dane = file_get_contents($file->getPathname());
        // $xxx = iconv('windows-1250', 'utf-8', $dane );

        $list = explode("\n", $dane);
        $ldap = $this->get('ldap_service');

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('delete from Parp\MainBundle\Entity\UserZasoby');
        $numDeleted = $query->execute();
        $wiersz2getter = array(
            3 => "LoginDoZasobu",
            4 => "Modul",
            5 => "PoziomDostepu",
            6 => "AktywneOd",
            7 => "Bezterminowo",
            8 => "AktywneDo",
            9 => "KanalDostepu",
            10 => "UprawnieniaAdministracyjne",
            11 => "OdstepstwoOdProcedury",
        );
        $tablica = array();
        foreach ($list as $wiersz) {
            // ostatni wiersz w pliku może być pusty!
            if (!empty($wiersz)) {
                //echo $wiersz ."\n";

                $wiersz = iconv('cp1250', 'utf-8//IGNORE', $wiersz);
                $dane = explode(";", $wiersz);
                if($dane[1] != "" && $dane[1] != ""){
                    $cnname = $this->ldap_escape($dane[1]) . '*' . $this->ldap_escape($dane[0]);
                    //echo ".".$wiersz.".<br>";
                    $ADUser = $ldap->getUserFromAD(null, $cnname);
                }
                if ($dane[1] != "" && $dane[1] != "" && !empty($ADUser)) {
                    // znajdz zasob                    
                    $zasob = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->findOneByNazwa(trim($dane[2]));
                    if (!$zasob) {
                        //echo "nie znaleziono $dane[2] " . "<br>";
                        //nie rób nic na razie
                    } else {
                        // sprawdz czy istnieje
                        /*
                          $userZasob = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findByAccountnameAndResource($ADUser[0]['samaccountname'], $zasob->getId());
                          if($ADUser[0]['samaccountname'] == 'andrzej_trocewicz'){
                          var_dump($userZasob);
                          var_dump($zasob->getId());

                          }
                          if (!$userZasob) {
                          // jezeli nie ma powiazania to utworz
                          //echo "brak" . "<br>";

                          $newUserZasob = new UserZasoby();
                          $newUserZasob->setSamaccountname($ADUser[0]['samaccountname']);
                          $newUserZasob->setZasobId($zasob->getId());
                          $em->persist($newUserZasob);

                          } */

                        // stworz tablice  bez powtorzen 
                        $samaccountname = $ADUser[0]['samaccountname'];
                        $zasobid = $zasob->getId();
                        $dane['zasobId'] = $zasobid;
                        if (key_exists($samaccountname, $tablica)) {
                            $klucz = $tablica[$samaccountname];
                            if (!in_array($zasobid, $klucz)) {
                                $tablica[$samaccountname][] = array("zasobId" => $zasobid, 'dane' => $dane);
                            }
                        } else {
                            $tablica[$samaccountname][] = array("zasobId" => $zasobid, 'dane' => $dane);
                        }
                    }
                }
            }
        }

        foreach ($tablica as $key => $values) {
            foreach ($values as $value) {
                //echo $key . ' ' . $value . "<br>";
                $newUserZasob = new UserZasoby();
                $newUserZasob->setAktywneOd(null);
                $newUserZasob->setAktywneDo(null);
                $newUserZasob->setCzyAktywne(true);
                $newUserZasob->setSamaccountname($key);
                $newUserZasob->setZasobId($value['zasobId']);
                foreach($value['dane'] as $k => $v){
                    $v = trim($v);
                    if($k >= 3 && $v != ""){
                        $setter = $wiersz2getter[$k];
                        if($k == 6 || $k == 8){
                            echo " <br>.".$value['dane'][1]." ".$value['dane'][2]." ".$v.".";
                            $v = \DateTime::createFromFormat('D M d H:i:s e Y', $v);
                            print_r($v);
                            //die();
                        }
                        if($v)
                            $newUserZasob->{"set".$setter}($v);                        
                    }                    
                }
                $em->persist($newUserZasob);
            }
        }
        $em->flush();

        return true;
    }

    /**
     * @Route("/resources/{samaccountname}", name="resources")
     * 
     */
    public function showResources($samaccountname, Request $request)
    {

        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);

        // Pobieramy listę zasobow
        $userZasoby = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findNameByAccountname($samaccountname);

        return $this->render('ParpMainBundle:Default:resources.html.twig', array('user' => $ADUser[0]['name'], 'zasoby' => $userZasoby));
    }

    /**
     * @Route("/test", name="test")
     * 
     */
    public function test()
    {
        $em = $this->getDoctrine()->getManager();
        $userEngagements = $em->getRepository('ParpMainBundle:UserUprawnienia')->findSekcja('lolek_lolek');
        var_dump($userEngagements);
    }
    
    public function poprawPlikCsv($file){
        $dane = file_get_contents($file->getPathname());
        // $xxx = iconv('windows-1250', 'utf-8', $dane );

        $list = explode("\n", $dane);
        $out = "";
        $buffer = "";
        $inTheMiddle = false;
        foreach($list as $line){            
            $c = substr_count($line, '"');
            if($c%2 == 1){
                if($inTheMiddle){
                    $buffer .= $line;
                    $out .= $buffer."\n";
                    $inTheMiddle = false;
                    $buffer = "";
                }else{
                    $inTheMiddle = true;
                    //$buffer = "";
                    $buffer = $line."\\n";
                }
            }elseif($inTheMiddle){
                $buffer .= $line."\\n";
            }else{
                $out .= $line."\n";
            }
        }//die($out);
        return $out;
    }


    
    /**
     * @param $term
     * @Route("/user/suggest/", name="userSuggest")
     */
    public function userSuggestAction(Request $request)
    {
        
        $post = ($request->getMethod() == 'POST');
        $ajax = $request->isXMLHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
/*
        if ((!$ajax) OR ( !$post)) {
            return null;
        }
*/

        $imienazwisko = $request->get('term', null);
        if (empty($imienazwisko)) {
            throw new \Exception('Nie przekazano imienia i nazwiska!');
        }

        $ldap = $this->get('ldap_service');

        $ADUsers = $ldap->getAllFromAD();

        $dane = array();
        $i = 0;
        foreach ($ADUsers as $user) {

            if (mb_stripos($user['name'], $imienazwisko, 0, 'UTF-8') !== FALSE) {
                $dane[$i] = $user['name'];
                $i++;
            }
        }
        
        //$vals = array("Kamil Jakacki", "Kamamamama", "Costam");
        $term = json_encode($dane);
        die($term);
    }
}
