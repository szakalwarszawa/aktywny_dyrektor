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

use Parp\MainBundle\Entity\Uprawnienia;
use Parp\MainBundle\Form\UprawnieniaType;

/**
 * Uprawnienia controller.
 *
 * @Route("/uprawnienia")
 */
class UprawnieniaController extends Controller
{

    /**
     * Lists all Uprawnienia entities.
     *
     * @Route("/index", name="uprawnienia")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:Uprawnienia')->findAll();
    
        $source = new Entity('ParpMainBundle:Uprawnienia');
    
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'uprawnienia_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'uprawnienia_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new Uprawnienia entity.
     *
     * @Route("/", name="uprawnienia_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Uprawnienia:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Uprawnienia();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'Uprawnienia został utworzony.');
                return $this->redirect($this->generateUrl('uprawnienia'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Uprawnienia entity.
     *
     * @param Uprawnienia $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Uprawnienia $entity)
    {
        $form = $this->createForm(new UprawnieniaType(), $entity, array(
            'action' => $this->generateUrl('uprawnienia_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz Uprawnienia', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Uprawnienia entity.
     *
     * @Route("/new", name="uprawnienia_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Uprawnienia();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Uprawnienia entity.
     *
     * @Route("/{id}", name="uprawnienia_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Uprawnienia')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Uprawnienia entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Uprawnienia entity.
     *
     * @Route("/{id}/edit", name="uprawnienia_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Uprawnienia')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Uprawnienia entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        
        $uzs = $em->getRepository('Parp\MainBundle\Entity\UserUprawnienia')->findUsersByUprawnienieId($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'users' => $uzs
        );
    }

    /**
    * Creates a form to edit a Uprawnienia entity.
    *
    * @param Uprawnienia $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Uprawnienia $entity)
    {
        $form = $this->createForm(new UprawnieniaType(), $entity, array(
            'action' => $this->generateUrl('uprawnienia_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing Uprawnienia entity.
     *
     * @Route("/{id}", name="uprawnienia_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Uprawnienia:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Uprawnienia')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Uprawnienia entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('uprawnienia_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Uprawnienia entity.
     *
     * @Route("/{id}", name="uprawnienia_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Uprawnienia')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Uprawnienia entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('uprawnienia'));
    }

    /**
     * Creates a form to delete a Uprawnienia entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('uprawnienia_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Uprawnienia','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
