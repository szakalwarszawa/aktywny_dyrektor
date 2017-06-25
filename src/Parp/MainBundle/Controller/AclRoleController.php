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

use Parp\MainBundle\Entity\AclRole;
use Parp\MainBundle\Form\AclRoleType;

/**
 * AclRole controller.
 *
 * @Route("/aclrole")
 */
class AclRoleController extends Controller
{

    /**
     * Lists all AclRole entities.
     *
     * @Route("/index", name="aclrole")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:AclRole')->findAll();
    
        $source = new Entity('ParpMainBundle:AclRole');
    
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'aclrole_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'aclrole_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new AclRole entity.
     *
     * @Route("/", name="aclrole_create")
     * @Method("POST")
     * @Template("ParpMainBundle:AclRole:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new AclRole();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->set('warning', 'AclRole został utworzony.');
                return $this->redirect($this->generateUrl('aclrole'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a AclRole entity.
     *
     * @param AclRole $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AclRole $entity)
    {
        $form = $this->createForm(new AclRoleType($this->getUsersFromAD(), $this->getDoctrine()->getManager()), $entity, array(
            'action' => $this->generateUrl('aclrole_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz AclRole', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new AclRole entity.
     *
     * @Route("/new", name="aclrole_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new AclRole();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a AclRole entity.
     *
     * @Route("/{id}", name="aclrole_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:AclRole')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AclRole entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing AclRole entity.
     *
     * @Route("/{id}/edit", name="aclrole_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:AclRole')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AclRole entity.');
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
    * Creates a form to edit a AclRole entity.
    *
    * @param AclRole $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(AclRole $entity)
    {
        $form = $this->createForm(new AclRoleType($this->getUsersFromAD(), $this->getDoctrine()->getManager()), $entity, array(
            'action' => $this->generateUrl('aclrole_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing AclRole entity.
     *
     * @Route("/{id}", name="aclrole_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:AclRole:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:AclRole')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AclRole entity.');
        }
        $originalUsers = clone $entity->getUsers();
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            foreach ($originalUsers as $user) {
                $em->remove($user);
            }
            //var_dump(count($editForm->getData()->getUsers())); die();
            foreach ($entity->getUsers() as $user) {
                $em->persist($user);
            }
            $em->flush();
            //die();
            $this->get('session')->getFlashBag()->set('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('aclrole_edit', array('id' => $id)));
        } else {
            die('a');
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a AclRole entity.
     *
     * @Route("/{id}", name="aclrole_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:AclRole')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find AclRole entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('aclrole'));
    }

    /**
     * Creates a form to delete a AclRole entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('aclrole_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj AclRole','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
    
    private function getUsersFromAD()
    {
        $ldap = $this->get('ldap_service');
        $aduser = $ldap->getUserFromAD($this->getUser()->getUsername());
        $widzi_wszystkich = true;//in_array("PARP_WNIOSEK_WIDZI_WSZYSTKICH", $this->getUser()->getRoles()) || in_array("PARP_ADMIN", $this->getUser()->getRoles());
        
        $ADUsers = $ldap->getAllFromAD();
        $users = array();
        foreach ($ADUsers as $u) {
            //albo ma role ze widzi wszystkich albo widzi tylko swoj departament
            if ($widzi_wszystkich || mb_strtolower(trim($aduser[0]['department'])) == mb_strtolower(trim($u['department']))) {
                $users[$u['samaccountname']] = $u['name'];
            }
        }
        return $users;
    }
}
