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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Form\DepartamentType;

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
     * @Route("/index", name="departament")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $grid = $this->generateGrid("2016");
        $grid2 = $this->generateGrid("stare");

        if ($grid->isReadyForRedirect() || $grid2->isReadyForRedirect()) {
            if ($grid->isReadyForExport()) {
                return $grid->getExportResponse();
            }

            if ($grid2->isReadyForExport()) {
                return $grid2->getExportResponse();
            }


            // Url is the same for the grids
            return new \Symfony\Component\HttpFoundation\RedirectResponse($grid->getRouteUrl());
        } else {
            return $this->render('ParpMainBundle:Departament:index.html.twig', array('grid' => $grid, 'grid2' => $grid2));
        }
    }

    protected function generateGrid($ktore)
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository(Departament::class)->findAll();

        $source = new Entity(Departament::class);
        $tableAlias = $source->getTableAlias();
        $source->manipulateQuery(
            function ($query) use ($tableAlias, $ktore) {

                $query->andWhere($tableAlias.'.nowaStruktura = '.($ktore == 'stare' ? '0' : '1'));
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

        // Edycja konta
        $rowAction2 = new RowAction('<i class="fas fa-pencil"></i> Edycja', 'departament_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="far fa-trash-alt"></i> Skasuj', 'departament_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');



        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));



        //$grid->isReadyForRedirect();
        //return $grid->getGridResponse();
        return $grid;
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

            $this->addFlash('warning', 'Departament został utworzony.');
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
        $form = $this->createForm(DepartamentType::class, $entity, array(
            'action' => $this->generateUrl('departament_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Utwórz Departament', 'attr' => array('class' => 'btn btn-success' )));

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

        $entity = $em->getRepository(Departament::class)->find($id);

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

        $entity = $em->getRepository(Departament::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Departament entity.');
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
    * Creates a form to edit a Departament entity.
    *
    * @param Departament $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Departament $entity)
    {
        $form = $this->createForm(DepartamentType::class, $entity, array(
            'action' => $this->generateUrl('departament_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

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

        $entity = $em->getRepository(Departament::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Departament entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('departament_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
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
            $entity = $em->getRepository(Departament::class)->find($id);

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
            ->add('submit', SubmitType::class, array('label' => 'Skasuj Departament','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
