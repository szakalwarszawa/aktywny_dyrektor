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

use ParpV1\MainBundle\Entity\Komentarz;
use ParpV1\MainBundle\Form\KomentarzType;

/**
 * Komentarz controller.
 *
 * @Route("/komentarz")
 */
class KomentarzController extends Controller
{

    /**
     * Lists all Komentarz entities.
     *
     * @Route("/index/{obiekt}/{obiektId}", name="komentarz")
     * @Template()
     */
    public function indexAction($obiekt, $obiektId)
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:Komentarz')->findAll();

        $source = new Entity('ParpMainBundle:Komentarz');
        $tableAlias = $source->getTableAlias();
        $source->manipulateQuery(
            function ($query) use ($tableAlias, $obiekt, $obiektId) {
                $query->andWhere($tableAlias.'.obiekt = \''.$obiekt.'\' and '.$tableAlias.'.obiektId = \''.$obiektId.'\'');
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'komentarz_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'komentarz_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');



        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));


        $grid->setRouteUrl($this->generateUrl('komentarz', array('obiekt' => $obiekt, 'obiektId' => $obiektId)));
        $grid->isReadyForRedirect();
        return $grid->getGridResponse(array('obiekt' => $obiekt, 'obiektId' => $obiektId));
    }
    /**
     * Creates a new Komentarz entity.
     *
     * @Route("/", name="komentarz_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Komentarz:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Komentarz();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'Komentarz został utworzony.');
                return $this->redirect($this->generateUrl(strtolower($entity->getObiekt())."_show", array('id' => $entity->getObiektId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Komentarz entity.
     *
     * @param Komentarz $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Komentarz $entity)
    {
        $form = $this->createForm(new KomentarzType(), $entity, array(
            'action' => $this->generateUrl('komentarz_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz Komentarz', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Komentarz entity.
     *
     * @Route("/new/{obiekt}/{obiektId}", name="komentarz_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request, $obiekt, $obiektId)
    {
        $entity = new Komentarz();
        $entity->setObiekt($obiekt);
        $entity->setObiektId($obiektId);
        $entity->setSamaccountname($this->getUser()->getUsername());
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'returnUrl' => $request->headers->get('referer')
        );
    }

    /**
     * Finds and displays a Komentarz entity.
     *
     * @Route("/{id}", name="komentarz_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Komentarz')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Komentarz entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Komentarz entity.
     *
     * @Route("/{id}/edit", name="komentarz_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Komentarz')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Komentarz entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        $pt = strtolower($entity->getObiekt())."_edit";
        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'returnUrl' => $this->generateUrl($pt, ['id' => $entity->getObiektId()]) //$request->headers->get('referer')
        );
    }

    /**
    * Creates a form to edit a Komentarz entity.
    *
    * @param Komentarz $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Komentarz $entity)
    {
        $form = $this->createForm(new KomentarzType(), $entity, array(
            'action' => $this->generateUrl('komentarz_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing Komentarz entity.
     *
     * @Route("/{id}", name="komentarz_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Komentarz:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Komentarz')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Komentarz entity.');
        }
        if ($entity->getSamaccountname() != $this->getUser()->getUsername()) {
            $this->addFlash('warning', 'Nie możesz wprowadzać zmian w cudzych komentarzach!!!');
            return $this->redirect($this->generateUrl('komentarz_edit', array('id' => $id)));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('komentarz_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Komentarz entity.
     *
     * @Route("/{id}", name="komentarz_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Komentarz')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Komentarz entity.');
            }

            $obiekt = (string) $entity->getObiekt();
            $obiektId = $entity->getObiektId();

            if ($entity->getSamaccountname() != $this->getUser()->getUsername()) {
                $this->addFlash('warning', 'Nie możesz wprowadzać zmian w cudzych komentarzach!!!');
                return $this->redirect($this->generateUrl('komentarz_edit', array('id' => $id)));
            }
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirectToRoute($this->pokazRouteDoPrzekierowania($obiekt), array(
            'id' => $obiektId,
        ));
    }

    /**
     * Zwraca route do przekierowania.
     *
     * @param string $komentarz
     *
     * @return string
     */
    private function pokazRouteDoPrzekierowania($komentarz)
    {
        switch ($komentarz) {
            case 'WniosekNadanieOdebranieZasobow' : return 'wnioseknadanieodebraniezasobow_show';
            case 'WniosekUtworzenieZasobu' : return 'wniosekutworzeniezasobu_show';
        }
    }

    /**
     * Creates a form to delete a Komentarz entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('komentarz_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Komentarz','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
