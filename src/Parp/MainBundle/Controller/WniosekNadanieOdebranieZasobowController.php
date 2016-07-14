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

use Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use Parp\MainBundle\Form\WniosekNadanieOdebranieZasobowType;

/**
 * WniosekNadanieOdebranieZasobow controller.
 *
 * @Route("/wnioseknadanieodebraniezasobow")
 */
class WniosekNadanieOdebranieZasobowController extends Controller
{
    protected $debug = false;
    /**
     * Lists all WniosekNadanieOdebranieZasobow entities.
     *
     * @Route("/index", name="wnioseknadanieodebraniezasobow")
     * @Template()
     */
    public function indexAction()
    {
        $grid = $this->generateGrid("wtoku");
        $grid2 = $this->generateGrid("oczekujace");
        $grid3 = $this->generateGrid("zamkniete");
        
        
        if ($grid->isReadyForRedirect() || $grid2->isReadyForRedirect() || $grid3->isReadyForRedirect() )
        {
            if ($grid->isReadyForExport())
            {
                return $grid->getExportResponse();
            }
        
            if ($grid2->isReadyForExport())
            {
                return $grid2->getExportResponse();
            }
            
            if ($grid3->isReadyForExport())
            {
                return $grid3->getExportResponse();
            }
        
            // Url is the same for the grids
            return new RedirectResponse($grid->getRouteUrl());
        }
        else
        {
            return $this->render('ParpMainBundle:WniosekNadanieOdebranieZasobow:index.html.twig', array('grid' => $grid, 'grid2' => $grid2, 'grid3' => $grid3));
        }
    }
    
    protected function generateGrid($ktore){
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->findAll();
    
        $source = new Entity('ParpMainBundle:WniosekNadanieOdebranieZasobow');
        $tableAlias = $source->getTableAlias();
        //die($co);
        $sam = $this->getUser()->getUsername();
        $source->manipulateQuery(
            function ($query) use ($tableAlias, $sam, $ktore)
            {
                $query->leftJoin($tableAlias . '.wniosek', 'w');
                $query->leftJoin('w.viewers', 'v');
                $query->leftJoin('w.editors', 'e');
                $query->leftJoin('w.status', 's');
                $query->andWhere('v.samaccountname = \''.$sam.'\'');
                
                $statusy = ['08_ROZPATRZONY_NEGATYWNIE', '07_ROZPATRZONY_POZYTYWNIE', '10_PODZIELONY'];
                switch($ktore){
                    case "wtoku":
                        $w = 's.nazwaSystemowa NOT IN (\''.implode('\',\'', $statusy).'\')';
                        //rdie($w);
                        $query->andWhere($w);
                        break;
                    case "oczekujace":
                        $query->andWhere('e.samaccountname = \''.$sam.'\'');
                        break;
                    case "zamkniete":
                        $query->andWhere('s.nazwaSystemowa IN (\''.implode('\',\'', $statusy).'\')');
                        break;
                }
                //$query->andWhere('w.samaccountname = \''.$sam.'\'');
                $query->addGroupBy($tableAlias . '.id');   
            }
        );
        $grid = $this->get('grid');
        
        
        $grid->setSource($source);
    
        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);
    
        // Zdejmujemy filtr
        $grid->getColumn('akcje')
                ->setFilterable(false)
                ->setSafe(true);
    
        // Edycja konta
        $rowAction1 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'wnioseknadanieodebraniezasobow_edit');
        $rowAction1->setColumn('akcje');
        $rowAction1->addAttribute('class', 'btn btn-success btn-xs');
        
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Pokaż', 'wnioseknadanieodebraniezasobow_show');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-info btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'wnioseknadanieodebraniezasobow_delete_form');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        //$grid->addRowAction($rowAction1);
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        //$grid->isReadyForRedirect();
        return $grid;

    }
    /**
     * Creates a new WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/", name="wnioseknadanieodebraniezasobow_create")
     * @Method("POST")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobow:edit.html.twig")
     */
    public function createAction(Request $request)
    {
        $msg = "";
        $entity = new WniosekNadanieOdebranieZasobow();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $jestCoOdebrac = false;
        if($entity->getOdebranie()){
            //sprawdzamy czy w ogole jest co odebrac
            $sams = explode(",", $entity->getPracownicy());
            $uzs = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findBy(array('samaccountname' => $sams, 'czyAktywne' => true, 'czyNadane' => true));
            
            $jestCoOdebrac = count($uzs) > 0;
            
            //die(count($uzs).".");  
        }
        if ($form->isValid() && (($entity->getOdebranie() && $jestCoOdebrac) || !$entity->getOdebranie())) {
            $em = $this->getDoctrine()->getManager();
            $this->setWniosekStatus($entity, "00_TWORZONY", false);
            $em->persist($entity);
            $em->persist($entity->getWniosek());
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'Wniosek został utworzony.');
                //return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow'));
                
                
                
                $prs = explode(",", $entity->getPracownicy());
                $pr = array();
                foreach($prs as $p){
                    $pr[$p] = 1;
                }
                
                return $this->redirect($this->generateUrl('addRemoveAccessToUsersAction', array(
                    'samaccountnames' => json_encode($pr),
                    'action' => ($entity->getOdebranie() ? 'removeResources' : 'addResources'),
                    'wniosekId' => $entity->getId()
                )));
        }
        if($entity->getOdebranie() && !$jestCoOdebrac){
            $msg = ("Nie można utworzyć takiego wniosku bo żadna z osób nie ma dostępu do żadnych zasobów - nie ma co odebrać!!!");
        }
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'message' => $msg
        );
    }
    private function getUsers(){
        
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        $users = array();
        foreach($ADUsers as $u){
            $users[$u['samaccountname']] = $u['name'];
        }
        return $users;
    }
    /**
     * Creates a form to create a WniosekNadanieOdebranieZasobow entity.
     *
     * @param WniosekNadanieOdebranieZasobow $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(WniosekNadanieOdebranieZasobow $entity)
    {
        
        $form = $this->createForm(new WniosekNadanieOdebranieZasobowType($this->getUsers()), $entity, array(
            'action' => $this->generateUrl('wnioseknadanieodebraniezasobow_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Przejdź do wyboru zasobów', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/new/{odebranie}", name="wnioseknadanieodebraniezasobow_new", defaults={"odebranie" : 0})
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobow:edit.html.twig")
     */
    public function newAction($odebranie = 0)
    {
        //var_dump($this->getUser());
        
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($this->getUser()->getUsername());
        
        $status = $this->getDoctrine()->getManager()->getRepository('Parp\MainBundle\Entity\WniosekStatus')->findOneByNazwaSystemowa('00_TWORZONY');
        
        $entity = new WniosekNadanieOdebranieZasobow();
        $entity->getWniosek()->setCreatedAt(new \Datetime());
        $entity->getWniosek()->setLockedAt(new \Datetime());
        $entity->getWniosek()->setCreatedBy($this->getUser()->getUsername());
        $entity->getWniosek()->setLockedBy($this->getUser()->getUsername());
        $entity->getWniosek()->setNumer('wniosek w trakcie tworzenia');
        $entity->getWniosek()->setJednostkaOrganizacyjna($ADUser[0]['department']);
        $entity->getWniosek()->setStatus($status);
        $entity->setOdebranie($odebranie);
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'message' => '',
            'userzasoby' => []
        );
    }
    protected function addViewersEditors($wniosek, &$where, $who){
        if ($this->debug) echo "<br>addViewersEditors ".$who."<br>";
        
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();
        switch($who){
            case "wnioskodawca":
                //
                $where[$wniosek->getCreatedBy()] = $wniosek->getCreatedBy();
                if ($this->debug) echo "<br>added ".$wniosek->getCreatedBy()."<br>";
                break;
            case "podmiot":
                //
                foreach($wniosek->getWniosekNadanieOdebranieZasobow()->getUserZasoby() as $u){
                    $where[$u->getSamaccountname()] = $u->getSamaccountname();   
                    if ($this->debug) echo "<br>added ".$u->getSamaccountname()."<br>"; 
                }
                break;
            case "przelozony":
                //bierze managera tworzacego - jednak nie , ma byc po podmiotach
                //$ADUser = $ldap->getUserFromAD($wniosek->getCreatedBy());  
                if($wniosek->getWniosekNadanieOdebranieZasobow()->getPracownikSpozaParp()){
                    //biore managera z pola managerSpoząParp
                    $ADManager = $ldap->getUserFromAD($wniosek->getWniosekNadanieOdebranieZasobow()->getManagerSpozaParp());
                    if(count($ADManager) == 0){
                        die("Nie moge znalezc przelozonego dla osoby : ".$wniosek->getWniosekNadanieOdebranieZasobow()->getPracownicySpozaParp()." z managerem ".$wniosek->getWniosekNadanieOdebranieZasobow()->getManagerSpozaParp());
                    }
                    //$przelozeni[$ADManager[0]['samaccountname']][] = $uz;
                }else{
                    //bierze pierwszego z userow , bo zalozenie ze wniosek juz rozbity po przelozonych
                    $uss = explode(",", $wniosek->getWniosekNadanieOdebranieZasobow()->getPracownicy());
                    $ADUser = $ldap->getUserFromAD($uss[0]);  
                    $mgr = mb_substr($ADUser[0]['manager'], mb_stripos($ADUser[0]['manager'], '=') + 1, (mb_stripos($ADUser[0]['manager'], ',OU')) - (mb_stripos($ADUser[0]['manager'], '=') + 1));
                    
                    
                    $mancn = str_replace("CN=", "", substr($mgr, 0, stripos($mgr, ',')));
                    $ADManager = $ldap->getUserFromAD(null, $mgr);
                }
                print_r($ADManager[0]['samaccountname']);
                $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                if ($this->debug) echo "<br>added ".$ADManager[0]['samaccountname']."<br>";
                break;
            case "ibi":
                //
                $em = $this->getDoctrine()->getManager();
                $role = $em->getRepository('ParpMainBundle:AclRole')->findOneByName("IBI");
                $users = $em->getRepository('ParpMainBundle:AclUserRole')->findByRole($role);
                foreach($users as $u){
                    $where[$u->getSamaccountname()] = $u->getSamaccountname(); 
                    if ($this->debug) echo "<br>added ".$u->getSamaccountname()."<br>";
                }
                break;
            case "wlasciciel":
                //
                foreach($wniosek->getWniosekNadanieOdebranieZasobow()->getUserZasoby() as $u){
                    $zasob = $em->getRepository('ParpMainBundle:Zasoby')->find($u->getZasobId());
                    $grupa = explode(",", $zasob->getWlascicielZasobu());
                    foreach($grupa as $g){
                        $mancn = str_replace("CN=", "", substr($g, 0, stripos($g, ',')));
                        //$g = $this->get('renameService')->fixImieNazwisko($g);
                        $ADManager = $ldap->getUserFromAD(null, $g);
                        if(count($ADManager) > 0){
                            if ($this->debug) echo "<br>added ".$ADManager[0]['name']."<br>";
                            $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                        }else{
                            //throw $this->createNotFoundException('Nie moge znalezc wlasciciel zasobu w AD : '.$g);
                        }
                    }
                }
                break;
            case "administrator":
                //
                foreach($wniosek->getWniosekNadanieOdebranieZasobow()->getUserZasoby() as $u){
                    $zasob = $em->getRepository('ParpMainBundle:Zasoby')->find($u->getZasobId());
                    $grupa = explode(",", $zasob->getAdministratorZasobu());
                    foreach($grupa as $g){
                        $mancn = str_replace("CN=", "", substr($g, 0, stripos($g, ',')));
                        $g = $this->get('renameService')->fixImieNazwisko($g);
                        $ADManager = $ldap->getUserFromAD(null, $g);
                        if(count($ADManager) > 0){
                            if ($this->debug) echo "<br>added ".$ADManager[0]['name']."<br>";
                            $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                        }
                        else{
                            //throw $this->createNotFoundException('Nie moge znalezc administrator zasobu w AD : '.$g);
                        }
                    }
                }
                break;
            case "techniczny":
                //
                foreach($wniosek->getWniosekNadanieOdebranieZasobow()->getUserZasoby() as $u){
                    $zasob = $em->getRepository('ParpMainBundle:Zasoby')->find($u->getZasobId());
                    $grupa = explode(",", $zasob->getAdministratorTechnicznyZasobu());
                    foreach($grupa as $g){
                        //$mancn = str_replace("CN=", "", substr($g, 0, stripos($g, ',')));
                        $g = $this->get('renameService')->fixImieNazwisko($g);
                        $ADManager = $ldap->getUserFromAD(null, $g);
                        $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                        if ($this->debug) echo "<br>added ".$ADManager[0]['name']."<br>";
                    }
                }
                break;
        }
    }
    protected function setWniosekStatus($wniosek, $statusName, $rejected){
        if ($this->debug) echo "<br>setWniosekStatus ".$statusName."<br>";
        
        
        $em = $this->getDoctrine()->getManager();
        $status = $em->getRepository('ParpMainBundle:WniosekStatus')->findOneByNazwaSystemowa($statusName);
        $wniosek->getWniosek()->setStatus($status);
        $wniosek->getWniosek()->setLockedBy(null);
        $wniosek->getWniosek()->setLockedAt(null);
        $viewers = array();
        $editors = array();
        $vs = explode(",",$status->getViewers());
        foreach($vs as $v){
            $this->addViewersEditors($wniosek->getWniosek(), $viewers, $v);
        }
        //if(!$rejected){
            $es = explode(",", $status->getEditors());
            foreach($es as $e){
                $this->addViewersEditors($wniosek->getWniosek(), $editors, $e);
            }
        //}
        
        
        //kasuje viewerow
        foreach($wniosek->getWniosek()->getViewers() as $v){
            $wniosek->getWniosek()->removeViewer($v);
            $em->remove($v);
        }
        //kasuje editorow
        foreach($wniosek->getWniosek()->getEditors() as $v){
            $wniosek->getWniosek()->removeEditor($v);
            $em->remove($v);
        }
        //dodaje viewerow 
        foreach($viewers as $v){
            $wv = new \Parp\MainBundle\Entity\WniosekViewer();
            $wv->setWniosek($wniosek->getWniosek());
            $wniosek->getWniosek()->addViewer($wv);
            $wv->setSamaccountname($v);
            if ($this->debug) echo "<br>dodaje usera viewra ".$v;
            $em->persist($wv);
        }
        $wniosek->getWniosek()->setViewernamesSet();
        //dodaje editorow
        foreach($editors as $v){
            $wv = new \Parp\MainBundle\Entity\WniosekEditor();
            $wv->setWniosek($wniosek->getWniosek());
            $wniosek->getWniosek()->addEditor($wv);
            $wv->setSamaccountname($v);
            if ($this->debug) echo "<br>dodaje usera editora ".$v;
            $em->persist($wv);
        }
        
        $wniosek->getWniosek()->setEditornamesSet();
        
        //wstawia historie statusow
        $sh = new \Parp\MainBundle\Entity\WniosekHistoriaStatusow();
        $sh->setWniosek($wniosek->getWniosek());
        $wniosek->getWniosek()->addStatusy($sh);
        $sh->setCreatedAt(new \Datetime());
        $sh->setRejected($rejected);
        $sh->setCreatedBy($this->getUser()->getUsername());
        $sh->setStatus($status);
        $sh->setStatusName($status->getNazwa());
        $sh->setOpis($status->getNazwa());
        $em->persist($sh);
    }

    /**
     * Finds and displays a WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/{id}/{isAccepted}/accept_reject", name="wnioseknadanieodebraniezasobow_accept_reject")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function acceptRejectAction(Request $request, $id, $isAccepted)
    {
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();

        $wniosek = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($id);
        $uzs = $em->getRepository('ParpMainBundle:UserZasoby')->findByWniosekWithZasob($wniosek);
        //print_r($uzs); die();
        if (!$wniosek) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }
        if($request->isMethod('POST')){
            $txt = $request->get('powodZwrotu');
            $wniosek->setPowodZwrotu($txt);
            
            $kom = new \Parp\MainBundle\Entity\Komentarz();
            $kom->setObiekt('WniosekNadanieOdebranieZasobow');
            $kom->setObiektId($id);
            $kom->setTytul("Wniosek zwrócony z powodu:");
            $kom->setOpis($txt);
            $kom->setSamaccountname($this->getUser()->getUsername());
            $kom->setCreatedAt(new \Datetime());
            $em->persist($kom);
            
        }else{
            $wniosek->setPowodZwrotu("");
        }
        if($isAccepted == "unblock"){
            $wniosek->setLockedBy(null);
            $wniosek->setLockedAt(null);
        }
        elseif($isAccepted == "reject"){
            //przenosi do status 8
            $this->setWniosekStatus($wniosek, "08_ROZPATRZONY_NEGATYWNIE", true);
        }else{
            switch($wniosek->getWniosek()->getStatus()->getNazwaSystemowa()){
                case "00_TWORZONY":
                    switch($isAccepted){
                        case "accept":
                            $this->get('wniosekNumer')->nadajNumer($wniosek, "wniosekONadanieUprawnien");
                            //klonuje wniosek na male i ustawia im statusy:
                            $przelozeni = array();
                            foreach($wniosek->getUserZasoby() as $uz){
                                if($wniosek->getPracownikSpozaParp()){
                                    //biore managera z pola managerSpoząParp
                                    $ADManager = $ldap->getUserFromAD($wniosek->getManagerSpozaParp());
                                    if(count($ADManager) == 0){
                                        die("Nie moge znalezc przelozonego dla osoby : ".$uz->getSamaccountname());
                                    }
                                    $przelozeni[$ADManager[0]['samaccountname']][] = $uz;
                                }else{
                                    $ADUser = $ldap->getUserFromAD($uz->getSamaccountname());  
                                    $mgr = mb_substr($ADUser[0]['manager'], mb_stripos($ADUser[0]['manager'], '=') + 1, (mb_stripos($ADUser[0]['manager'], ',OU')) - (mb_stripos($ADUser[0]['manager'], '=') + 1));
                                    
                                    
                                    $mancn = str_replace("CN=", "", substr($mgr, 0, stripos($mgr, ',')));
                                    $ADManager = $ldap->getUserFromAD(null, $mgr);
                                    if(count($ADManager) == 0){
                                        die("Nie moge znalezc przelozonego dla osoby : ".$uz->getSamaccountname());
                                    }
                                    $przelozeni[$ADManager[0]['samaccountname']][] = $uz;
                                }
                            }
                            if ($this->debug) echo "<pre>"; \Doctrine\Common\Util\Debug::dump($przelozeni); echo "</pre>"; 
                            if(count($przelozeni) > 1){
                                $numer = 1;
                                //teraz dla kazdego przelozonego tworzy oddzielny wniosek
                                $this->setWniosekStatus($wniosek, "10_PODZIELONY", false);
                                foreach($przelozeni as $sam => $p){
                                    if ($this->debug) echo "<br><br>Tworzy nowy wniosek dla przelozonego  ".$sam." wzietego z osoby  ".$p[0]->getSamaccountname()." :<br><br>";
                                    $wn = new \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow();
                                    $wn->getWniosek()->setCreatedBy($wniosek->getWniosek()->getCreatedBy());
                                    $wn->getWniosek()->setCreatedAt($wniosek->getWniosek()->getCreatedAt());
                                    $wn->getWniosek()->setLockedBy($wniosek->getWniosek()->getLockedBy());
                                    $wn->getWniosek()->setLockedAt($wniosek->getWniosek()->getLockedAt());
                                    $wn->getWniosek()->setParent($wniosek->getWniosek());
                                    $wn->getWniosek()->setJednostkaOrganizacyjna($wniosek->getWniosek()->getJednostkaOrganizacyjna());
                                    $wn->setPracownikSpozaParp($wniosek->getPracownikSpozaParp());
                                    $wn->getWniosek()->setNumer($numer++);
                                    $users = array();
                                    foreach($p as $uz){
                                        $nuz = clone $uz;
                                        $em->persist($nuz);
                                        $wn->setZasobId($nuz->getZasobId());
                                        $users[$nuz->getSamaccountname()] =  $nuz->getSamaccountname();
                                        $nuz->setWniosek($wn);
                                        $wn->addUserZasoby($nuz);
                                    }
                                    $wn->setPracownicy(implode(",", $users));
                                    //klonuje wszystkie historie statusow
                                    foreach($wniosek->getWniosek()->getStatusy() as $s){
                                        $s2 = clone $s;
                                        $s2->setWniosek($wn->getWniosek());
                                        $em->persist($s2);
                                    }
                                    $this->setWniosekStatus($wn, ($wniosek->getOdebranie() ? "05_EDYCJA_ADMINISTRATOR" : "02_EDYCJA_PRZELOZONY"), false);
                                    $em->persist($wn->getWniosek());
                                    $em->persist($wn);
                                }
                            }else{
                                $this->setWniosekStatus($wniosek, ($wniosek->getOdebranie() ? "05_EDYCJA_ADMINISTRATOR" : "02_EDYCJA_PRZELOZONY"), false);
                            }
                            //$em->remove($wniosek);
                            if ($this->debug) die('<br>wszystko poszlo ok');
                            break;
                        case "return":
                            //nie powinno miec miejsca
                            die('nie powinno miec miejsca');
                            break;
                    }
                    break;
                case "01_EDYCJA_WNIOSKODAWCA":
                    switch($isAccepted){
                        case "accept":
                            //przenosi do status 2
                            $this->setWniosekStatus($wniosek, ($wniosek->getOdebranie() ? "05_EDYCJA_ADMINISTRATOR" : "02_EDYCJA_PRZELOZONY"), false);
                            break;
                        case "return":
                            //przenosi do status 1
                            die('nie powinno miec miejsca');
                            break;
                    }
                    break;
                case "02_EDYCJA_PRZELOZONY":
                    switch($isAccepted){
                        case "accept":
                            //klonuje wniosek na male i ustawia im statusy:
                            $zasoby = array();
                            foreach($wniosek->getUserZasoby() as $uz){
                                $zasoby[$uz->getZasobId()][] = $uz;
                            }
                            if(count($zasoby) > 1){
                                $this->setWniosekStatus($wniosek, "10_PODZIELONY", false);
                                $numer = 1;
                                //teraz dla kazdego zasobu tworzy oddzielny wniosek
                                foreach($zasoby as $z){
                                    if ($this->debug) echo "<br><br>Tworzy nowy wniosek dla zasobu ".$z->getZasobId()."<br><br>";
                                        $wn = new \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow();
                                        $wn->getWniosek()->setCreatedBy($wniosek->getWniosek()->getCreatedBy());
                                        $wn->getWniosek()->setCreatedAt($wniosek->getWniosek()->getCreatedAt());
                                        $wn->getWniosek()->setLockedBy($wniosek->getWniosek()->getLockedBy());
                                        $wn->getWniosek()->setLockedAt($wniosek->getWniosek()->getLockedAt());
                                        $wn->getWniosek()->setParent($wniosek->getWniosek());
                                        $wn->getWniosek()->setJednostkaOrganizacyjna($wniosek->getWniosek()->getJednostkaOrganizacyjna());
                                        $wn->setPracownikSpozaParp($wniosek->getPracownikSpozaParp());
                                        $wn->setManagerSpozaParp($wniosek->getManagerSpozaParp());
                                        $wn->getWniosek()->setNumer($numer++);
                                        $users = array();
                                        foreach($z as $uz){
                                            $nuz = clone $uz;
                                            $em->persist($nuz);
                                            $wn->setZasobId($nuz->getZasobId());
                                            $users[$nuz->getSamaccountname()] =  $nuz->getSamaccountname();
                                            $nuz->setWniosek($wn);
                                            $wn->addUserZasoby($nuz);
                                        }
                                        $wn->setPracownicy(implode(",", $users));
                                        //klonuje wszystkie historie statusow
                                        foreach($wniosek->getWniosek()->getStatusy() as $s){
                                            $s2 = clone $s;
                                            $s2->setWniosek($wn->getWniosek());
                                            $em->persist($s2);
                                        }
                                        $this->setWniosekStatus($wn, "03_EDYCJA_WLASCICIEL", false);
                                        $em->persist($wn->getWniosek());
                                        $em->persist($wn);
                                }
                            }else{
                                $this->setWniosekStatus($wniosek, "03_EDYCJA_WLASCICIEL", false);
                            }
                            break;
                        case "return":
                            $this->setWniosekStatus($wniosek, ("01_EDYCJA_WNIOSKODAWCA"), true);
                            break;
                    }
                    break;
                case "03_EDYCJA_WLASCICIEL":
                    switch($isAccepted){
                        case "accept":
                            $maBycIbi = false;
                            foreach($wniosek->getUSerZasoby() as $uz){
                                $maBycIbi = $maBycIbi || $uz->getUprawnieniaAdministracyjne();
                            }
                            
                            if($maBycIbi){
                                $this->setWniosekStatus($wniosek, "04_EDYCJA_IBI", false);
                            }else{
                                $this->setWniosekStatus($wniosek, "05_EDYCJA_ADMINISTRATOR", false);                                
                            }
                            
                            break;
                        case "return":
                            $this->setWniosekStatus($wniosek, "02_EDYCJA_PRZELOZONY", true);
                            break;
                    }
                    break;
                case "04_EDYCJA_IBI":
                    switch($isAccepted){
                        case "accept":
                            $this->setWniosekStatus($wniosek, "05_EDYCJA_ADMINISTRATOR", false);
                            break;
                        case "return":
                            $this->setWniosekStatus($wniosek, "03_EDYCJA_WLASCICIEL", true);
                            break;
                    }
                    break;
                case "05_EDYCJA_ADMINISTRATOR":
                    switch($isAccepted){
                        case "accept":
                            $this->setWniosekStatus($wniosek, "06_EDYCJA_TECHNICZNY", false);
                            break;
                        case "return":
                            $this->setWniosekStatus($wniosek, "04_EDYCJA_IBI", true);
                            break;
                    }
                    break;
                case "06_EDYCJA_TECHNICZNY":
                    switch($isAccepted){
                        case "accept":
                            $isAccepted = "acceptAndPublish";
                            $this->setWniosekStatus($wniosek, "07_ROZPATRZONY_POZYTYWNIE", false);
                            break;
                        case "return":
                            $this->setWniosekStatus($wniosek, "05_EDYCJA_ADMINISTRATOR", true);
                            break;
                    }
                    break;
            }
            
            if($isAccepted == "acceptAndPublish"){
                foreach($wniosek->getUserZasoby() as $uz){
                    $z = $em->getRepository('ParpMainBundle:Zasoby')->find($uz->getZasobId());
                    $this->get('uprawnieniaservice')->wyslij(
                        array(
                            'cn' => '', 
                            'samaccountname' => $uz->getSamaccountname(), 
                            'fromWhen' => new \Datetime()
                        ), array(), 
                        array($z->getNazwa())
                    );
                }
            }
        }
        //die('a');
        $em->flush();

        
        if($isAccepted == "unblock"){
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow', array(
            )));
        }elseif($wniosek->getWniosek()->getStatus()->getNazwaSystemowa() == "00_TWORZONY"){
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow', array(
            )));
            
        }else{
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array(
                'id' => $id
            )));
        }
    }
    /**
     * Finds and displays a WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/{id}/show", name="wnioseknadanieodebraniezasobow_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($id);
        $uzs = $em->getRepository('ParpMainBundle:UserZasoby')->findByWniosekWithZasob($entity);
        //print_r($uzs); die();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }
        
        $editor = $em->getRepository('ParpMainBundle:WniosekEditor')->findOneBy(array(
            'samaccountname' => $this->getUser()->getUsername(),
            'wniosek' => $entity
            )
        );
        if($entity->getWniosek()->getLockedBy()){
            $editor = $entity->getWniosek()->getLockedBy() == $this->getUser()->getUsername();
        }elseif($editor){
            $entity->getWniosek()->setLockedBy($this->getUser()->getUsername());
            $entity->getWniosek()->setLockedAt(new \Datetime());
            $em->flush();
        }
        //die(($editor->getId()).".");
        $viewer = $em->getRepository('ParpMainBundle:WniosekViewer')->findOneBy(array(
            'samaccountname' => $this->getUser()->getUsername(),
            'wniosek' => $entity
            )
        );
        
        if (!$editor && !$viewer) {
            
            
            return $this->render("ParpMainBundle:WniosekNadanieOdebranieZasobow:denied.html.twig", array('wniosek' => $entity, 'viewer' => 0));
        }
        
        
        
        if(substr($entity->getWniosek()->getStatus()->getNazwaSystemowa(), 0, 1) == "1"){
            $editor = false;
        }
        

        $deleteForm = $this->createDeleteForm($id);
        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
            'userzasoby' => $uzs,
            'editor' => $editor,
            'canReturn' => ($entity->getWniosek()->getStatus()->getNazwaSystemowa() != "00_TWORZONY" && $entity->getWniosek()->getStatus()->getNazwaSystemowa() != "01_EDYCJA_WNIOSKODAWCA"),
            'canUnblock' => ($entity->getWniosek()->getLockedBy() == $this->getUser()->getUsername())
        );
    }
    
    /**
     * Finds and displays a WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/{id}", name="wnioseknadanieodebraniezasobow_delete")
     * @Method("GET")
     * @Template()
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/{id}/delete_uz", name="wnioseknadanieodebraniezasobow_delete_uz")
     * @Method("GET")
     * @Template()
     */
    public function deleteUzAction($id)
    {
         
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:UserZasoby')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find UserZasoby entity.');
        }   
        $wniosekId = $entity->getWniosek()->getId();
        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array('id' => $wniosekId)));
    }
    /**
     * Displays a form to edit an existing WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/{id}/edit", name="wnioseknadanieodebraniezasobow_edit")
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobow:edit.html.twig")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();



        $entity = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }
        
        $editor = $em->getRepository('ParpMainBundle:WniosekEditor')->findOneBy(array(
            'samaccountname' => $this->getUser()->getUsername(),
            'wniosek' => $entity
            )
        );
        if($entity->getWniosek()->getLockedBy()){
            $editor = $entity->getWniosek()->getLockedBy() == $this->getUser()->getUsername();
        }else{
            $entity->getWniosek()->setLockedBy($this->getUser()->getUsername());
            $entity->getWniosek()->setLockedAt(new \Datetime());
            $em->flush();
        }
        $viewer = $em->getRepository('ParpMainBundle:WniosekViewer')->findOneBy(array(
            'samaccountname' => $this->getUser()->getUsername(),
            'wniosek' => $entity
            )
        );
        if (!$editor) {
            
            
            return $this->render("ParpMainBundle:WniosekNadanieOdebranieZasobow:denied.html.twig", array('wniosek' => $entity, 'viewer' => ($viewer ? 1 : 0)));
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        $uzs = $em->getRepository('ParpMainBundle:UserZasoby')->findByWniosekWithZasob($entity);
        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'userzasoby' => $uzs
        );
    }

    /**
    * Creates a form to edit a WniosekNadanieOdebranieZasobow entity.
    *
    * @param WniosekNadanieOdebranieZasobow $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(WniosekNadanieOdebranieZasobow $entity)
    {
        $form = $this->createForm(new WniosekNadanieOdebranieZasobowType($this->getUsers()), $entity, array(
            'action' => $this->generateUrl('wnioseknadanieodebraniezasobow_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/{id}", name="wnioseknadanieodebraniezasobow_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobow:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/{id}", name="wnioseknadanieodebraniezasobow_delete_form")
     * @Method("DELETE")
     */
    public function deleteFormAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow'));
    }

    /**
     * Creates a form to delete a WniosekNadanieOdebranieZasobow entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('wnioseknadanieodebraniezasobow_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj wniosek','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
