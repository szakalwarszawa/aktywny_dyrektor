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

use Parp\MainBundle\Entity\GrupyUprawnien;
use Parp\MainBundle\Form\GrupyUprawnienType;

/**
 * GrupyUprawnien controller.
 *
 * @Route("/grupyuprawnien")
 */
class GrupyUprawnienController extends Controller
{

    /**
     * Lists all GrupyUprawnien entities.
     *
     * @Route("/index", name="grupyuprawnien")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:GrupyUprawnien')->findAll();
    
        $source = new Entity('ParpMainBundle:GrupyUprawnien');
    
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'grupyuprawnien_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'grupyuprawnien_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new GrupyUprawnien entity.
     *
     * @Route("/", name="grupyuprawnien_create")
     * @Method("POST")
     * @Template("ParpMainBundle:GrupyUprawnien:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new GrupyUprawnien();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'GrupyUprawnien został utworzony.');
                return $this->redirect($this->generateUrl('grupyuprawnien'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a GrupyUprawnien entity.
     *
     * @param GrupyUprawnien $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(GrupyUprawnien $entity)
    {
        $form = $this->createForm(new GrupyUprawnienType(), $entity, array(
            'action' => $this->generateUrl('grupyuprawnien_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz GrupyUprawnien', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new GrupyUprawnien entity.
     *
     * @Route("/new", name="grupyuprawnien_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new GrupyUprawnien();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a GrupyUprawnien entity.
     *
     * @Route("/{id}", name="grupyuprawnien_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:GrupyUprawnien')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GrupyUprawnien entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing GrupyUprawnien entity.
     *
     * @Route("/{id}/edit", name="grupyuprawnien_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:GrupyUprawnien')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GrupyUprawnien entity.');
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
    * Creates a form to edit a GrupyUprawnien entity.
    *
    * @param GrupyUprawnien $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(GrupyUprawnien $entity)
    {
        $form = $this->createForm(new GrupyUprawnienType(), $entity, array(
            'action' => $this->generateUrl('grupyuprawnien_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing GrupyUprawnien entity.
     *
     * @Route("/{id}", name="grupyuprawnien_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:GrupyUprawnien:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:GrupyUprawnien')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GrupyUprawnien entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->setUprawnieniaHistoriaZmian();
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('grupyuprawnien_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a GrupyUprawnien entity.
     *
     * @Route("/{id}", name="grupyuprawnien_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:GrupyUprawnien')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find GrupyUprawnien entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('grupyuprawnien'));
    }

    /**
     * Creates a form to delete a GrupyUprawnien entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('grupyuprawnien_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj GrupyUprawnien','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
