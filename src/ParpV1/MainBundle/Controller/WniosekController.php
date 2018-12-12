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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use ParpV1\MainBundle\Entity\Wniosek;
use ParpV1\MainBundle\Entity\WniosekViewer;
use ParpV1\MainBundle\Entity\WniosekEditor;
use ParpV1\MainBundle\Entity\Komentarz;
use ParpV1\MainBundle\Form\WniosekType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Wniosek controller.
 *
 * @Route("/wniosek")
 */
class WniosekController extends Controller
{

    /**
     * @Security("has_role('PARP_ADMIN')")
     *
     * @Route("/{id}/przekierujWniosek", name="wniosek_przekieruj")
     * @Template()
     */
    public function przekierujAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $wniosek = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

        if (!$wniosek) {
            throw $this->createNotFoundException('Unable to find Wniosek entity.');
        }
        $typWniosku = $wniosek->getWniosekNadanieOdebranieZasobow() ? 'wniosekONadanieUprawnien' : 'wniosekOUtworzenieZasobu';
        $people = ['viewrs' => [], 'editors' => []];
        foreach ($wniosek->getViewers() as $v) {
            $people['viewers'][$v->getSamaccountname()] = $v->getSamaccountname();
        }
        foreach ($wniosek->getEditors() as $v) {
            $people['editors'][$v->getSamaccountname()] = $v->getSamaccountname();
        }
        $dane = [
            'status' => $wniosek->getStatus()->getId(),
            'viewers' => $people['viewers'],
            'editors' => $people['editors'],
        ];

        $statusyEntities = $em->getRepository('ParpMainBundle:WniosekStatus')->findBy(['typWniosku' => $typWniosku]);
        $statusy = [];
        foreach ($statusyEntities as $e) {
            $statusy[$e->getId()] = $e->getNazwa();
        }

        $viewersEditors = $this->getUsersFromADWithRole("");
        $builder = $this->createFormBuilder($dane)
                ->add('status', ChoiceType::class, array(
                    'required' => false,
                    'label' => 'Status',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control'
                    ),
                    'choices' => array_flip($statusy)
                ))
                ->add('viewers', ChoiceType::class, array(
                    'required' => false,
                    'label' => 'Viewers',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control select2'
                    ),
                    'choices' => array_flip($viewersEditors),
                    'multiple' => true,
                    'expanded' => false
                ))
                ->add('editors', ChoiceType::class, array(
                    'required' => false,
                    'label' => 'Editors',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control select2'
                    ),
                    'choices' => array_flip($viewersEditors),
                    'multiple' => true,
                    'expanded' => false
                ))
                ->add('powod', TextareaType::class, array(
                    'required' => false,
                    'label' => 'Powód',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control'
                    ),
                ))
                ->add('zapisz', SubmitType::class, array(
                        'attr' => array(
                            'class' => 'btn btn-danger col-sm-12'
                        ),
                    ));

                $form = $builder->setAction($this->generateUrl('wniosek_przekieruj', ['id' => $id]))->setMethod('POST')->getForm();

        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            $dane = $form->getData();
            $komentarz = new Komentarz();
            $komentarz->setSamaccountname($this->getUser()->getUsername());
            $komentarz->setTytul("Ręczne przekierowanie wniosku");
            $komentarz->setOpis($dane['powod']);
            $komentarz->setObiekt($typWniosku == 'wniosekONadanieUprawnien' ? "WniosekNadanieOdebranieZasobow" : "WniosekUtworzenieZasobu");
            $komentarz->setObiektId($typWniosku == 'wniosekONadanieUprawnien' ? $wniosek->getWniosekNadanieOdebranieZasobow()->getId() : $wniosek->getWniosekUtworzenieZasobu()->getId());
            $em->persist($komentarz);

            $nowyStatus = $em->getRepository('ParpMainBundle:WniosekStatus')->find($dane['status']);
            $wniosek->setStatus($nowyStatus);
            $wniosek->setLockedBy(null);
            $wniosek->setLockedAt(null);

            foreach ($wniosek->getViewers() as $v) {
                $em->remove($v);
            }
            foreach ($wniosek->getEditors() as $v) {
                $em->remove($v);
            }
            foreach ($dane['viewers'] as $v) {
                $nv = new WniosekViewer();
                $nv->setSamaccountname($v);
                $nv->setWniosek($wniosek);
                $wniosek->addViewer($nv);
                $em->persist($nv);
            }

            foreach ($dane['editors'] as $v) {
                $nv = new WniosekEditor();
                $nv->setSamaccountname($v);
                $nv->setWniosek($wniosek);
                $wniosek->addEditor($nv);
                $em->persist($nv);
            }

            $em->flush();

            return $this->redirect($this->generateUrl(($typWniosku == 'wniosekONadanieUprawnien' ? 'wnioseknadanieodebraniezasobow_show' : 'wniosekutworzeniezasobu_edit'), ['id' => ($typWniosku == 'wniosekONadanieUprawnien' ? $wniosek->getWniosekNadanieOdebranieZasobow()->getId() : $wniosek->getWniosekUtworzenieZasobu()->getId())]));
        }


        return array(
            'wniosek'      => $wniosek,
            'form' => $form->createView()
        );
    }

    private function getUsersFromADWithRole($role)
    {
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        $users = array();
        foreach ($ADUsers as &$u) {
            if (in_array($role, $u['roles']) || $role == "") {
                $users[$u['samaccountname']] = $u['name'];
            }
        }
        //echo "<pre>"; var_dump($users); die();
        return $users;
    }


    /**
     * Lists all Wniosek entities.
     *
     * @Route("/index", name="wniosek")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:Wniosek')->findAll();

        $source = new Entity('ParpMainBundle:Wniosek');

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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'wniosek_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'wniosek_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');



        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));



        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new Wniosek entity.
     *
     * @Route("/", name="wniosek_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Wniosek:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Wniosek();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'Wniosek został utworzony.');
                return $this->redirect($this->generateUrl('wniosek'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Wniosek entity.
     *
     * @param Wniosek $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Wniosek $entity)
    {
        $form = $this->createForm(WniosekType::class, $entity, array(
            'action' => $this->generateUrl('wniosek_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Utwórz Wniosek', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Wniosek entity.
     *
     * @Route("/new", name="wniosek_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Wniosek();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Wniosek entity.
     *
     * @Route("/{id}", name="wniosek_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Wniosek entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Wniosek entity.
     *
     * @Route("/{id}/edit", name="wniosek_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Wniosek entity.');
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
    * Creates a form to edit a Wniosek entity.
    *
    * @param Wniosek $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Wniosek $entity)
    {
        $form = $this->createForm(WniosekType::class, $entity, array(
            'action' => $this->generateUrl('wniosek_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing Wniosek entity.
     *
     * @Route("/{id}", name="wniosek_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Wniosek:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Wniosek entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('wniosek_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Wniosek entity.
     *
     * @Route("/{id}", name="wniosek_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Wniosek')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Wniosek entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('wniosek'));
    }

    /**
     * Creates a form to delete a Wniosek entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('wniosek_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Skasuj Wniosek','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
