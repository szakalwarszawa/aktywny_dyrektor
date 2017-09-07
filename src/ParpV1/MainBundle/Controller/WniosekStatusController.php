<?php

namespace ParpV1\MainBundle\Controller;

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

use Parp\MainBundle\Entity\WniosekStatus;
use Parp\MainBundle\Form\WniosekStatusType;

/**
 * WniosekStatus controller.
 *
 * @Route("/wniosekstatus")
 */
class WniosekStatusController extends Controller
{

    /**
     * Lists all WniosekStatus entities.
     *
     * @Route("/index", name="wniosekstatus")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:WniosekStatus')->findAll();
    
        $source = new Entity('ParpMainBundle:WniosekStatus');
    
        $tableAlias = $source->getTableAlias();
        //die($co);
        $source->manipulateQuery(
            function ($query) use ($tableAlias) {
                $query->addOrderBy($tableAlias . '.nazwaSystemowa', 'ASC');
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'wniosekstatus_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'wniosekstatus_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new WniosekStatus entity.
     *
     * @Route("/", name="wniosekstatus_create")
     * @Method("POST")
     * @Template("ParpMainBundle:WniosekStatus:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new WniosekStatus();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'WniosekStatus został utworzony.');
                return $this->redirect($this->generateUrl('wniosekstatus'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a WniosekStatus entity.
     *
     * @param WniosekStatus $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(WniosekStatus $entity)
    {
        $form = $this->createForm(new WniosekStatusType(), $entity, array(
            'action' => $this->generateUrl('wniosekstatus_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz Status', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new WniosekStatus entity.
     *
     * @Route("/new", name="wniosekstatus_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new WniosekStatus();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a WniosekStatus entity.
     *
     * @Route("/{id}", name="wniosekstatus_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekStatus')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekStatus entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing WniosekStatus entity.
     *
     * @Route("/{id}/edit", name="wniosekstatus_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekStatus')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekStatus entity.');
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
    * Creates a form to edit a WniosekStatus entity.
    *
    * @param WniosekStatus $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(WniosekStatus $entity)
    {
        $form = $this->createForm(new WniosekStatusType(), $entity, array(
            'action' => $this->generateUrl('wniosekstatus_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing WniosekStatus entity.
     *
     * @Route("/{id}", name="wniosekstatus_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:WniosekStatus:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekStatus')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekStatus entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('wniosekstatus_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a WniosekStatus entity.
     *
     * @Route("/{id}", name="wniosekstatus_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:WniosekStatus')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find WniosekStatus entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('wniosekstatus'));
    }

    /**
     * Creates a form to delete a WniosekStatus entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('wniosekstatus_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Status','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
