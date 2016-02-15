<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Parp\MainBundle\Entity\Zasoby;
use Parp\MainBundle\Form\ZasobyType;


/**
 * Zasoby controller.
 *
 * @Route("/zasoby")
 */
class ZasobyController extends Controller
{

    /**
     * Lists all Zasoby entities.
     *
     * @Route("/", name="zasoby")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        //$entities = $em->getRepository('ParpMainBundle:Zasoby')->findAll();
        $entities = $em->getRepository('ParpMainBundle:Zasoby')->findBy(array(), array('nazwa'=>'asc'));

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Zasoby entity.
     *
     * @Route("/", name="zasoby_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Zasoby:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Zasoby();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            //return $this->redirect($this->generateUrl('zasoby_show', array('id' => $entity->getId())));
            
            $this->get('session')->getFlashBag()->set('warning', 'Zasób został utworzony.');
            return $this->redirect($this->generateUrl('zasoby'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Zasoby entity.
     *
     * @param Zasoby $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Zasoby $entity)
    {
        $form = $this->createForm(new ZasobyType(), $entity, array(
            'action' => $this->generateUrl('zasoby_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Zasoby entity.
     *
     * @Route("/new", name="zasoby_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Zasoby();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Zasoby entity.
     *
     * @Route("/{id}", name="zasoby_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Zasoby entity.
     *
     * @Route("/{id}/edit", name="zasoby_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
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
    * Creates a form to edit a Zasoby entity.
    *
    * @param Zasoby $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Zasoby $entity)
    {
        $form = $this->createForm(new ZasobyType(), $entity, array(
            'action' => $this->generateUrl('zasoby_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array(
            'label' => 'Update',
            'attr' => array('class' => 'btn btn-primary' )
        ));

        return $form;
    }
    /**
     * Edits an existing Zasoby entity.
     *
     * @Route("/{id}", name="zasoby_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Zasoby:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('zasoby_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'id' => $id,
        );
    }
    /**
     * Deletes a Zasoby entity.
     *
     * @Route("/{id}", name="zasoby_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Zasoby entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('zasoby'));
    }

    /**
     * Creates a form to delete a Zasoby entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('zasoby_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array(
                'label' => 'Delete',
                'attr' => array('class' => 'btn btn-danger' )
            ))
            ->getForm()
        ;
    }
}
