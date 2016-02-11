<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Parp\MainBundle\Entity\Engagement;
use Parp\MainBundle\Form\EngagementType;

/**
 * Engagement controller.
 *
 * @Route("/engagement")
 */
class EngagementController extends Controller
{

    /**
     * Lists all Engagement entities.
     *
     * @Route("/", name="engagement")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ParpMainBundle:Engagement')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Creates a new Engagement entity.
     *
     * @Route("/", name="engagement_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Engagement:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Engagement();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            //return $this->redirect($this->generateUrl('engagement_show', array('id' => $entity->getId())));
            $this->get('session')->getFlashBag()->set('warning', 'Zaangażowanie zostało utworzone.');
            return $this->redirect($this->generateUrl('engagement'));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Engagement entity.
     *
     * @param Engagement $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Engagement $entity)
    {
        $form = $this->createForm(new EngagementType(), $entity, array(
            'action' => $this->generateUrl('engagement_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Engagement entity.
     *
     * @Route("/new", name="engagement_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Engagement();
        $form = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Engagement entity.
     *
     * @Route("/{id}", name="engagement_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Engagement')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Engagement entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Engagement entity.
     *
     * @Route("/{id}/edit", name="engagement_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Engagement')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Engagement entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'id' => $id,
        );
    }

    /**
     * Creates a form to edit a Engagement entity.
     *
     * @param Engagement $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Engagement $entity)
    {
        $form = $this->createForm(new EngagementType(), $entity, array(
            'action' => $this->generateUrl('engagement_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Engagement entity.
     *
     * @Route("/{id}", name="engagement_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Engagement:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Engagement')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Engagement entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('engagement_edit', array('id' => $id)));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'id' => $id,
        );
    }

    /**
     * Deletes a Engagement entity.
     *
     * @Route("/{id}", name="engagement_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Engagement')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Engagement entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('engagement'));
    }

    /**
     * Creates a form to delete a Engagement entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
                        ->setAction($this->generateUrl('engagement_delete', array('id' => $id)))
                        ->setMethod('DELETE')
                        ->add('submit', 'submit', array('label' => 'Delete'))
                        ->getForm()
        ;
    }

}
