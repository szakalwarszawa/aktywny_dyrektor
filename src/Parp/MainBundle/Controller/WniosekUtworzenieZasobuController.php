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

/**
 * WniosekUtworzenieZasobu controller.
 *
 * @Route("/wniosekutworzeniezasobu")
 */
class WniosekUtworzenieZasobuController extends Controller
{

    /**
     * Lists all WniosekUtworzenieZasobu entities.
     *
     * @Route("/index", name="wniosekutworzeniezasobu")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:WniosekUtworzenieZasobu')->findAll();
    
        $source = new Entity('ParpMainBundle:WniosekUtworzenieZasobu');
    
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'wniosekutworzeniezasobu_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'wniosekutworzeniezasobu_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new WniosekUtworzenieZasobu entity.
     *
     * @Route("/", name="wniosekutworzeniezasobu_create")
     * @Method("POST")
     * @Template("ParpMainBundle:WniosekUtworzenieZasobu:edit.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new WniosekUtworzenieZasobu();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'WniosekUtworzenieZasobu został utworzony.');
                return $this->redirect($this->generateUrl('wniosekutworzeniezasobu'));
        }

        return array(
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
    private function createCreateForm(WniosekUtworzenieZasobu $entity)
    {
        $form = $this->createForm(new WniosekUtworzenieZasobuType($this->getUsers()), $entity, array(
            'action' => $this->generateUrl('wniosekutworzeniezasobu_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz WniosekUtworzenieZasobu', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new WniosekUtworzenieZasobu entity.
     *
     * @Route("/new", name="wniosekutworzeniezasobu_new")
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekUtworzenieZasobu:edit.html.twig")
     */
    public function newAction()
    {
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($this->getUser()->getUsername());
        
        $status = $this->getDoctrine()->getManager()->getRepository('Parp\MainBundle\Entity\WniosekStatus')->findOneByNazwaSystemowa('00_TWORZONY');
        
        $entity = new WniosekUtworzenieZasobu();
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
        $entity->getZasob()->setKomorkaOrgazniacyjna($departament);
        $form   = $this->createCreateForm($entity);

        

        //echo "<pre>"; print_r($ADUser); die();

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'message' => ''
        );
        
    }

    /**
     * Finds and displays a WniosekUtworzenieZasobu entity.
     *
     * @Route("/{id}", name="wniosekutworzeniezasobu_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
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
     * Displays a form to edit an existing WniosekUtworzenieZasobu entity.
     *
     * @Route("/{id}/edit", name="wniosekutworzeniezasobu_edit")
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekUtworzenieZasobu:edit.html.twig")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekUtworzenieZasobu')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekUtworzenieZasobu entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a WniosekUtworzenieZasobu entity.
    *
    * @param WniosekUtworzenieZasobu $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(WniosekUtworzenieZasobu $entity)
    {
        $form = $this->createForm(new WniosekUtworzenieZasobuType($this->getUsers()), $entity, array(
            'action' => $this->generateUrl('wniosekutworzeniezasobu_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing WniosekUtworzenieZasobu entity.
     *
     * @Route("/{id}", name="wniosekutworzeniezasobu_update")
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

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('wniosekutworzeniezasobu_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a WniosekUtworzenieZasobu entity.
     *
     * @Route("/{id}", name="wniosekutworzeniezasobu_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:WniosekUtworzenieZasobu')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find WniosekUtworzenieZasobu entity.');
            }

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
            ->add('submit', 'submit', array('label' => 'Skasuj WniosekUtworzenieZasobu','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
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
}
