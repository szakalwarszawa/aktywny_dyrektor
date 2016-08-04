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
use Parp\MainBundle\Grid\ParpExcelExport;
use Parp\MainBundle\Entity\Zasoby;
use Parp\MainBundle\Form\ZasobyType;

/**
 * Zasoby controller.
 *
 * @Route("/zasoby")
 */
class ZasobyController extends Controller
{

    /**
     * Lists all Zasoby entities.
     *
     * @Route("/index/{aktywne}", name="zasoby", defaults={"aktywne" : true})
     * @Template()
     */
    public function indexAction($aktywne = true)
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:Zasoby')->findAll();
    
        $source = new Entity('ParpMainBundle:Zasoby');
        $tableAlias = $source->getTableAlias();
        $source->manipulateQuery(
            function ($query) use ($tableAlias, $aktywne)
            {
                $query->andWhere($tableAlias.'.published = '.($aktywne ? "1" : "0"));
            });
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'zasoby_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'zasoby_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse("ParpMainBundle:Zasoby:index.html.twig", array('aktywne' => $aktywne));
    }
    /**
     * Creates a new Zasoby entity.
     *
     * @Route("/", name="zasoby_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Zasoby:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Zasoby();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'Zasoby został utworzony.');
                return $this->redirect($this->generateUrl('zasoby'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Zasoby entity.
     *
     * @param Zasoby $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Zasoby $entity)
    {
        $form = $this->createForm(new ZasobyType($this), $entity, array(
            'action' => $this->generateUrl('zasoby_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz Zasoby', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Zasoby entity.
     *
     * @Route("/new", name="zasoby_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Zasoby();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Zasoby entity.
     *
     * @Route("/{id}", name="zasoby_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Zasoby entity.
     *
     * @Route("/{id}/edit", name="zasoby_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
        }
        $grupy = explode(",", $entity->getGrupyAD());
        $grupyAd = array();
        foreach($grupy as $g){
            if($g != ""){
                $grupyAd[$g] = array(
                    'exists' => $this->get('ldap_service')->checkGroupExistsFromAD($g),
                    'members' => $this->get('ldap_service')->getMembersOfGroupFromAD($g)
                );
            }
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        $em = $this->getDoctrine()->getManager();
        $uzs = $em->getRepository('Parp\MainBundle\Entity\UserZasoby')->findUsersByZasobId($id);
        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'users' => $uzs,
            'grupy' => $grupy,
            'grupyAd' => $grupyAd
        );
    }

    private function getUsersFromAD(){
        $ldap = $this->get('ldap_service');
        $aduser = $ldap->getUserFromAD($this->getUser()->getUsername());
        $widzi_wszystkich = in_array("PARP_WNIOSEK_WIDZI_WSZYSTKICH", $this->getUser()->getRoles()) || in_array("PARP_ADMIN", $this->getUser()->getRoles());
        
        $ADUsers = $ldap->getAllFromAD();
        $users = array();
        foreach($ADUsers as $u){
            //albo ma role ze widzi wszystkich albo widzi tylko swoj departament
            if($widzi_wszystkich || mb_strtolower(trim($aduser[0]['department'])) == mb_strtolower(trim($u['department']))){
                $users[$u['samaccountname']] = $u['name'];
            }
        }
        return $users;
    }
    /**
    * Creates a form to edit a Zasoby entity.
    *
    * @param Zasoby $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Zasoby $entity)
    {
        $form = $this->createForm(new ZasobyType($this), $entity, array(
            'action' => $this->generateUrl('zasoby_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    
    /**
     * Edits an existing Zasoby entity.
     *
     * @Route("/{id}", name="zasoby_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Zasoby:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('zasoby_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Zasoby entity.
     *
     * @Route("/{id}", name="zasoby_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Zasoby entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('zasoby'));
    }

    /**
     * Creates a form to delete a Zasoby entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('zasoby_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Zasoby','attr' => array('class' => 'btn btn-danger' )))
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
}
