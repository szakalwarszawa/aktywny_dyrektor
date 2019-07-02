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
use ParpV1\MainBundle\Entity\Komentarz;
use ParpV1\MainBundle\Form\KomentarzType;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

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
        $entityManager = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository(Komentarz::class)->findAll();

        $source = new Entity(Komentarz::class);
        $tableAlias = $source->getTableAlias();
        $source->manipulateQuery(
            function ($query) use ($tableAlias, $obiekt, $obiektId) {
                $query->andWhere($tableAlias.'.obiekt = \''.$obiekt.'\' and '.$tableAlias.'.obiektId = \''.$obiektId.'\'');
            }
        );
        $grid = $this->get('grid');
        $grid->setSource($source);

        $wniosekZablokowany = false;
        if ('WniosekNadanieOdebranieZasobow' === $obiekt) {
            $wniosekNadanieOdebranieZasobow = $entityManager
                ->getRepository(WniosekNadanieOdebranieZasobow::class)
                ->findOneById($obiektId);
            $accessCheckerService = $this->get('check_access');
            $wniosekZablokowany = $accessCheckerService
                ->checkWniosekIsBlocked(WniosekNadanieOdebranieZasobow::class, $obiektId);
        }

        if (!$wniosekZablokowany) {
            $actionsColumn = new ActionsColumn('akcje', 'Działania');
            $grid->addColumn($actionsColumn);

            // Zdejmujemy filtr
            $grid->getColumn('akcje')
                    ->setFilterable(false)
                    ->setSafe(true);

            // Edycja konta
            $rowAction2 = new RowAction('<i class="fas fa-pencil"></i> Edycja', 'komentarz_edit');
            $rowAction2->setColumn('akcje');
            $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

            // Edycja konta
            $rowAction3 = new RowAction('<i class="far fa-trash-alt"></i> Skasuj', 'komentarz_delete');
            $rowAction3->setColumn('akcje');
            $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');



            $grid->addRowAction($rowAction2);
            $grid->addRowAction($rowAction3);

            $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
        }

        $grid->setRouteUrl($this->generateUrl('komentarz', array('obiekt' => $obiekt, 'obiektId' => $obiektId)));
        $grid->isReadyForRedirect();
        return $grid->getGridResponse(array('obiekt' => $obiekt, 'obiektId' => $obiektId, 'wniosek_zablokowany' => $wniosekZablokowany));
    }

    /**
     * Displays a form to create a new Komentarz entity.
     *
     * @Route("/new/{obiekt}/{obiektId}", name="komentarz_new")
     * @Template()
     */
    public function newAction(Request $request, $obiekt, $obiektId)
    {
        $komentarz = (new Komentarz())
            ->setObiekt($obiekt)
            ->setObiektId($obiektId)
            ->setSamaccountname($this->getUser()->getUsername());
        ;

        try {
            $returnUrl = $this
                ->generateUrl(strtolower($obiekt) . '_show', [
                    'id' => $obiektId
                ])
            ;
        } catch (RouteNotFoundException $exception) {
            $this->addFlash('info', 'Nie można dodać komentarza do podanego obiektu.');

            return $this->redirectToRoute('wnioseknadanieodebraniezasobow');
        }

        $form = $this->createForm(KomentarzType::class, $komentarz);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this
                ->getDoctrine()
                ->getManager()
            ;
            $entityManager->persist($komentarz);
            $entityManager->flush();

            $this->addFlash('warning', 'Komentarz został utworzony.');

            return $this->redirect($returnUrl);
        }

        return [
            'entity' => $komentarz,
            'form'   => $form->createView(),
            'returnUrl' => $returnUrl,
        ];
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

        $entity = $em->getRepository(Komentarz::class)->find($id);

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
     * @Template()
     */
    public function editAction(Request $request, $id)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $entity = $entityManager->getRepository(Komentarz::class)->find($id);

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
        $form = $this->createForm(KomentarzType::class, $entity, array(
            'action' => $this->generateUrl('komentarz_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

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
        $entityManager = $this->getDoctrine()->getManager();

        $entity = $entityManager->getRepository(Komentarz::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Komentarz entity.');
        }
        if ($entity->getSamaccountname() != $this->getUser()->getUsername()) {
            $this->addFlash('warning', 'Nie możesz wprowadzać zmian w cudzych komentarzach!!!');
            return $this->redirect($this->generateUrl('komentarz_edit', array('id' => $id)));
        }

        if ('WniosekNadanieOdebranieZasobow' === $entity->getObiekt()) {
            $wniosekNadanieOdebranieZasobow = $entityManager
                ->getRepository(WniosekNadanieOdebranieZasobow::class)
                ->findOneById($entity->getObiektId());
            $accessCheckerService = $this->get('check_access');
            $wniosekZablokowany = $accessCheckerService
                ->checkWniosekIsBlocked(WniosekNadanieOdebranieZasobow::class, $entity->getObiektId(), true);
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('komentarz_edit', array('id' => $id)));
        }

        $returnUrl = $this
            ->generateUrl(strtolower($entity->getObiekt()) . '_show', [
                'id' => $entity->getObiektId()
            ]);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'returnUrl' => $returnUrl,
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
            $entityManager = $this->getDoctrine()->getManager();
            $entity = $entityManager->getRepository(Komentarz::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Komentarz entity.');
            }

            $obiekt = (string) $entity->getObiekt();
            $obiektId = $entity->getObiektId();

            if ('WniosekNadanieOdebranieZasobow' === $entity->getObiekt()) {
                $wniosekNadanieOdebranieZasobow = $entityManager
                    ->getRepository(WniosekNadanieOdebranieZasobow::class)
                    ->findOneById($entity->getObiektId());
                $accessCheckerService = $this->get('check_access');
                $wniosekZablokowany = $accessCheckerService
                    ->checkWniosekIsBlocked(WniosekNadanieOdebranieZasobow::class, $entity->getObiektId(), true);
            }

            if ($entity->getSamaccountname() != $this->getUser()->getUsername()) {
                $this->addFlash('warning', 'Nie możesz wprowadzać zmian w cudzych komentarzach!!!');
                return $this->redirect($this->generateUrl('komentarz_edit', array('id' => $id)));
            }
            $entityManager->remove($entity);
            $entityManager->flush();
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
            case 'WniosekNadanieOdebranieZasobow':
                return 'wnioseknadanieodebraniezasobow_show';
            case 'WniosekUtworzenieZasobu':
                return 'wniosekutworzeniezasobu_show';
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
            ->add('submit', SubmitType::class, array('label' => 'Skasuj Komentarz','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
