<?php

namespace ParpV1\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
use DateTime;
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
     * @Route("/index/{aktywne}", name="zastepstwo", defaults={"aktywne" : true})
     *
     * @param bool $aktywne
     *
     * @return Response
     */
    public function indexAction(bool $aktywne = true): Response
    {
        $source = new Entity(Zastepstwo::class);
        $now = new DateTime();

        $tableAlias = $source->getTableAlias();
        $source->manipulateQuery(
            function ($query) use ($tableAlias, $now, $aktywne) {
                if ($aktywne) {
                    $query->andWhere($tableAlias . '.dataDo >= :now')
                        ->setParameter(':now', $now)
                        ->addOrderBy($tableAlias . '.dataDo', 'ASC');
                } else {
                    $query->andWhere($tableAlias . '.dataDo < :now')
                        ->setParameter(':now', $now)
                        ->addOrderBy($tableAlias . '.dataDo', 'DESC');
                }
            }
        );
        $source->manipulateRow(
            function ($row) use ($now) {
                if ($row->getField('dataOd') > $now) {
                    $row->setColor('#d6e8f2');
                    $row->setField('opis', '<i class="fad fa-hourglass-half"></i> ' . $row->getField('opis'));
                }

                return $row;
            }
        );

        $grid = $this->get('grid');
        $grid->setSource($source);

        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);

        // Zdejmujemy filtr
        $grid->getColumn('akcje')
                ->setFilterable(false)
                ->setSafe(true);

        // Podgląd zastępstwa
        $rowAction1 = new RowAction('<i class="far fa-search"></i> Podgląd', 'zastepstwo_show');
        $rowAction1->setColumn('akcje');
        $rowAction1->addAttribute('class', 'btn btn-info btn-xs');

        // Edycja zastępstwa
        $rowAction2 = new RowAction('<i class="fas fa-pencil"></i> Edycja', 'zastepstwo_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
        $rowAction2->addManipulateRender(
            function ($action, $row) use ($now) {
                if ($row->getField('dataDo') > $now) {
                    return $action;
                } else {
                    return null;
                }
            }
        );

        // Usunięcie zastępstwa
        $rowAction3 = new RowAction('<i class="far fa-trash-alt"></i> Skasuj', 'zastepstwo_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
        $rowAction3->addManipulateRender(
            function ($action, $row) use ($now) {
                if ($row->getField('dataOd') > $now) {
                    return $action;
                } else {
                    return null;
                }
            }
        );

        $grid->addRowAction($rowAction1);
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
        $grid->isReadyForRedirect();

        return $grid->getGridResponse("ParpMainBundle:Zastepstwo:index.html.twig", [
                'aktywne' => $aktywne
        ]);
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
            $entity->setLastModifiedBy($this->getUser()->getUsername());
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

        $entity = $em->getRepository(Zastepstwo::class)->find($id);

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
        $entity = $entityManager->getRepository(Zastepstwo::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zastepstwo entity.');
        }

        if (!in_array("PARP_ADMIN", $this->getUser()->getRoles())
            && !in_array("PARP_ADMIN_ZASTEPSTW", $this->getUser()->getRoles())
            && $entity->getKogoZastepuje() != $this->getUser()->getUsername()
            ) {
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
        $now = new DateTime();

        if ($entity->getDataDo() < $now) {
            $this->addFlash('danger', 'Nie można edytować zakończonych zastępstw. Wprowadzone zmiany nie zostaną zapisane.');
        }

        $form = $this->createForm(ZastepstwoType::class, $entity, array(
            'current_user' => $this->getUser(),
            'ad_users' => $this->getUsersFromAD(),
            'action' => $this->generateUrl('zastepstwo_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

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
        $entityManager = $this->getDoctrine()->getManager();
        $now = new DateTime();

        $entity = $entityManager->getRepository(Zastepstwo::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zastepstwo entity.');
        }

        if ($entity->getDataDo() < $now) {
            $this->addFlash('danger', 'Nie można edytować zakończonych zastępstw.');
            return $this->redirectToRoute('zastepstwo');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->setLastModifiedBy($this->getUser()->getUsername());
            $entityManager->flush();
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
        $now = new DateTime();

        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entity = $entityManager->getRepository(Zastepstwo::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Zastepstwo entity.');
            }

            if ($entity->getDataOd() < $now) {
                $this->addFlash('danger', 'Nie można usuwać rozpoczętych zastępstw.');
                return $this->redirectToRoute('zastepstwo');
            }

            if (!in_array("PARP_ADMIN", $this->getUser()->getRoles())
                && !in_array("PARP_ADMIN_ZASTEPSTW", $this->getUser()->getRoles())
                && $entity->getKogoZastepuje() != $this->getUser()->getUsername()
            ) {
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
            ->add('submit', SubmitType::class, array('label' => 'Skasuj Zastępstwo','attr' => array('class' => 'btn btn-danger' )))
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
