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

use Parp\MainBundle\Entity\Wniosek;
use Parp\MainBundle\Form\WniosekType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Wniosek controller.
 *
 * @Route("/wniosek")
 */
class WniosekController extends Controller
{

    /**
     * @Security("has_role('PARP_ADMIN')")
     *
     * @Route("/{id}/przekierujWniosek", name="wniosek_przekieruj")
     * @Method("GET")
     * @Template()
     */
    public function przekierujAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $wniosek = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

        if (!$wniosek) {
            throw $this->createNotFoundException('Unable to find Wniosek entity.');
        }

        return array(
            'wniosek'      => $wniosek
        );
    }



    /**
     * Lists all Wniosek entities.
     *
     * @Route("/index", name="wniosek")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:Wniosek')->findAll();
    
        $source = new Entity('ParpMainBundle:Wniosek');
    
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'wniosek_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'wniosek_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new Wniosek entity.
     *
     * @Route("/", name="wniosek_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Wniosek:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Wniosek();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'Wniosek został utworzony.');
                return $this->redirect($this->generateUrl('wniosek'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Wniosek entity.
     *
     * @param Wniosek $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Wniosek $entity)
    {
        $form = $this->createForm(new WniosekType(), $entity, array(
            'action' => $this->generateUrl('wniosek_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz Wniosek', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Wniosek entity.
     *
     * @Route("/new", name="wniosek_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Wniosek();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Wniosek entity.
     *
     * @Route("/{id}", name="wniosek_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Wniosek entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Wniosek entity.
     *
     * @Route("/{id}/edit", name="wniosek_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Wniosek entity.');
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
    * Creates a form to edit a Wniosek entity.
    *
    * @param Wniosek $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Wniosek $entity)
    {
        $form = $this->createForm(new WniosekType(), $entity, array(
            'action' => $this->generateUrl('wniosek_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing Wniosek entity.
     *
     * @Route("/{id}", name="wniosek_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Wniosek:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Wniosek entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('wniosek_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Wniosek entity.
     *
     * @Route("/{id}", name="wniosek_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Wniosek entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('wniosek'));
    }

    /**
     * Creates a form to delete a Wniosek entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('wniosek_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Wniosek','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
