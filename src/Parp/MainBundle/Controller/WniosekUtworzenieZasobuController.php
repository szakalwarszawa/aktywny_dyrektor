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

use Parp\MainBundle\Entity\WniosekUtworzenieZasobu;
use Parp\MainBundle\Form\WniosekUtworzenieZasobuType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use Symfony\Component\HttpFoundation\Response;

/**
 * WniosekUtworzenieZasobu controller.
 *
 * @Route("/wniosekutworzeniezasobu")
 */
class WniosekUtworzenieZasobuController extends Controller
{
    protected $debug = false;

    /**
     * Lists all WniosekUtworzenieZasobu entities.
     *
     * @Route("/index/{ktore}", name="wniosekutworzeniezasobu", defaults={"ktore" : "oczekujace"})
     * @Template()
     */
    public function indexAction($ktore = "oczekujace")
    {
        $em = $this->getDoctrine()->getManager();
        $grid = $this->generateGrid($ktore);
        //$grid2 = $this->generateGrid("oczekujace");
        //$grid3 = $this->generateGrid("zamkniete");
        $zastepstwa = $em->getRepository('ParpMainBundle:Zastepstwo')->znajdzZastepstwa($this->getUser()->getUsername());
        
        if ($grid->isReadyForRedirect() /* || $grid2->isReadyForRedirect() || $grid3->isReadyForRedirect() */ )
        {
            if ($grid->isReadyForExport())
            {
                return $grid->getExportResponse();
            }
        
/*
            if ($grid2->isReadyForExport())
            {
                return $grid2->getExportResponse();
            }
            
            if ($grid3->isReadyForExport())
            {
                return $grid3->getExportResponse();
            }
*/
        
            // Url is the same for the grids
            return new \Symfony\Component\HttpFoundation\RedirectResponse($grid->getRouteUrl());
        }
        else
        {
            return $this->render('ParpMainBundle:WniosekUtworzenieZasobu:index.html.twig', array('ktore' => $ktore, 'grid' => $grid, 'zastepstwa' => $zastepstwa));
        }
    }

    protected function generateGrid($ktore){
        $em = $this->getDoctrine()->getManager();
        
        
        
        
        
        //$entities = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobow')->findAll();
        $zastepstwa = $em->getRepository('ParpMainBundle:Zastepstwo')->znajdzKogoZastepuje($this->getUser()->getUsername());
        $source = new Entity('ParpMainBundle:WniosekUtworzenieZasobu');
        $tableAlias = $source->getTableAlias();
        //die($co);
        $sam = $this->getUser()->getUsername();
        $source->manipulateQuery(
            function ($query) use ($tableAlias, $zastepstwa, $ktore)
            {
                $query->leftJoin($tableAlias . '.wniosek', 'w');
                $query->leftJoin('w.viewers', 'v');
                $query->leftJoin('w.editors', 'e');
                $query->leftJoin('w.status', 's');
                if($ktore != "wszystkie"){
                    $query->andWhere('v.samaccountname IN (\''.implode('\',\'', $zastepstwa).'\')');
                }
                
                $statusy = ['08_ROZPATRZONY_NEGATYWNIE_O_ZASOB', '07_ROZPATRZONY_POZYTYWNIE_O_ZASOB', '00_TWORZONY_O_ZASOB'];
                switch($ktore){
                    case "wtoku":
                        $w = 's.nazwaSystemowa NOT IN (\''.implode('\',\'', $statusy).'\')';
                        //rdie($w);
                        $query->andWhere($w);
                        $query->andWhere($tableAlias.'.id NOT in (select wn.id from ParpMainBundle:WniosekUtworzenieZasobu wn left join wn.wniosek w2 left join w2.editors e2 where e2.samaccountname IN (\''.implode('\',\'', $zastepstwa).'\'))');
                        break;
                    case "oczekujace":
                        $query->andWhere('e.samaccountname IN (\''.implode('\',\'', $zastepstwa).'\')');
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
        $rowAction1 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'wniosekutworzeniezasobu_edit');
        $rowAction1->setColumn('akcje');
        $rowAction1->addAttribute('class', 'btn btn-success btn-xs');
        
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Pokaż', 'wniosekutworzeniezasobu_show');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-info btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'wniosekutworzeniezasobu_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
        $rowAction3->manipulateRender(
            function ($action, $row)
            {
                if ($row->getField('wniosek.numer') == "wniosek w trakcie tworzenia") {
                    
                    return $action;
                }else{
                    return null;
                }
        
            }
        );
        //die('a');
       
    
        //$grid->addRowAction($rowAction1);
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        //$grid->isReadyForRedirect();
        return $grid;

    }
    
    
    
    
    
    /**
     * Creates a new WniosekUtworzenieZasobu entity.
     *
     * @Route("/create", name="wniosekutworzeniezasobu_create")
     * @Method("POST")
     * @Template("ParpMainBundle:WniosekUtworzenieZasobu:edit.html.twig")
     */
    public function createAction(Request $request)
    {
        //echo "<pre>";        \Doctrine\Common\Util\Debug::dump($request->request->get('parp_mainbundle_wniosekutworzeniezasobu')['typWnioskuZmianaInformacji'],10); die();
        $entity = new WniosekUtworzenieZasobu();
        
        if(isset($request->request->get('parp_mainbundle_wniosekutworzeniezasobu')['typWnioskuDoRejestru']))
            $this->ustawTyp($entity, 'parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoRejestru');
        
        if(isset($request->request->get('parp_mainbundle_wniosekutworzeniezasobu')['typWnioskuDoUruchomienia']))
            $this->ustawTyp($entity, 'parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoUruchomienia');
        
        if(isset($request->request->get('parp_mainbundle_wniosekutworzeniezasobu')['typWnioskuZmianaInformacji']))
            $this->ustawTyp($entity, 'parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaInformacji');
        
        if(isset($request->request->get('parp_mainbundle_wniosekutworzeniezasobu')['typWnioskuZmianaWistniejacym']))
            $this->ustawTyp($entity, 'parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaWistniejacym');
        
        if(isset($request->request->get('parp_mainbundle_wniosekutworzeniezasobu')['typWnioskuWycofanie']))
            $this->ustawTyp($entity, 'parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanie');
        
        if(isset($request->request->get('parp_mainbundle_wniosekutworzeniezasobu')['typWnioskuWycofanieZinfrastruktury']))
            $this->ustawTyp($entity, 'parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanieZinfrastruktury');
        
        
                //die(".".$entity->getTyp());
        
        $form = $this->createCreateForm($entity, $entity->getTyp());
        $form->handleRequest($request);
            ///die('d');

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            //echo "<pre>";        \Doctrine\Common\Util\Debug::dump($entity,10); die();
            $em->persist($entity);
            $em->persist($entity->getWniosek());
            switch($entity->getTyp()){
                case "nowy":
                    $entity->getZasob()->setWniosekUtworzenieZasobu($entity);
                    break;
                case "kasowanie":
                    $entity->getZmienianyZasob()->setWniosekSkasowanieZasobu($entity);
                    break;
                case "zmiana":
                    $entity->getZmienianyZasob()->addWnioskiZmieniajaceZasob($entity);
                    break;
            }
            if($entity->getZasob()){
                $em->persist($entity->getZasob());
            }
            $this->setWniosekStatus($entity, "00_TWORZONY_O_ZASOB", false);
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'Wniosek został utworzony.');
                return $this->redirect($this->generateUrl('wniosekutworzeniezasobu_show', ['id' => $entity->getId()]));
        }else{
            
            $er = $form->getErrorsAsString();
            die($er);
        }

        return array(
            'editor' => false,
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a WniosekUtworzenieZasobu entity.
     *
     * @param WniosekUtworzenieZasobu $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(WniosekUtworzenieZasobu $entity, $hideCheckboxes = true)
    {
        $form = $this->createForm(new WniosekUtworzenieZasobuType($this->getUsersFromAD(), $this->getManagers(), $entity->getTyp(), $entity, $this), $entity, array(
            'action' => $this->generateUrl('wniosekutworzeniezasobu_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz Wniosek', 'attr' => array('class' => 'btn btn-success' )));
        $form->add('submit2', 'submit', array('label' => 'Utwórz Wniosek', 'attr' => array('class' => 'btn btn-success' )));
        $form->add('dalej', 'button', array( 'label' => 'Dalej', 'attr' => array('class' => 'btn btn-success' )));
        $form->add('dalej2', 'button', array('label' => 'Dalej', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new WniosekUtworzenieZasobu entity.
     *
     * @Route("/new", name="wniosekutworzeniezasobu_new")
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekUtworzenieZasobu:wybierz_typ_wniosku.html.twig")
     */
    public function newAction()
    {
        $entity = new WniosekUtworzenieZasobu();
        $form   = $this->createCreateForm($entity, $entity->getTyp());
        return ['form' => $form->createView(),
            'editor' => false,'delta' => [], 'readonly' => false,
            'canUnblock' => false
            ];
    }
    protected function ustawTyp($entity, $typ){
        switch($typ){
            case "parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoRejestru":
                $entity->setTypWnioskuDoRejestru(true);
                break;
            case "parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoUruchomienia":
                $entity->setTypWnioskuDoUruchomienia(true);
                break;
            case "parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaInformacji":
                $entity->setTypWnioskuZmianaInformacji(true);
                break;
            case "parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaWistniejacym":
                $entity->setTypWnioskuZmianaWistniejacym(true);
                break;
            case "parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanie":
                $entity->setTypWnioskuWycofanie(true);
                
                $entity->setZasob(null);
                break;
            case "parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanieZinfrastruktury":
                $entity->setTypWnioskuWycofanieZinfrastruktury(true);
                break;
        }
    }
    /**
     * Displays a form to create a new WniosekUtworzenieZasobu entity.
     *
     * @Route("/new_z_typem/{typ1}/{typ2}", name="wniosekutworzeniezasobu_new_z_type", options={"expose"=true}, defaults={"typ2"=""})
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekUtworzenieZasobu:edit.html.twig")
     */
    public function newWithTypeAction($typ1, $typ2="")
    {
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($this->getUser()->getUsername());
        
        $status = $this->getDoctrine()->getManager()->getRepository('Parp\MainBundle\Entity\WniosekStatus')->findOneByNazwaSystemowa('00_TWORZONY_O_ZASOB');
        
        $entity = new WniosekUtworzenieZasobu();
        //var_dump($typ1, $typ2); die();
        $this->ustawTyp($entity, $typ1);
        if($typ2 != ""){
            $this->ustawTyp($entity, $typ2);    
        }
        
        $entity->getWniosek()->setCreatedAt(new \Datetime());
        $entity->getWniosek()->setLockedAt(new \Datetime());
        $entity->getWniosek()->setCreatedBy($this->getUser()->getUsername());
        $entity->getWniosek()->setLockedBy($this->getUser()->getUsername());
        $entity->getWniosek()->setNumer('wniosek w trakcie tworzenia');
        $entity->getWniosek()->setJednostkaOrganizacyjna($ADUser[0]['department']);
        $entity->getWniosek()->setStatus($status);
        $entity->setImienazwisko($ADUser[0]['name']);
        $entity->setLogin($ADUser[0]['samaccountname']);
        $entity->setDepartament($ADUser[0]['department']);
        $entity->setStanowisko($ADUser[0]['title']);
        $departament = $this->getDoctrine()->getManager()->getRepository('Parp\MainBundle\Entity\Departament')->findOneByName($ADUser[0]['department']);
        if($entity->getZasob())
            $entity->getZasob()->setKomorkaOrgazniacyjna($departament);
        $form   = $this->createCreateForm($entity);
        $this->getDoctrine()->getManager()->persist($entity->getWniosek());
        

        //echo "<pre>"; print_r($ADUser); die();

        return array(
            'editor' => false,
            'entity' => $entity,
            'form'   => $form->createView(),
            'message' => '',
            'readonly' => false,
            'canUnblock' => false
        );
        
    }

    
    /**
     * Finds and displays a WniosekUtworzenieZasobu entity.
     *
     * @Route("/{id}/show", name="wniosekutworzeniezasobu_show")
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekUtworzenieZasobu:edit.html.twig")
     */
    public function showAction($id)
    {
        return $this->editAction($id, true);
    }

    /**
     * Displays a form to edit an existing WniosekUtworzenieZasobu entity.
     *
     * @Route("/{id}/edit", name="wniosekutworzeniezasobu_edit")
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekUtworzenieZasobu:edit.html.twig")
     */
    public function editAction($id, $readonly = false)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekUtworzenieZasobu')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekUtworzenieZasobu entity.');
        }
        $access = $this->checkAccess($entity);
        if(!$access['viewer'] && !$access['editor']){
            return $this->render("ParpMainBundle:WniosekNadanieOdebranieZasobow:denied.html.twig", array('wniosek' => $entity, 'viewer' => 0));
        }
        //die(count($uzs).">");
        $editor = $access['editor'];
        if(substr($entity->getWniosek()->getStatus()->getNazwaSystemowa(), 0, 1) == "1"){
            $editor = false;
        }
        $delta = [];
        if($entity->getTyp() == "zmiana"){
            $delta = $this->obliczZmienionePola($entity);
            $entity->setZmienionePola(implode(",", array_keys($delta)));
        }
        $editForm = $this->createEditForm($entity, true, $readonly);
//        var_dump($entity->getZasob()->getName()); die();
        $comments = $em->getRepository("ParpMainBundle:Komentarz")->getCommentCount("WniosekUtworzenieZasobu", $entity->getId());

        $deleteForm = $this->createDeleteForm($id);
        return array(
            'canReturn' => true, /*(
                $entity->getWniosek()->getStatus()->getNazwaSystemowa() != "00_TWORZONY_O_ZASOB" && 
                $entity->getWniosek()->getStatus()->getNazwaSystemowa() != "01_EDYCJA_WNIOSKODAWCA_O_ZASOB" && 
                $entity->getWniosek()->getStatus()->getNazwaSystemowa() != "04_EDYCJA_ADMINISTRATOR_O_ZASOB" && 
                $entity->getWniosek()->getStatus()->getNazwaSystemowa() != "05_EDYCJA_TECHNICZNY_O_ZASOB"
            ),*/
            'canUnblock' => ($entity->getWniosek()->getLockedBy() == $this->getUser()->getUsername()),
            'editor' => $editor,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'delta' => $delta,
            'readonly' => $readonly,
            'comments' => $comments
        );
    }
    protected function obliczZmienionePola($entity){
        $em = $this->getDoctrine()->getManager();
        $metadata = $em->getClassMetadata("Parp\\MainBundle\\Entity\\Zasoby");
        $z1 = array();
        $z2 = array();
        foreach($metadata->getFieldNames() as $fm){
            $getter = "get".ucfirst($fm);
            $val1 = $entity->getZasob()->{$getter}();
            $val2 = $entity->getZmienianyZasob()->{$getter}();
            if(\is_a($val1, "Datetime")){
                $val1 = $val1->format("Y-m-d");
            }
            if(\is_a($val2, "Datetime")){
                $val2 = $val2->format("Y-m-d");
            }
            $z1[$fm] = $val1;
            $z2[$fm] = $val2;
        }
        
        $delta = array_diff($z1, $z2);
        
        
        unset($delta['id']);
        return $delta;
    }
    /**
    * Creates a form to edit a WniosekUtworzenieZasobu entity.
    *
    * @param WniosekUtworzenieZasobu $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(WniosekUtworzenieZasobu $entity, $hideCheckboxes = true, $readonly = true)
    {
        //var_dump($entity); die();
        $form = $this->createForm(new WniosekUtworzenieZasobuType($this->getUsersFromAD(), $this->getManagers(), $entity->getTyp(), $entity, $this, $readonly), $entity, array(
            'action' => $this->generateUrl('wniosekutworzeniezasobu_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));
        
        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success'.($readonly ? " hidden" : "") )));
        $form->add('submit2', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success'.($readonly ? " hidden" : "") )));
        $form->add('dalej', 'button', array( 'label' => 'Dalej', 'attr' => array('class' => 'btn btn-success'.($readonly ? " hidden" : "") )));
        $form->add('dalej2', 'button', array('label' => 'Dalej', 'attr' => array('class' => 'btn btn-success'.($readonly ? " hidden" : "") )));
        
/*
        foreach($form->all() as $ff){            
            //echo "<pre>"; \Doctrine\Common\Util\Debug::dump($ff); die();
        }
*/
        
        
        

        return $form;
    }
    
    
    /**
     * Edits an existing WniosekUtworzenieZasobu entity.
     *
     * @Route("/wczytaj_dane_zasobu/{id}", name="wniosekutworzeniezasobu_wczytaj_dane_zasobu", options={"expose"=true})
     * @Method("GET")
     * @Template()
     */
    public function wczytajDaneZasobuAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
        }
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizer = new ObjectNormalizer();
        $normalizer->setIgnoredAttributes(array('wniosekUtworzenieZasobu', 'wnioskiZmieniajaceZasob'));
        $normalizers = array($normalizer);
        
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($entity, 'json');
        $response = new \Symfony\Component\HttpFoundation\Response($jsonContent);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    /**
     * Edits an existing WniosekUtworzenieZasobu entity.
     *
     * @Route("/update/{id}", name="wniosekutworzeniezasobu_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:WniosekUtworzenieZasobu:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekUtworzenieZasobu')->find($id);


        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekUtworzenieZasobu entity.');
        }
        $editForm = $this->createEditForm($entity, true, false);
        //var_dump($editForm);
        $editForm->handleRequest($request);

        if ($editForm->isValid() && $editForm->isSubmitted()) {

            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            
            return $this->redirect($this->generateUrl('wniosekutworzeniezasobu_show', array('id' => $id)));
        }else{
            var_dump($editForm->getErrorsAsString());
            die('Blad formularza ');
        }
        
        $access = $this->checkAccess($entity);
        if(!$access['viewer'] && !$access['editor']){
            return $this->render("ParpMainBundle:WniosekNadanieOdebranieZasobow:denied.html.twig", array('wniosek' => $entity, 'viewer' => 0));
        }
        //die(count($uzs).">");
        $editor = $access['editor'];
        if(substr($entity->getWniosek()->getStatus()->getNazwaSystemowa(), 0, 1) == "1"){
            $editor = false;
        }
        

        return array(
            'canReturn' => ($entity->getWniosek()->getStatus()->getNazwaSystemowa() != "00_TWORZONY_O_ZASOB" && $entity->getWniosek()->getStatus()->getNazwaSystemowa() != "01_EDYCJA_WNIOSKODAWCA_O_ZASOB"),
            'canUnblock' => ($entity->getWniosek()->getLockedBy() == $this->getUser()->getUsername()),
            'editor' => $editor,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    
    
    /**
     * Finds and displays a WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/skasuj/{id}", name="wniosekutworzeniezasobu_delete")
     * @Method("GET")
     * @Template()
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekUtworzenieZasobu')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekUtworzenieZasobu entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }
    
    /**
     * Deletes a WniosekNadanieOdebranieZasobow entity.
     *
     * @Route("/skasuj/{id}", name="wniosekutworzeniezasobu_delete_form")
     * @Method("DELETE")
     */
    public function deleteFormAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:WniosekUtworzenieZasobu')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find WniosekUtworzenieZasobu entity.');
            }
            
            $this->get('session')->getFlashBag()->set('warning', 'Wniosek został skasowany.');
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('wniosekutworzeniezasobu'));
    }

    /**
     * Creates a form to delete a WniosekUtworzenieZasobu entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('wniosekutworzeniezasobu_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Wniosek','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
    
    private function getManagers(){
        
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllManagersFromAD();
        $users = array();
        foreach($ADUsers as $u){
            $users[$u['samaccountname']] = $u['name'];
        }
        return $users;
    }
    private function getUsersFromAD(){
        
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        $users = array();
        foreach($ADUsers as $u){
            $users[$u['samaccountname']] = $u['name'];
        }
        return $users;
    }
    
    protected function checkAccess($entity, $onlyEditors = false){
        
        $em = $this->getDoctrine()->getManager();
        $zastepstwa = $em->getRepository('ParpMainBundle:Zastepstwo')->znajdzKogoZastepuje($this->getUser()->getUsername());

        //print_r($uzs); die();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekUtworzenieZasobu entity.');
        }
        
        $editor = $em->getRepository('ParpMainBundle:WniosekEditor')->findOneBy(array(
            'samaccountname' => $zastepstwa, //$this->getUser()->getUsername(),
            'wniosek' => $entity->getWniosek()
            )
        );
        //to sprawdza czy ma bezposredni dostep do edycji bez brania pod uwage zastepstw
        $editorsBezZastepstw = $em->getRepository('ParpMainBundle:WniosekEditor')->findOneBy(array(
            'samaccountname' => $this->getUser()->getUsername(),
            'wniosek' => $entity->getWniosek()
            )
        );
        if($entity->getWniosek()->getLockedBy()){
            if($entity->getWniosek()->getLockedBy() != $this->getUser()->getUsername()){
                $editor = null;
            }
        }elseif($editor){
            $entity->getWniosek()->setLockedBy($this->getUser()->getUsername());
            $entity->getWniosek()->setLockedAt(new \Datetime());
            $em->flush();
        }
        //die(($editor->getId()).".");
        $viewer = $em->getRepository('ParpMainBundle:WniosekViewer')->findOneBy(array(
            'samaccountname' => $zastepstwa, //$this->getUser()->getUsername(),
            'wniosek' => $entity->getWniosek()
            )
        );
        //|| $onlyEditors
/*
        if ((!$editor ) && (!$viewer)) {
            
            
            return false;
        }
*/
        
        return ['viewer' => $viewer, 'editor' => $editor, 'editorsBezZastepstw' => $editorsBezZastepstw];
    }
    
    protected function setWniosekStatus($wniosek, $statusName, $rejected, $oldStatus = null){
        if ($this->debug) echo "<br>setWniosekStatus ".$statusName."<br>";
        
        $zastepstwo = $this->sprawdzCzyDzialaZastepstwo($wniosek);
        if($zastepstwo != null){
            //var_dump($zastepstwo); 
            //die('Mam zastepstwo');        
        }
        
        $em = $this->getDoctrine()->getManager();
        $status = $em->getRepository('ParpMainBundle:WniosekStatus')->findOneByNazwaSystemowa($statusName);
        $wniosek->getWniosek()->setStatus($status);
        $wniosek->getWniosek()->setLockedBy(null);
        $wniosek->getWniosek()->setLockedAt(null);
        $viewers = array();
        $editors = array();
        //die($statusName);
        $vs = explode(",",$status->getViewers());
        foreach($vs as $v){
            $this->addViewersEditors($wniosek->getWniosek(), $viewers, $v);
        }
        
        $czyMaGrupyAD = false;
                
        
        if($statusName == "07_ROZPATRZONY_POZYTYWNIE_O_ZASOB" && $oldStatus != null && $czyMaGrupyAD){
            //jak ma grupy AD do opublikowania to zostawiamy edytorow tych co byli
            $os = $em->getRepository('ParpMainBundle:WniosekStatus')->findOneByNazwaSystemowa($oldStatus);
            $es = explode(",", $os->getEditors());
        }else{
            $es = explode(",", $status->getEditors());
        }
        foreach($es as $e){
            $this->addViewersEditors($wniosek->getWniosek(), $editors, $e);
            //print_r($editors);
        }
        
        
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
        $sh->setZastepstwo($zastepstwo);
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
    
    protected function sprawdzCzyDzialaZastepstwo($wniosek)
    {        
        $ret = $this->checkAccess($wniosek);
        //var_dump($ret);die();
        if($wniosek->getId() && $ret['editorsBezZastepstw'] == null){
            //dziala zastepstwo, szukamy ktore
            $zastepstwa = $this->getDoctrine()->getRepository('ParpMainBundle:Zastepstwo')->znajdzZastepstwa($this->getUser()->getUsername());
            foreach($zastepstwa as $z){
                if($z->getKogoZastepuje() == ($ret['editor'] ? $ret['editor']->getSamaccountname() : "______NIE ZADZIALA______")){
                    //var_dump($z); die();
                    return $z;
                }
            }
        }else{
            return null;
        }
    }
    
    /**
     * Finds and displays a WniosekUtworzenieZasobu entity.
     *
     * @Route("/{id}/{isAccepted}/accept_reject/{publishForReal}", name="wniosekutworzeniezasobu_accept_reject", defaults={"publishForReal" : false})
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function acceptRejectAction(Request $request, $id, $isAccepted, $publishForReal = false)
    {
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();

        $wniosek = $em->getRepository('ParpMainBundle:WniosekUtworzenieZasobu')->find($id);
        //print_r($uzs); die();
        if (!$wniosek) {
            throw $this->createNotFoundException('Unable to find WniosekUtworzenieZasobu entity.');
        }
        if($request->isMethod('POST')){
            $txt = $request->get('powodZwrotu');
            $wniosek->setPowodZwrotu($txt);
            
            $kom = new \Parp\MainBundle\Entity\Komentarz();
            $kom->setObiekt('WniosekUtworzenieZasobu');
            $kom->setObiektId($id);
            $kom->setTytul("Wniosek odbito z powodu:");
            $kom->setOpis($txt);
            $kom->setSamaccountname($this->getUser()->getUsername());
            $kom->setCreatedAt(new \Datetime());
            $em->persist($kom);
            
        }else{
            $wniosek->setPowodZwrotu("");
        }
        
        $status = $wniosek->getWniosek()->getStatus()->getNazwaSystemowa();
        if($isAccepted == "unblock"){
            $wniosek->getWniosek()->setLockedBy(null);
            $wniosek->getWniosek()->setLockedAt(null);
        }
        elseif($isAccepted == "reject"){
            //przenosi do status 8
            $this->setWniosekStatus($wniosek, "08_ROZPATRZONY_NEGATYWNIE_O_ZASOB", true);
        }
        elseif($isAccepted == "publish"){
            //przenosi do status 11

            die('Blad 564654 . To nie powinno miec miejsca');
            $em->flush();
            // return new Response(""), if you used NullOutput()
            return $this->render('ParpMainBundle:WniosekUtworzenieZasobu:publish.html.twig', array('wniosek' => $wniosek, 'showonly' => $showonly, 'content' => $converter->convert($content)));
            
        }else{
            switch($status){
                case "00_TWORZONY_O_ZASOB":
                    switch($isAccepted){
                        case "accept":
                            $this->get('wniosekNumer')->nadajNumer($wniosek, "wniosekOUtworzenieZasobu");
                            //klonuje wniosek na male i ustawia im statusy:
                            
                            $this->setWniosekStatus($wniosek, "02_EDYCJA_WLASCICIEL_O_ZASOB", false);
                            
                            //$em->remove($wniosek);
                            if ($this->debug) die('<br>wszystko poszlo ok');
                            break;
                        case "return":
                            //nie powinno miec miejsca
                            die('blad 5034 nie powinno miec miejsca');
                            break;
                    }
                    break;
                case "01_EDYCJA_WNIOSKODAWCA_O_ZASOB":
                    switch($isAccepted){
                        case "accept":
                            //przenosi do status 2
                            $this->setWniosekStatus($wniosek, "02_EDYCJA_WLASCICIEL_O_ZASOB", false);
                        
                            break;
                        case "return":
                            //przenosi do status 1
                            die('blad 45 nie powinno miec miejsca');
                            break;
                    }
                    break;
                case "02_EDYCJA_WLASCICIEL_O_ZASOB":
                    switch($isAccepted){
                        case "accept":
                            if($wniosek->getWniosekDomenowy()){
                                //przenosi do status 021
                                $this->setWniosekStatus($wniosek, "021_EDYCJA_NADZORCA_DOMEN", false);
                            }else{
                                //przenosi do status 3
                                $this->setWniosekStatus($wniosek, "03_EDYCJA_PARP_ADMIN_REJESTRU_ZASOBOW", false);
                            }
                            
                            
                            break;
                        case "return":
                            $this->setWniosekStatus($wniosek, ("01_EDYCJA_WNIOSKODAWCA_O_ZASOB"), true);
                            break;
                    }
                    break;
                case "021_EDYCJA_NADZORCA_DOMEN":
                    switch($isAccepted){
                        case "accept":                            
                            $this->setWniosekStatus($wniosek, "03_EDYCJA_PARP_ADMIN_REJESTRU_ZASOBOW", false);                            
                            break;
                        case "return":
                            $this->setWniosekStatus($wniosek, ("02_EDYCJA_WLASCICIEL_O_ZASOB"), true);
                            break;
                    }
                    break;
                case "03_EDYCJA_PARP_ADMIN_REJESTRU_ZASOBOW":
                    switch($isAccepted){
                        case "acceptAndPublish":
                            $this->setWniosekStatus($wniosek, "07_ROZPATRZONY_POZYTYWNIE_O_ZASOB", false, $status);
                            break;
                        case "accept":
                            $this->setWniosekStatus($wniosek, "07_ROZPATRZONY_POZYTYWNIE_O_ZASOB", false);
                            break;
                        case "moveToAdmin":
                            $this->setWniosekStatus($wniosek, "04_EDYCJA_ADMINISTRATOR_O_ZASOB", false);
                            break;
                        case "moveToAdminTechniczny":
                            $this->setWniosekStatus($wniosek, "05_EDYCJA_TECHNICZNY_O_ZASOB", false);
                            break;
                        case "return":
                            $this->setWniosekStatus($wniosek, "02_EDYCJA_WLASCICIEL_O_ZASOB", true);
                            break;
                    }
                case "04_EDYCJA_ADMINISTRATOR_O_ZASOB":
                    switch($isAccepted){
                        case "moveToAdminRejestru":
                            $this->setWniosekStatus($wniosek, "03_EDYCJA_PARP_ADMIN_REJESTRU_ZASOBOW", false);
                            break;
                        case "moveToAdminTechniczny":
                            $this->setWniosekStatus($wniosek, "05_EDYCJA_TECHNICZNY_O_ZASOB", false);
                            break;
                    }
                    break;
                case "05_EDYCJA_TECHNICZNY_O_ZASOB":
                    switch($isAccepted){
                        case "moveToAdminRejestru":
                            $this->setWniosekStatus($wniosek, "03_EDYCJA_PARP_ADMIN_REJESTRU_ZASOBOW", false);
                            break;
                        case "moveToAdmin":
                            $this->setWniosekStatus($wniosek, "04_EDYCJA_ADMINISTRATOR_O_ZASOB", false);
                            break;
                    }
                    break;
            }
            
            if($isAccepted == "acceptAndPublish"){
                $this->setWniosekStatus($wniosek, "11_OPUBLIKOWANY_O_ZASOB", false);
                switch($wniosek->getTyp()){
                    case "nowy":
                        $wniosek->getZasob()->setPublished(true);
                        break;
                    case "zmiana":
                        //powinien wprowadzic zmiany!!!
                        
                        $delta = $this->obliczZmienionePola($wniosek);
                        //var_dump($delta);
                        foreach($delta as $k => $v){
                            $getter = "set".ucfirst($k);
                            $wniosek->getZmienianyZasob()->{$getter}($v);
                        }
                        
                        $wniosek->getZmienianyZasob()->setPublished(true);
                        $wniosek->getZasob()->setPublished(false);
                
                        //die('a');
                        break;
                    case "kasowanie":
                        $wniosek->getZmienianyZasob()->setPublished(false);
                        //$wniosek->getZasob()->setPublished(false);
                        break;        
                }
                
                
                
                
                //die('tu publikowac choc nie bardzo wiem co ?? moze jednak zadanie 1!!!');
            }
        }
        $em->flush();

        
        if($isAccepted == "unblock"){
            return $this->redirect($this->generateUrl('wniosekutworzeniezasobu', array(
            )));
        }elseif($wniosek->getWniosek()->getStatus()->getNazwaSystemowa() == "00_TWORZONY_O_ZASOB"){
            return $this->redirect($this->generateUrl('wniosekutworzeniezasobu', array(
            )));
            
        }else{
            return $this->redirect($this->generateUrl('wniosekutworzeniezasobu_show', array(
                'id' => $id
            )));
        }
    }
    
    protected function addViewersEditors($wniosek, &$where, $who){
        if ($this->debug) echo "<br>addViewersEditors ".$who."<br>";
        if($wniosek->getWniosekUtworzenieZasobu()->getTyp() == "nowy"){
            $zasob = $wniosek->getWniosekUtworzenieZasobu()->getZasob();
        }else{
            $zasob = $wniosek->getWniosekUtworzenieZasobu()->getZmienianyZasob();
            //print_r($zasob); die();
        }
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();
        switch($who){
            case "wnioskodawca":
                //
                $where[$wniosek->getCreatedBy()] = $wniosek->getCreatedBy();
                if ($this->debug) echo "<br>added ".$wniosek->getCreatedBy()."<br>";
                break;
            case "wlasciciel":
                //
                $grupa = explode(",", $zasob->getWlascicielZasobu());
                if($wniosek->getWniosekUtworzenieZasobu()->getTyp() == "kasowanie"){
                    
                }else{
                    
                }
                foreach($grupa as $g){
                    $mancn = str_replace("CN=", "", substr($g, 0, stripos($g, ',')));
                    $g = trim($g);
                    //$g = $this->get('renameService')->fixImieNazwisko($g);
                    //$g = $this->get('renameService')->fixImieNazwisko($g);
                    $ADManager = $ldap->getUserFromAD($g);
                    if ($this->debug) echo "<br>szuka wlasciciela  ".$g."<br>";
                    if(count($ADManager) > 0){
                        if ($this->debug) echo "<br>added ".$ADManager[0]['name']."<br>";
                        $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                        break;
                    }else{
                        //throw $this->createNotFoundException('Nie moge znalezc wlasciciel zasobu w AD : '.$g);
                        die ("!!!!!!!!!!blad 111 nie moge znalezc usera ".$g);
                    }
                    //echo "<br>dodaje wlasciciela ".$g;
                    //print_r($where);
                }
                break;
            case "nadzorcaDomen":
                //
                $em = $this->getDoctrine()->getManager();
                $role = $em->getRepository('ParpMainBundle:AclRole')->findOneByName("PARP_NADZORCA_DOMEN");
                $users = $em->getRepository('ParpMainBundle:AclUserRole')->findByRole($role);
                foreach($users as $u){
                    $where[$u->getSamaccountname()] = $u->getSamaccountname(); 
                    if ($this->debug) echo "<br>added ".$u->getSamaccountname()."<br>";
                }
                break;
            case "administratorZasobow":
                //
                $em = $this->getDoctrine()->getManager();
                $role = $em->getRepository('ParpMainBundle:AclRole')->findOneByName("PARP_ADMIN_REJESTRU_ZASOBOW");
                $users = $em->getRepository('ParpMainBundle:AclUserRole')->findByRole($role);
                foreach($users as $u){
                    $where[$u->getSamaccountname()] = $u->getSamaccountname(); 
                    if ($this->debug) echo "<br>added ".$u->getSamaccountname()."<br>";
                }
                break;
            case "administrator":
                //
                $grupa = explode(",", $zasob->getAdministratorZasobu());
                foreach($grupa as $g){
                    $mancn = str_replace("CN=", "", substr($g, 0, stripos($g, ',')));
                    $g = trim($g);
                    //$g = $this->get('renameService')->fixImieNazwisko($g);
                    $ADManager = $ldap->getUserFromAD($g);
                    if(count($ADManager) > 0){
                        if ($this->debug) echo "<br>added ".$ADManager[0]['name']."<br>";
                        $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                    }
                    else{
                        throw $this->createNotFoundException('Nie moge znalezc administrator zasobu w AD : '.$g);
                    }
                }
                break;
            case "techniczny":
                //
                $grupa = explode(",", $zasob->getAdministratorTechnicznyZasobu());
                foreach($grupa as $g){
                    //$mancn = str_replace("CN=", "", substr($g, 0, stripos($g, ',')));
                    //$g = $this->get('renameService')->fixImieNazwisko($g);
                    $g = trim($g);
                    $ADManager = $ldap->getUserFromAD($g);
                    $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                    if ($this->debug) echo "<br>added ".$ADManager[0]['name']."<br>";
                }
                break;
        }
    }
    
}
