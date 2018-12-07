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
use ParpV1\MainBundle\Entity\Zastepstwo;
use ParpV1\MainBundle\Form\ZastepstwoType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Zastepstwo controller.
 *
 * @Route("/zastepstwo")
 */
class ZastepstwoController extends Controller
{

    /**
     * Lists all Zastepstwo entities.
     *
     * @Route("/index", name="zastepstwo")
     * @Template()
     */
    public function indexAction()
    {
        $source = new Entity('ParpMainBundle:Zastepstwo');

        $grid = $this->get('grid');
        $grid->setSource($source);

        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);

        // Zdejmujemy filtr
        $grid->getColumn('akcje')
                ->setFilterable(false)
                ->setSafe(true);

        // Edycja zastępstwa
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'zastepstwo_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Usunięcie zastępstwa
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'zastepstwo_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');

        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));

        $grid->isReadyForRedirect();

        return $grid->getGridResponse();
    }
    /**
     * Creates a new Zastepstwo entity.
     *
     * @Route("/", name="zastepstwo_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $entity = new Zastepstwo();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entity);
            $entityManager->flush();

            $this->addFlash('warning', 'Zastepstwo zostało utworzone.');

            return $this->redirect($this->generateUrl('zastepstwo'));
        }

        return $this->render('ParpMainBundle:Zastepstwo:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Zastepstwo entity.
     *
     * @param Zastepstwo $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Zastepstwo $entity)
    {
        $form = $this->createForm(ZastepstwoType::class, $entity, array(
            'current_user' => $this->getUser(),
            'ad_users' => $this->getUsersFromAD(),
            'action' => $this->generateUrl('zastepstwo_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Utwórz Zastepstwo', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Zastepstwo entity.
     *
     * @Route("/new", name="zastepstwo_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Zastepstwo();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Zastepstwo entity.
     *
     * @Route("/{id}", name="zastepstwo_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zastepstwo')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zastepstwo entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Zastepstwo entity.
     *
     * @Route("/{id}/edit", name="zastepstwo_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository('ParpMainBundle:Zastepstwo')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zastepstwo entity.');
        }

        if (!in_array("PARP_ADMIN", $this->getUser()->getRoles()) && !in_array("PARP_ADMIN_ZASTEPSTW", $this->getUser()->getRoles()) && $entity->getKogoZastepuje() != $this->getUser()->getUsername()) {
            $this->addFlash('warning', 'Nie masz uprawnień do edycji nie swoich zastępstw.');
            return $this->redirect($this->generateUrl('zastepstwo'));
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
    * Creates a form to edit a Zastepstwo entity.
    *
    * @param Zastepstwo $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Zastepstwo $entity)
    {
        $form = $this->createForm(ZastepstwoType::class, $entity, array(
            'current_user' => $this->getUser(),
            'ad_users' => $this->getUsersFromAD(),
            'action' => $this->generateUrl('zastepstwo_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing Zastepstwo entity.
     *
     * @Route("/{id}", name="zastepstwo_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Zastepstwo:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zastepstwo')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zastepstwo entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('zastepstwo_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Zastepstwo entity.
     *
     * @Route("/{id}", name="zastepstwo_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entity = $entityManager->getRepository('ParpMainBundle:Zastepstwo')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Zastepstwo entity.');
            }

            if (!in_array("PARP_ADMIN", $this->getUser()->getRoles()) && !in_array("PARP_ADMIN_ZASTEPSTW", $this->getUser()->getRoles()) && $entity->getKogoZastepuje() != $this->getUser()->getUsername()) {
                $this->addFlash('warning', 'Nie masz uprawnień do usunięcia nie swoich zastępstw.');
            } else {
                $entityManager->remove($entity);
                $entityManager->flush();
                $this->addFlash('warning', 'Zastępstwo usunięte.');
            }
        }

        return $this->redirect($this->generateUrl('zastepstwo'));
    }

    /**
     * Creates a form to delete a Zastepstwo entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('zastepstwo_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Zastępstwo','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }


    private function getUsersFromAD()
    {
        $ldap = $this->get('ldap_service');
        $aduser = $ldap->getUserFromAD($this->getUser()->getUsername());

        $widzi_wszystkich = in_array("PARP_ADMIN", $this->getUser()->getRoles()) || in_array("PARP_ADMIN_ZASTEPSTW", $this->getUser()->getRoles());

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
