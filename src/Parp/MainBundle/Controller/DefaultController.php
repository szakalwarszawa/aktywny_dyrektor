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

class DefaultController extends Controller
{

    /**
     * @Route("/index/{onlyTemporary}", name="main", defaults={"onlyTemporary": "usersFromAd"})
     * @Route("/", name="main_home")
     * @Template()
     */
    public function indexAction($onlyTemporary = "usersFromAd")
    {
        //$this->get('check_access')->checkAccess('USER_MANAGEMENT');
        
        $ldap = $this->get('ldap_service');
        // Sięgamy do AD:
        if($onlyTemporary != "usersFromAd"){
            $ADUsers = $this->getDoctrine()->getRepository('ParpMainBundle:Entry')->getTempEntriesAsUsers($ldap);
        }else{
            $aduser = $ldap->getUserFromAD($this->getUser()->getUsername());
            $widzi_wszystkich = 
                in_array("PARP_BZK_1", $this->getUser()->getRoles()) ||
                in_array("PARP_BZK_2", $this->getUser()->getRoles()) ||
                in_array("PARP_ADMIN", $this->getUser()->getRoles())            
            ;
            $ADUsersTemp = $ldap->getAllFromAD();
            $ADUsers = array();
            foreach($ADUsersTemp as $u){
                //albo ma role ze widzi wszystkich albo widzi tylko swoj departament
                if($widzi_wszystkich || mb_strtolower(trim($aduser[0]['department'])) == mb_strtolower(trim($u['department']))){
                    $ADUsers[] = $u;//['name'];
                }
            }
            //$ADUsers;
    
            
            //print_r($ADUsers);
        }
        //die(".".count($ADUsers));
        if(count($ADUsers) == 0){
            return $this->render('ParpMainBundle:Default:NoData.html.twig');
        }
        //echo "<pre>"; print_r($ADUsers); die();
        $source = new Vector($ADUsers);

        $grid = $this->get('grid');

        //$MyTypedColumn = new TextColumn(array('id' => 'samaccountname', 'field' => 'samaccountname', 'title' => 'Nazwa użytkownika', 'source' => true, 'filterable' => false, 'sortable' => true, 'primary' => true));
        //$grid->addColumn($MyTypedColumn);

        $source->setId('samaccountname');
        $grid->setSource($source);
        
        if(count($ADUsers) > 0){
            //echo "<pre>"; print_r($ADUsers); die();
            $grid->hideColumns(array(
                'manager',
                //'accountDisabled',
                //'info',
                'description',
                'division',
                //            'thumbnailphoto',
                'useraccountcontrol',
                //'samaccountname',
                'initials',
                'accountExpires',
                'accountexpires',
                'email',
                'lastlogon',
                'cn',
                'distinguishedname',
                'memberOf',
                'roles'
            ));
            // Konfiguracja nazw kolumn
    
    
            $grid->getColumn('samaccountname')
                    ->setTitle('Nazwa użytkownika')
                    ->setOperators(array("like"))
                    ->setOperatorsVisible(false)
                    ->setPrimary(true);
    
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
            $grid->getColumn('isDisabled')
                    ->setTitle("Konto wyłączone")
                    ->setOperators(array("like"))
                    ->setOperatorsVisible(false);
        }

        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);

        // Zdejmujemy filtr
        $grid->getColumn('akcje')
                ->setFilterable(false)
                ->setSafe(true);

        if($onlyTemporary == "usersFromAd"){
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
        }else{
            
            // Edycja konta
            $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Zobacz użytkownika', 'show_uncommited');
            $rowAction2->setColumn('akcje');
            $rowAction2->setRouteParameters(
                    array('id')
            );
            $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
            // Edycja konta
            $rowAction3 = new RowAction('<i class="fa fa-sitemap"></i> Zaangażowania', 'engageUser');
            $rowAction3->setColumn('akcje');
            $rowAction3->setRouteParameters(
                    array('samaccountname')
            );
            $rowAction3->addAttribute('class', 'btn btn-success btn-xs');
    
            $grid->addRowAction($rowAction2);
            $grid->addRowAction($rowAction3);
        }

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
        
        $massAction1 = new MassAction("Przypisz dodatkowe zasoby", 'ParpMainBundle:Default:processMassAction', false, array('action' => 'addResources'));
        $grid->addMassAction($massAction1);

        $massAction2 = new MassAction("Odbierz prawa do zasobów", 'ParpMainBundle:Default:processMassAction', false, array('action' => 'removeResources'));
        $grid->addMassAction($massAction2);     
        $massAction3 = new MassAction("Przypisz dodatkowe uprawnienia",'ParpMainBundle:Default:processMassAction', false, array('action' => 'addPrivileges'));
        //$massAction3->setParameters(array('action' => 'addPrivileges', 'samaccountname' => 'samaccountname'));
        $grid->addMassAction($massAction3);
        $massAction4 = new MassAction("Odbierz uprawnienia",'ParpMainBundle:Default:processMassAction', false, array('action' => 'removePrivileges'));
        //'ParpMainBundle:Default:processMassAction', false, array('action' => 'removePrivileges'));
        $grid->addMassAction($massAction4);


        return $grid->getGridResponse();
    }
    /**
     * @return array
     * @Template();
     */
    public function processMassActionAction($action, $primaryKeys, $allPrimaryKeys, $session = null, $parameters = null)
    {/*

        print_r($action);
        echo "-------";
        print_r($primaryKeys);
        echo "-------";
        print_r($parameters);
        echo "-------";
        print_r($_POST); 
        die();
*/
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
     * @Route("/show_uncommited/{id}", name="show_uncommited");
     * @Template();
     */
    function show_uncommitedAction($id, Request $request)
    {
        $entry = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Entry')->find($id);
        return $this->render('ParpMainBundle:Default:show.html.twig', array(
            'entry' => $entry
        ));
            
    }
    function array_diff($a1, $a2){
        $ret = array();
        foreach($a1 as $k => $v1){
            if(isset($a2[$k]) && $a2[$k] != $a1[$k])
                $ret[$k] = $a1[$k];
            elseif(!isset($a2[$k]))
                $ret[$k] = $a1[$k];
        }
        return $ret;
    }
    /**
     * @Route("/user/{samaccountname}/edit", name="userEdit");
     * @Template();
     */
    function editAction($samaccountname, Request $request)
    {
        
        $admin = in_array("PARP_ADMIN", $this->getUser()->getRoles());
        $kadry1 = in_array("PARP_BZK_1", $this->getUser()->getRoles());
        $kadry2 = in_array("PARP_BZK_2", $this->getUser()->getRoles());
        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);
        //print_r($ADUser); die();
        $ADManager = $ldap->getUserFromAD(null, substr($ADUser[0]['manager'], 0, stripos($ADUser[0]['manager'], ',')));

        // wyciagnij imie i nazwisko managera z nazwy domenowej
        $ADUser[0]['manager'] = mb_substr($ADUser[0]['manager'], mb_stripos($ADUser[0]['manager'], '=') + 1, (mb_stripos($ADUser[0]['manager'], ',OU')) - (mb_stripos($ADUser[0]['manager'], '=') + 1));

        $defaultData = $ADUser[0];
        //print_r($defaultData); die();
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

        
        $zasoby = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findNameByAccountname($samaccountname);
        for($i = 0; $i < count($zasoby); $i++){
            $uz = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->find($zasoby[$i]['id']);
            
            $zasoby[$i]['opisHtml'] = $uz->getOpisHtml();
            $zasoby[$i]['modul'] = $uz->getModul();
            $zasoby[$i]['loginDoZasobu'] = $uz->getLoginDoZasobu();
            $zasoby[$i]['poziomDostepu'] = $uz->getPoziomDostepu();
            $zasoby[$i]['aktywneOd'] = $uz->getAktywneOd()->format("Y-m-d");
            $zasoby[$i]['aktywneDo'] = $uz->getAktywneDo()->format("Y-m-d");
            $zasoby[$i]['kanalDostepu'] = $uz->getKanalDostepu();
            $zasoby[$i]['powodOdebrania'] = $uz->getPowodOdebrania();
            $zasoby[$i]['powodNadania'] = $uz->getPowodNadania();
            $zasoby[$i]['czyAktywne'] = $uz->getCzyAktywne();
            $zasoby[$i]['wniosekId'] = $uz->getWniosek() ? $uz->getWniosek()->getId() : 0;
            $zasoby[$i]['wniosekNumer'] = $uz->getWniosek() ? $uz->getWniosek()->getWniosek()->getNumer() : 0;
        }
        
        //print_r($defaultData); die();
        $form = $this->createUserEditForm($defaultData);



        $form->handleRequest($request);

        if ($form->isValid()) {
            $ndata = $form->getData();
            if($kadry1 || $kadry2){
                return $this->parseUserKadry($samaccountname, $ndata, $previousData);
            }elseif(!$admin){
                die("Nie masz uprawnien by edytowac uzytkownikow!!!");
            }
            $newSection = $form->get('infoNew')->getData();
            $oldSection = $form->get('info')->getData();
            //echo ".".$oldSection.".";
            if($newSection != ""){
                $ns = new \Parp\MainBundle\Entity\Section();
                $ns->setName($newSection);
                $ns->setShortName($newSection);
                $this->getDoctrine()->getManager()->persist($ns);
                $ndata['info'] = $newSection;
                unset($ndata['infoNew']);
            }
            //die($newSection);
            $newrights = $ndata['initialrights'];
            $odata = $previousData;
            $roznicauprawnien = (($ndata['initialrights'] != $odata['initialrights']));
            unset($ndata['initialrights']);
            unset($odata['initialrights']);
            unset($ndata['memberOf']);
            unset($odata['memberOf']);
            unset($ndata['fromWhen']);
            unset($odata['fromWhen']);
            
            //hack by dalo sie puste inicjaly wprowadzic
            if($ndata['initials'] == "")
                $ndata['initials'] = "puste";
            //$ndata['division'] = "";
            if($ndata['isDisabled'] == 0)
                $ndata['disableDescription'] = $ndata['description'];
                
                
            $roles1 = $odata['roles'];
            unset($odata['roles']);
            $roles2 = $ndata['roles'];
            unset($ndata['roles']);
            
            $rolesDiff = $roles1 != $roles2;

            if (0 < count($this->array_diff($ndata, $odata)) || $roznicauprawnien || $rolesDiff) {
                
                //  Mamy zmianę, teraz trzeba wyodrebnić co to za zmiana
                // Tworzymy nowy wpis w bazie danych
                $newData = $this->array_diff($ndata, $odata);
                if($rolesDiff){
                    $roles = $this->getDoctrine()->getRepository('ParpMainBundle:AclUserRole')->findBySamaccountname($samaccountname);
                    foreach($roles as $r){
                        $this->getDoctrine()->getManager()->remove($r);
                    }
                    foreach($roles2 as $r){
                        $role = $this->getDoctrine()->getRepository('ParpMainBundle:AclRole')->findOneByName($r);
                        $us = new \Parp\MainBundle\Entity\AclUserRole();
                        $us->setSamaccountname($samaccountname);
                        $us->setRole($role);
                        $this->getDoctrine()->getManager()->persist($us);
                    }
                    $this->get('session')->getFlashBag()->set('warning', "Role zostały zmienione");
                    
                }
                if(0 < count($this->array_diff($ndata, $odata)) || $roznicauprawnien){
                    //sprawdzamy tu by dalo sie zarzadzac uprawnieniami !!!
                    
                    
                    $this->get('adcheck_service')->checkIfUserCanBeEdited($samaccountname);
                    $this->get('session')->getFlashBag()->set('warning', "Zmiany do AD zostały wprowadzone");
                    $entry = new Entry($this->getUser()->getUsername());
                    $entry->setSamaccountname($samaccountname);
                    $entry->setDistinguishedName($previousData["distinguishedname"]);
                    if(($roznicauprawnien)){
                        $value = implode(",", $newrights);
                        $entry->setInitialrights($value);
                    }
                    foreach ($newData as $key => $value) {
                        switch ($key) {
                            case "isDisabled":
                                $entry->setIsDisabled($value);
                                break;
                            case "disableDescription":
                                $entry->setDisableDescription($value);
                                break;
                            case "name":
                                $entry->setCn($value);
                                break;
                            case "initials":
                                $entry->setInitials($value);
                                break;
                            case "accountExpires":
                                if($value){
                                    $entry->setAccountexpires(new \DateTime($value));    
                                }else{
                                    $entry->setAccountexpires(new \DateTime("3000-01-01 00:00:00"));
                                }
                                
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
                        $entry->setFromWhen(new \DateTime('today'));
                        $this->getDoctrine()->getManager()->persist($entry);
                }

                $this->getDoctrine()->getManager()->flush();

                return $this->redirect($this->generateUrl('main'));
            }
        }
        $uprawnienia = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:UserUprawnienia')->findBy(array('samaccountname' => $samaccountname));//, 'czyAktywne' => true));
        $historyEntries = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Entry')->findBy(array('samaccountname' => $samaccountname, 'isImplemented' => 1));
        $pendingEntries = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Entry')->findBy(array('samaccountname' => $samaccountname, 'isImplemented' => 0));
        $up2grupaAd = array();
        foreach($uprawnienia as $u){
            $up = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:Uprawnienia')->find($u->getUprawnienieId());
            if($up->getGrupaAd())
                $up2grupaAd[$up->getId()] = $up->getGrupaAd();
        }
        $grupyAd = $ADUser[0]["memberOf"];
        $names = explode(' ', $ADUser[0]["name"]);
        $dane_rekord = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:DaneRekord')->findOneBy(array('imie' => $names[0], 'nazwisko' => $names[1]));
        
        
        $userGroupsTemp = $ldap->getAllUserGroupsRecursivlyFromAD($ADUser[0]['samaccountname']);
        $userGroups = [];
        foreach($userGroupsTemp as $ug){
            if(is_array($ug)){
                $userGroups[] = $ug['dn'];
            }
        }
        
        $tmpl = $kadry1 || $kadry2 ?  "ParpMainBundle:Default:editKadry.html.twig" : 'ParpMainBundle:Default:edit.html.twig';
        //die($tmpl);
        return $this->render($tmpl, array(
            'userGroups' => $userGroups,
            'user' => $ADUser[0],
            'form' => $form->createView(),
            'zasoby' => $zasoby,
            'uprawnienia' => $uprawnienia,
            'grupyAd' => $grupyAd,
            'up2grupaAd' => $up2grupaAd,
            'pendingEntries' => $pendingEntries,
            'historyEntries' => $historyEntries,
            'dane_rekord' => $dane_rekord
                //'manager' => isset($ADManager[0]) ? $ADManager[0] : "",
        ));
    }
    protected function parseUserKadry($samaccountname, $ndata, $odata){
        
        $diff = $this->array_diff($ndata, $odata);
        unset($diff['samaccountname']);
        unset($diff['initials']);
        unset($diff['title']);
        unset($diff['department']);
        unset($diff['cn']);
        unset($diff['roles']);
        unset($diff['samaccountname']);
        unset($diff['initialrights']);
        unset($diff['fromWhen']);
        
        
        //var_dump($ndata, $odata, $diff); die();
        if(count($diff) > 0){
            $entry = new \Parp\MainBundle\Entity\Entry($this->getUser()->getUsername());
            //zmiana sekcji
            if(isset($diff['info'])){
                $entry->setInfo($ndata['info']);
            }
            //zmiana przelozonego
            if(isset($diff['manager'])){
                $entry->setManager($ndata['manager']);
                
            }
            //data wygasniecia
            if(isset($diff['accountExpires'])){
                $entry->setAccountExpires(new \Datetime($ndata['accountExpires']));
                
            }
            //konto wylaczone
            if(isset($diff['isDisabled'])){
                $entry->setIsDisabled($ndata['isDisabled']);
                $entry->setDisableDescription($ndata['disableDescription']);                
            }
            $this->getDoctrine()->getManager()->persist($entry);
            $this->getDoctrine()->getManager()->flush();
            //powod wylaczenia
            $msg = "Zmiany wprowadzono.";
        }else{
            
            $msg = "Nie było zmian do wprowadzenia.";
        }
        
        $this->get('session')->getFlashBag()->set('warning', $msg);
        
        return $this->redirect($this->generateUrl('userEdit', array('samaccountname' => $samaccountname)));
        
    }
    protected function createUserEditForm($defaultData){
        // Pobieramy listę stanowisk
        $titlesEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Position')->findBy(array(), array('name' => 'asc'));
        $titles = array();
        foreach ($titlesEntity as $tmp) {
            $titles[$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Biur i Departamentów
        $departmentsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findBy(array('nowaStruktura' => 1), array('name' => 'asc'));
        $departments = array();
        foreach ($departmentsEntity as $tmp) {
            $departments[$tmp->getName()] = $tmp->getName();
        }
        // Pobieramy listę Sekcji
        $sectionsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Section')->findBy(array(), array('name' => 'asc'));
        $sections = array();
        foreach ($sectionsEntity as $tmp) {
            $sections[$tmp->getDepartament()->getShortname()][$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Uprawnien
        $rightsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:GrupyUprawnien')->findBy(array(), array('opis' => 'asc'));
        $rights = array();
        foreach ($rightsEntity as $tmp) {
            $rights[$tmp->getKod()] = $tmp->getOpis();
        }
        $rolesEntity = $this->getDoctrine()->getRepository('ParpMainBundle:AclRole')->findBy(array(), array('name' => 'asc'));
        $roles = array();
        foreach ($rolesEntity as $tmp) {
            $roles[$tmp->getName()] = $tmp->getOpis();
        }
        $now = new \Datetime();
        
        $ldap = $this->get('ldap_service');
        $aduser = $ldap->getUserFromAD($this->getUser()->getUsername());
        $admin = in_array("PARP_ADMIN", $this->getUser()->getRoles());
        $kadry1 = in_array("PARP_BZK_1", $this->getUser()->getRoles());
        $kadry2 = in_array("PARP_BZK_2", $this->getUser()->getRoles());
        
        
        $builder = $this->createFormBuilder(@$defaultData)
                ->add('samaccountname', 'text', array(
                    'required' => false,
                    'read_only' => true,
                    'label' => 'Nazwa konta',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                        'readonly' => (!$admin)
                    ),
                ))
                ->add('cn', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Imię i nazwisko',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                        'readonly' => (!$admin)
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
                        'readonly' => (!$admin)
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
                        'class' => 'form-control select2',
                        'disabled' => (!$admin)
                    ),
                    //'data' => @$defaultData["title"],
                    'choices' => $titles,
//                'mapped'=>false,
                ))
                ->add('infoNew', 'hidden', array(
                    'mapped' => false,
                    'label' => false,
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                        'readonly' => (!$admin)
                    ),
                    
                ))
                ->add('info', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Sekcja',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control select2',
                        'disabled' => (!$admin && !$kadry1 && !$kadry2)
                    ),
                    'choices' => $sections,
                    //'data' => @$defaultData['info'],
                ))
                ->add('department', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Biuro / Departament',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control select2',
                        'disabled' => (!$admin)
                    ),
                    'choices' => $departments,
                    //'data' => @$defaultData["department"],
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
                        'readonly' => (!$admin && !$kadry1 && !$kadry2),
                        
                        //'disabled' => (!$admin && !$kadry1 && !$kadry2)

                    ),
                    //'data' => @$defaultData['manager']
                ))
                ->add('accountExpires', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    //'widget' => 'single_text',
                    'label' => 'Data wygaśnięcia konta',
                    //'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                        'readonly' => (!$admin && !$kadry1 && !$kadry2)
                    ),
                    'required' => false,
                    //'data' => @$expires
                ))
                ->add('fromWhen', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Zmiana obowiązuje od',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'required' => false,
                    'data' => $now->format("Y-m-d")
                ))
                ->add('initialrights', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Uprawnienia początkowe',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control select2',
                        'disabled' => (!$admin)
                    ),
                    'choices' => $rights,
                    //'data' => (@$defaultData["initialrights"]),
                    'multiple' => true,
                    'expanded' => false
                ))
                
                ->add('roles', 'choice', array(
                    'required' => false,
                    'read_only' => (!$admin),
                    'label' => 'Role w AkD',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control select2',
                        'readonly' => (!$admin),
                        'disabled' => (!$admin)
                    ),
                    'choices' => $roles,
                    //'data' => (@$defaultData["initialrights"]),
                    'multiple' => true,
                    'expanded' => false
                ))
                
                ->add('isDisabled', 'choice', array(
                    'required' => true,
                    'read_only' => false,
                    'label' => 'Konto wyłączone w AD',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control select21',
                        'disabled' => (!$admin && !$kadry1 && !$kadry2)
                    ),
                    'choices' => array(
                        '0' => 'NIE',
                        '1' => 'TAK'
                    ),
                    //'data' => @$defaultData["department"],
                ))
                ->add('disableDescription', 'choice', array(
                    'label' => 'Podaj powód wyłączenia konta',
                    'choices' => array(
                        "" => "",
                        "Konto wyłączono z powodu nieobecności dłuższej niż 21 dni" => "Konto wyłączono z powodu nieobecności dłuższej niż 21 dni",
                        "Konto wyłączono z powodu rozwiązania stosunku pracy" => "Konto wyłączono z powodu rozwiązania stosunku pracy",
                    ),
                    'required' => false,
                    'attr' => array(                        
                        'disabled' => (!$admin && !$kadry1 && !$kadry2)
                    )
                ));
                               
                if(!(!$admin && !$kadry1 && !$kadry2)){
                    $builder->add('zapisz', 'submit', array(
                        'attr' => array(
                            'class' => 'btn btn-success col-sm-12',
                            'disabled' => (!$admin && !$kadry1 && !$kadry2)
                        ),
                    ));
                }         
                $form = $builder->setMethod('POST')
                ->getForm();
        return $form;
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

        

        $entry = new Entry($this->getUser()->getUsername());
        $form = $this->createUserEditForm($entry);
        
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('adcheck_service')->checkIfUserCanBeEdited($entry->getSamaccountname());
            
            
            $newSection = $form->get('infoNew')->getData();
            $oldSection = $form->get('info')->getData();
            if($newSection != ""){
                $ns = new \Parp\MainBundle\Entity\Section();
                $ns->setName($newSection);
                $ns->setShortName($newSection);
                $this->getDoctrine()->getManager()->persist($ns);
                $entry->setInfo($newSection);
                //unset($ndata['infoNew']);
            }
            // perform some action, such as saving the task to the database
            // utworz distinguishedname
            $tab = explode(".", $this->container->getParameter('ad_domain'));
            $ou = ($this->container->getParameter('ad_ou'));
            $department = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findOneByName($entry->getDepartment());
            //print_r($form->get('department')->getData());die();
            $distinguishedname = "CN=" . $entry->getCn() . ", OU=" . $department->getShortname() . ",".$ou.", DC=" . $tab[0] . ",DC=" . $tab[1];

            $entry->setDistinguishedName($distinguishedname);
            
            $entry->setFromWhen(new \DateTime($entry->getFromWhen()));
            
            $entry->setAccountExpires(new \DateTime($entry->getAccountExpires()));
            
            $value = implode(",", $entry->getInitialrights());
            $entry->setInitialrights($value);
            
            //print_r($entry);
            $em->persist($entry);
            $em->flush();
            return $this->redirect($this->generateUrl('show_uncommited', array('id' => $entry->getId())));
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
            'samaccountname' => $samaccountname,
            'user' => @$ADUser[0],
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
     * @Route("/suggestinitials", name="suggest_initials", options={"expose"=true})
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
        $p = explode(" ", $request->get('cn'));
        $initials = substr($p[0], 0, 1). substr($p[1], 0, 1);
        /*

        $samaccountname = $request->get('samaccountname', null);
        if (empty($samaccountname)) {
            throw new \Exception('Nie przekazano nazwy konta!');
        }
        $department = $request->get('department', null);
        $cn = $request->get('samaccountname', null);

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
        //print_r($temp0);
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
*/
        $initials = mb_strtoupper($initials, 'UTF-8');

        return $this->render('ParpMainBundle:Default:suggestinitials.html.twig', array('initials' => $initials));
    }

    /**
     * @Route("/findmanager", name="find_manager", options={"expose"=true})
     * 
     */
    public function ajaxFindManager(Request $request)
    {

        $post = ($request->getMethod() == 'POST');
        $ajax = $request->isXMLHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
        if ((!$ajax) OR ( !$post)) {
            //return null;
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
                            'mimeTypes' => array('text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/msexcel', 'application/xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                            'mimeTypesMessage' => 'Niewłaściwy typ plku. Proszę wczytac plik z rozszerzeniem csv'
                                )),
                    ),
                    'mapped' => false,
                ))
                ->add('rok', 'text', array('required' => false, 'label' => 'Przy imporcie zaangażowań podaj rok', 'data' => date("Y")))
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
                    if(is_array($ret)){
                        $msg = 'Plik został wczytany poprawnie. ';
                        $w = array();
                        foreach($ret as $k=>$v){
                            $w[] = "$k : $v";
                        }
                        $msg .= implode(", ", $w);
                        
                    }
                    $this->get('session')->getFlashBag()->set('warning', $msg);
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
        
        $ext = $file->guessExtension();
        //print_r($ext);die();
        
        if($ext == "xlsx"){
            $ret = $this->wczytajPlikZaangazowania($file->getPathname());
        }else{
            $list = explode("\n", $dane);
            $ldap = $this->get('ldap_service');
    
            $em = $this->getDoctrine()->getManager();
            
            //!!! tego sie pozbywam 
            //$query = $em->createQuery('delete from Parp\MainBundle\Entity\UserZasoby');
            //$numDeleted = $query->execute();
            
            $pierwszyWiersz = explode(";", $list[0]);
            $komorka = $pierwszyWiersz[0];
            //print_r($komorka); die();
            if($komorka == "Nazwa zasobu"){
                $ret = $this->wczytajPlikZasoby($file);
            }else{
                $ret = $this->wczytajPlikZasobyUser($file);
            }
        }
        return $ret;
    }
    protected $col2month = array("G", "I", "K", 'M', 'O', 'Q', 'S', 'U', 'W', 'Y', 'AA', 'AC');
    protected function getMonthsFromRow($row, $programy){
        $months = array();
        for($i = 1; $i <= 12; $i++){
            $months[$i] = array();
            $val = trim($row[$this->col2month[$i-1]]);
            $val = $val == "" ? 0 : floatval($val);
            $months[$i] = $val;

        }
        return $months;
    }
    protected $ADUsers = array();
    protected function findUserByName($imienazwisko){
        foreach ($this->ADUsers as $user) {
            if (mb_stripos($user['name'], $imienazwisko, 0, 'UTF-8') !== FALSE) {
                return $user;
            }
        }
    }
    protected function wczytajPlikZaangazowania($file){
        
        $rok = $this->get('request')->request->all()['form']['rok'];
        $this->ADUsers = $this->get('ldap_service')->getAllFromAD();

        $dane = array();
        
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
        
        $progs = $this->get('doctrine')->getManager()->getRepository('ParpMainBundle:Engagement')->findAll();
        $programy = array();
        foreach($progs as $p){
            $programy[$p->getName()] = $p;
        }
        
        $userdane = array();
        foreach($sheetData as $row){
            //pomijamy pierwszy rzad
            if($row['A'] != 'D/B'){
                $pr = trim($row['F']);
                $userdane[$row['B']][$pr] = $this->getMonthsFromRow($row, $programy);
            }
        }
        //print_r($programy);
        $ret = array();
        foreach($userdane as $user => $angaz){
            $u = $this->findUserByName($user);
            if($u == null){
                $this->addFlash('notice', 'Pomijam usera "'.$user.'" bo go nie ma w systemie');
            }else{
                foreach($angaz as $prog => $year){
                    if(!isset($programy[$prog])){
                        $this->addFlash('notice',  'Pomijam program "'.$prog.'" bo go nie ma w systemie');                    
                    }else{
                        //$pid = $programy[$prog]->getId();
                        foreach($year as $m => $proc ){
                            $pars = array(
                                'samaccountname' => $u['samaccountname'],
                                //'percent' => $proc*100,
                                'engagement' => $programy[$prog],
                                'month' => $m,
                                'year' => $rok                        
                            ); 
                            $ue = $this->get('doctrine')->getManager()->getRepository('ParpMainBundle:UserEngagement')->findOneBy($pars);
                            if($ue == null){
                                //print_r($pars);
                                //die('a');
                                $ue = new \Parp\MainBundle\Entity\UserEngagement();
                                $this->get('doctrine')->getManager()->persist($ue);
                            }else{
                                //die('b');
                            }
                            $ue->setSamaccountname($pars['samaccountname']);
                            $ue->setPercent($proc*100);
                            $ue->setEngagement($programy[$prog]);
                            $ue->setMonth($pars['month']);
                            $ue->setYear($pars['year']);
                            
                            $ret[] = $pars;       
                        }
                    }
                }
            }
            
        }       
        $this->get('doctrine')->getManager()->flush();
        
        //die();
        return true;
        //print_r($ret); die(); 
/*
        
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            //echo 'Worksheet - ' , $worksheet->getTitle() , $EOL;
            foreach ($worksheet->getRowIterator() as $row) {
                //echo '    Row number - ' , $row->getRowIndex() , $EOL;
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                foreach ($cellIterator as $cell) {
                    if (!is_null($cell)) {
                        //echo '        Cell - ' , $cell->getCoordinate() , ' - ' , $cell->getCalculatedValue() , $EOL;
                    }
                }
            }
        }
*/
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
    
    protected function parseMultiRowsUserZasoby($list){
        $ret = array();
        $multirows = array(5,6,7,8,9);
        foreach ($list as $wiersz) {
            //$wiersz = iconv('cp1250', 'utf-8//IGNORE', $wiersz);
            $dane = explode(";", $wiersz);
            $rowcount = 0;
            foreach($multirows as $r){
                $rowcount = substr_count($dane[$r], ",") > $rowcount ? substr_count($dane[$r], ",") : $rowcount;                
            }
            //die(".".$multirow);
            if($rowcount > 0){
                //ąecho $wiersz."<br>";
                for($i = 0; $i < $rowcount+1; $i++){
                    $nd = $dane;
                    foreach($multirows as $r){
                        //echo "<br>".$r." ".$i."<br>";
                        $v = explode(",", $dane[$r]);
                        $nd[$r] = $v[$i];
                    }
                    $ret[] = implode(";", $nd);
                } 
            }else{
                $ret[] = $wiersz;    
            }
        }
        return $ret;
    }
    protected function wczytajPlikZasobyUser($file)
    {
        $wynik = array('utworzono' => 0, 'zmieniono' => 0, 'nie zmieniono' => 0, 'skasowano' => 0);
        $dane = file_get_contents($file->getPathname());
        $zamianaSlownikaKanalDostepu = array(
            "DZ_O - Zdalny, za pomocą komputera nie będącego własnością PARP" => "DZ_O - Zdalny - za pomocą komputera nie będącego własnością PARP",
            "DZ_P - Zdalny, za pomocą komputera będącego własnością PARP" => "DZ_P - Zdalny - za pomocą komputera będącego własnością PARP",
            "WK - Wewnętrzny kablowy" => "WK - Wewnętrzny kablowy",
            "WR - Wewnętrzny radiowy" => "WR - Wewnętrzny radiowy",
            "WRK - Wewnętrzny radiowy i kablowy" => "WRK - Wewnętrzny radiowy i kablowy",
        );
        // $xxx = iconv('windows-1250', 'utf-8', $dane );
      
        foreach($zamianaSlownikaKanalDostepu as $f => $r){
            $dane = str_replace(iconv('utf-8//IGNORE', 'cp1250', $f), iconv('utf-8//IGNORE', 'cp1250', $r), $dane);
        }
        $list = explode("\n", $dane);
        $list = $this->parseMultiRowsUserZasoby($list);
        //print_r($list); die();
        $ldap = $this->get('ldap_service');

        $em = $this->getDoctrine()->getManager();
        //$query = $em->createQuery('delete from Parp\MainBundle\Entity\UserZasoby uz where uz.importedFromEcm = 1');
        //$numDeleted = $query->execute();
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
        $pomijacKolumnyPrzyPorownaniu = array(3,4,5,7,9,10,11);
        $uzsIdPokryteWimporcie = array();
        
        
        //print_r($tablica); die();
        foreach ($tablica as $samaccountname => $values) {
            $samsUserZasoby = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findByAccountnameAndEcm($samaccountname);                          
            foreach ($values as $value) {
                //echo $key . ' ' . $value . "<br>";
                
                //echo "<br><br><br>szukam DUBLA $samaccountname ".$value['zasobId']."<br><br><br>";
                $newUserZasob = null;
                //szukam czy istnieje taki zasob
                foreach($samsUserZasoby as $uzs){
                    //uznajemy ze to ten sam userZasoby jesli rowne sa: (samaccountname) zasobId, poziomDostepu, aktywneOd i aktywneDo, kanalDostepu
                    
                    $equal  = $uzs->getZasobId() == $value['zasobId'];
                    if($equal){
                        foreach($wiersz2getter as $col => $getter){
                            if(!in_array($col, $pomijacKolumnyPrzyPorownaniu)){
                                $val = $uzs->{"get".$getter}();
                                if($col == 6 || $col == 8){
                                    $val = $val->format('D M d H:i:s T Y');
                                }
                                $equal = $equal && ($val == trim($value['dane'][$col]));
                                //echo "<br>porownuje $getter <br>.".$val .".<br>.". $value['dane'][$col].".<br> wynik ".($val == $value['dane'][$col])." ".$equal."<br>";
                            }                        
                        }
                    }
                    if($equal){
                        //echo('mam dubla');
                        $newUserZasob = $uzs;    
                        $uzsIdPokryteWimporcie[] = $uzs->getId();
                        $wynik['nie zmieniono'] += 1;
                    }
                }
                if($newUserZasob == null){
                    //echo "<br><br><br>NIE MAM DUBLA <br><br><br>";
                    
                    $newUserZasob = new UserZasoby();
                    $newUserZasob->setImportedFromEcm(true);
                    $newUserZasob->setAktywneOd(null);
                    $newUserZasob->setAktywneDo(null);
                    $newUserZasob->setCzyAktywne(true);
                    $newUserZasob->setPowodNadania("na podstawie wniosku z ECM-PARP");
                    $newUserZasob->setSamaccountname($samaccountname);
                    $newUserZasob->setZasobId($value['zasobId']);
                    foreach($value['dane'] as $k => $v){
                        $v = trim($v);
                        if($k >= 3 && $v != ""){
                            $setter = $wiersz2getter[$k];
                            if($k == 6 || $k == 8){
                                //echo " <br>.".$value['dane'][1]." ".$value['dane'][2]." ".$v.".";
                                $v = \DateTime::createFromFormat('D M d H:i:s T Y', $v);
                                //print_r($v);
                                //die();
                            }
                            if($v)
                                $newUserZasob->{"set".$setter}($v);                        
                        }                    
                    }
                    $wynik['utworzono'] += 1;
                }
                $em->persist($newUserZasob);
                
            }
        }
        
        foreach($samsUserZasoby as $uzs){
            if(!in_array($uzs->getId(), $uzsIdPokryteWimporcie)){
                $uzs->setPowodOdebrania("na podstawie wniosku z ECM-PARP");                
                $em->remove($uzs);
                $wynik['skasowano'] += 1;
            }            
        }
        $em->flush();

        return $wynik;
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
    
    public function poprawPlikCsv($file)
    {
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
                //$dane[$i] = $user['name'];
                $dane[$i] = $user['name'];//$this->get('renameService')->fixImieNazwisko($user['name']);
                $i++;
            }
        }
        
        //$vals = array("Kamil Jakacki", "Kamamamama", "Costam");
        $term = json_encode($dane);
        die($term);
    }
}
