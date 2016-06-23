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

use Parp\SoapBundle\Entity\ADOrganizationalUnit;
use Parp\SoapBundle\Form\ADOrganizationalUnitType;

/**
 * ADOrganizationalUnit controller.
 *
 * @Route("/adorganizationalunit")
 */
class ADOrganizationalUnitController extends Controller
{

    /**
     * Lists all ADOrganizationalUnit entities.
     *
     * @Route("/index", name="adorganizationalunit")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpSoapBundle:ADOrganizationalUnit')->findAll();
    
        $source = new Entity('ParpSoapBundle:ADOrganizationalUnit');
    
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'adorganizationalunit_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'adorganizationalunit_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new ADOrganizationalUnit entity.
     *
     * @Route("/", name="adorganizationalunit_create")
     * @Method("POST")
     * @Template("ParpSoapBundle:ADOrganizationalUnit:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new ADOrganizationalUnit();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'ADOrganizationalUnit został utworzony.');
                return $this->redirect($this->generateUrl('adorganizationalunit'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a ADOrganizationalUnit entity.
     *
     * @param ADOrganizationalUnit $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ADOrganizationalUnit $entity)
    {
        $form = $this->createForm(new ADOrganizationalUnitType(), $entity, array(
            'action' => $this->generateUrl('adorganizationalunit_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz ADOrganizationalUnit', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new ADOrganizationalUnit entity.
     *
     * @Route("/new", name="adorganizationalunit_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new ADOrganizationalUnit();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a ADOrganizationalUnit entity.
     *
     * @Route("/{id}", name="adorganizationalunit_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpSoapBundle:ADOrganizationalUnit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ADOrganizationalUnit entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing ADOrganizationalUnit entity.
     *
     * @Route("/{id}/edit", name="adorganizationalunit_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpSoapBundle:ADOrganizationalUnit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ADOrganizationalUnit entity.');
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
    * Creates a form to edit a ADOrganizationalUnit entity.
    *
    * @param ADOrganizationalUnit $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(ADOrganizationalUnit $entity)
    {
        $form = $this->createForm(new ADOrganizationalUnitType(), $entity, array(
            'action' => $this->generateUrl('adorganizationalunit_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing ADOrganizationalUnit entity.
     *
     * @Route("/{id}", name="adorganizationalunit_update")
     * @Method("PUT")
     * @Template("ParpSoapBundle:ADOrganizationalUnit:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpSoapBundle:ADOrganizationalUnit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ADOrganizationalUnit entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('adorganizationalunit_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a ADOrganizationalUnit entity.
     *
     * @Route("/{id}", name="adorganizationalunit_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpSoapBundle:ADOrganizationalUnit')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ADOrganizationalUnit entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('adorganizationalunit'));
    }

    /**
     * Creates a form to delete a ADOrganizationalUnit entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('adorganizationalunit_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj ADOrganizationalUnit','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
