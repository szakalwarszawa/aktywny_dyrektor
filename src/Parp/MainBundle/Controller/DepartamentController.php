<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Parp\MainBundle\Entity\Departament;
use Parp\MainBundle\Form\DepartamentType;

/**
 * Departament controller.
 *
 * @Route("/departament")
 */
class DepartamentController extends Controller
{

    /**
     * Lists all Departament entities.
     *
     * @Route("/", name="departament")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        //$entities = $em->getRepository('ParpMainBundle:Departament')->findAll();
        $entities = $em->getRepository('ParpMainBundle:Departament')->findBy(array(), array('name'=>'asc'));

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Departament entity.
     *
     * @Route("/", name="departament_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Departament:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Departament();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            //return $this->redirect($this->generateUrl('departament_show', array('id' => $entity->getId())));
            $this->get('session')->getFlashBag()->set('warning', 'Departament został dodany');
            return $this->redirect($this->generateUrl('departament'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Departament entity.
     *
     * @param Departament $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Departament $entity)
    {
        $form = $this->createForm(new DepartamentType(), $entity, array(
            'action' => $this->generateUrl('departament_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Departament entity.
     *
     * @Route("/new", name="departament_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Departament();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Departament entity.
     *
     * @Route("/{id}", name="departament_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Departament')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Departament entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Departament entity.
     *
     * @Route("/{id}/edit", name="departament_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Departament')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Departament entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'id' => $id
        );
    }

    /**
    * Creates a form to edit a Departament entity.
    *
    * @param Departament $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Departament $entity)
    {
        $form = $this->createForm(new DepartamentType(), $entity, array(
            'action' => $this->generateUrl('departament_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Departament entity.
     *
     * @Route("/{id}", name="departament_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Departament:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Departament')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Departament entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('departament_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'id' => $id,
        );
    }
    /**
     * Deletes a Departament entity.
     *
     * @Route("/{id}", name="departament_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Departament')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Departament entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('departament'));
    }

    /**
     * Creates a form to delete a Departament entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('departament_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
