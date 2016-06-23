<?php

namespace Parp\SoapBundle\Controller;

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

use Parp\SoapBundle\Entity\ADUser;
use Parp\SoapBundle\Form\ADUserType;

/**
 * ADUser controller.
 *
 * @Route("/aduser")
 */
class ADUserController extends Controller
{

    /**
     * Lists all ADUser entities.
     *
     * @Route("/index", name="aduser")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpSoapBundle:ADUser')->findAll();
    
        $source = new Entity('ParpSoapBundle:ADUser');
    
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'aduser_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'aduser_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new ADUser entity.
     *
     * @Route("/", name="aduser_create")
     * @Method("POST")
     * @Template("ParpSoapBundle:ADUser:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new ADUser();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'ADUser został utworzony.');
                return $this->redirect($this->generateUrl('aduser'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a ADUser entity.
     *
     * @param ADUser $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ADUser $entity)
    {
        $form = $this->createForm(new ADUserType(), $entity, array(
            'action' => $this->generateUrl('aduser_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz ADUser', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new ADUser entity.
     *
     * @Route("/new", name="aduser_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new ADUser();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a ADUser entity.
     *
     * @Route("/{id}", name="aduser_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpSoapBundle:ADUser')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ADUser entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing ADUser entity.
     *
     * @Route("/{id}/edit", name="aduser_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpSoapBundle:ADUser')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ADUser entity.');
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
    * Creates a form to edit a ADUser entity.
    *
    * @param ADUser $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(ADUser $entity)
    {
        $form = $this->createForm(new ADUserType(), $entity, array(
            'action' => $this->generateUrl('aduser_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing ADUser entity.
     *
     * @Route("/{id}", name="aduser_update")
     * @Method("PUT")
     * @Template("ParpSoapBundle:ADUser:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpSoapBundle:ADUser')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ADUser entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('aduser_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a ADUser entity.
     *
     * @Route("/{id}", name="aduser_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpSoapBundle:ADUser')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ADUser entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('aduser'));
    }

    /**
     * Creates a form to delete a ADUser entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('aduser_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj ADUser','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
