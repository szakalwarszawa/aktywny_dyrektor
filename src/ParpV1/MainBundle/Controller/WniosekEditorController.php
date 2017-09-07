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

use Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowEditor;
use Parp\MainBundle\Form\WniosekNadanieOdebranieZasobowEditorType;

/**
 * WniosekNadanieOdebranieZasobowEditor controller.
 *
 * @Route("/wnioseknadanieodebraniezasoboweditor")
 */
class WniosekEditorController extends Controller
{

    /**
     * Lists all WniosekNadanieOdebranieZasobowEditor entities.
     *
     * @Route("/index", name="wnioseknadanieodebraniezasoboweditor")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobowEditor')->findAll();
    
        $source = new Entity('ParpMainBundle:WniosekNadanieOdebranieZasobowEditor');
    
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'wnioseknadanieodebraniezasoboweditor_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'wnioseknadanieodebraniezasoboweditor_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new WniosekNadanieOdebranieZasobowEditor entity.
     *
     * @Route("/", name="wnioseknadanieodebraniezasoboweditor_create")
     * @Method("POST")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobowEditor:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new WniosekNadanieOdebranieZasobowEditor();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'WniosekNadanieOdebranieZasobowEditor został utworzony.');
                return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasoboweditor'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a WniosekNadanieOdebranieZasobowEditor entity.
     *
     * @param WniosekNadanieOdebranieZasobowEditor $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(WniosekNadanieOdebranieZasobowEditor $entity)
    {
        $form = $this->createForm(new WniosekNadanieOdebranieZasobowEditorType(), $entity, array(
            'action' => $this->generateUrl('wnioseknadanieodebraniezasoboweditor_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz WniosekNadanieOdebranieZasobowEditor', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new WniosekNadanieOdebranieZasobowEditor entity.
     *
     * @Route("/new", name="wnioseknadanieodebraniezasoboweditor_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new WniosekNadanieOdebranieZasobowEditor();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a WniosekNadanieOdebranieZasobowEditor entity.
     *
     * @Route("/{id}", name="wnioseknadanieodebraniezasoboweditor_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobowEditor')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobowEditor entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing WniosekNadanieOdebranieZasobowEditor entity.
     *
     * @Route("/{id}/edit", name="wnioseknadanieodebraniezasoboweditor_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobowEditor')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobowEditor entity.');
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
    * Creates a form to edit a WniosekNadanieOdebranieZasobowEditor entity.
    *
    * @param WniosekNadanieOdebranieZasobowEditor $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(WniosekNadanieOdebranieZasobowEditor $entity)
    {
        $form = $this->createForm(new WniosekNadanieOdebranieZasobowEditorType(), $entity, array(
            'action' => $this->generateUrl('wnioseknadanieodebraniezasoboweditor_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing WniosekNadanieOdebranieZasobowEditor entity.
     *
     * @Route("/{id}", name="wnioseknadanieodebraniezasoboweditor_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobowEditor:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobowEditor')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobowEditor entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasoboweditor_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a WniosekNadanieOdebranieZasobowEditor entity.
     *
     * @Route("/{id}", name="wnioseknadanieodebraniezasoboweditor_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:WniosekNadanieOdebranieZasobowEditor')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobowEditor entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasoboweditor'));
    }

    /**
     * Creates a form to delete a WniosekNadanieOdebranieZasobowEditor entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('wnioseknadanieodebraniezasoboweditor_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj WniosekNadanieOdebranieZasobowEditor','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
