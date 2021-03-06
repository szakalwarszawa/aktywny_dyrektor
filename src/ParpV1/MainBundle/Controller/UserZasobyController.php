<?php

namespace ParpV1\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use APY\DataGridBundle\APYDataGridBundle;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Form\UserZasobyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * UserZasoby controller.
 *
 * @Route("/userzasoby")
 */
class UserZasobyController extends Controller
{

    /**
     * Lists all UserZasoby entities.
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     *
     * @Route("/index", name="userzasoby")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository(UserZasoby::class)->findAll();

        $source = new Entity(UserZasoby::class);

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
        $rowAction2 = new RowAction('<i class="fas fa-pencil"></i> Edycja', 'userzasoby_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="far fa-trash-alt"></i> Skasuj', 'userzasoby_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');



        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));



        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new UserZasoby entity.
     *
     * @Route("/", name="userzasoby_create")
     * @Method("POST")
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     * @Template("ParpMainBundle:UserZasoby:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new UserZasoby();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'UserZasoby został utworzony.');
                return $this->redirect($this->generateUrl('userzasoby'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a UserZasoby entity.
     *
     * @param UserZasoby $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(UserZasoby $entity)
    {
        $form = $this->createForm(UserZasobyType::class, $entity, array(
            'action' => $this->generateUrl('userzasoby_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Utwórz UserZasoby', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new UserZasoby entity.
     *
     * @Route("/new", name="userzasoby_new")
     * @Method("GET")
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     * @Template()
     */
    public function newAction()
    {
        $entity = new UserZasoby();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a UserZasoby entity.
     *
     * @Route("/{id}", name="userzasoby_show")
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(UserZasoby::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find UserZasoby entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing UserZasoby entity.
     *
     * @Route("/{id}/edit", name="userzasoby_edit")
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(UserZasoby::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find UserZasoby entity.');
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
    * Creates a form to edit a UserZasoby entity.
    *
    * @param UserZasoby $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(UserZasoby $entity)
    {
        $choicesModul = array();
        $choicesPoziomDostepu = array();

        $em = $this->getDoctrine()->getManager();
        $zasob = $em->getRepository(Zasoby::class)->find($entity->getZasobId());
        $p1 = explode(",", $zasob->getModulFunkcja());
        foreach ($p1 as $p) {
            $p = trim($p);
            $choicesModul[$p] = $p;
        }
        $p2 = explode(",", $zasob->getPoziomDostepu());
        foreach ($p2 as $p) {
            $p = trim($p);
            $choicesPoziomDostepu[$p] = $p;
        }


        $form = $this->createForm(UserZasobyType::class, $entity, array(
            'action' => $this->generateUrl('userzasoby_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'is_sub_form' => false,
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing UserZasoby entity.
     *
     * @Route("/{id}", name="userzasoby_update")
     * @Method("PUT")
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     * @Template("ParpMainBundle:UserZasoby:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(UserZasoby::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find UserZasoby entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('userzasoby_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a UserZasoby entity.
     *
     * @Route("/{id}", name="userzasoby_delete")
     * @Method("DELETE")
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository(UserZasoby::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find UserZasoby entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('userzasoby'));
    }

    /**
     * Creates a form to delete a UserZasoby entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('userzasoby_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Skasuj UserZasoby','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
