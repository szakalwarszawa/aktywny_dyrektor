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

use Parp\MainBundle\Entity\Zadanie;
use Parp\MainBundle\Form\ZadanieType;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Zadanie controller.
 *
 * @Route("/zadanie")
 */
class ZadanieController extends Controller
{

    /**
     * Lists all Zadanie entities.
     *
     * @Route("/index", name="zadanie")
     * @Template()
     */
    public function indexAction()
    {
        $grid = $this->makeGrid(true);
        $grid2 = $this->makeGrid(false);
        return $this->render('ParpMainBundle:Zadanie:index.html.twig', array('grid' => $grid, 'grid2' => $grid2));
    }
    
    protected function makeGrid($aktywne)
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:Zadanie')->findAll();
    
        $source = new Entity('ParpMainBundle:Zadanie');
        
        $user = $this->get('security.context')->getToken()->getUser();
        $ad = $this->get('ldap_service')->getUserFromAD($user->getUsername());
        $username = trim($ad[0]['name']);
        //print_r($username);
        $source->manipulateQuery(
            function ($query) use ($username, $aktywne) {
                $query->andWhere('_a.osoby like :user')->setParameter('user', '%'.$username.'%');
                if ($aktywne) {
                    $query->andWhere('_a.dataUkonczenia is null');
                } else {
                    $query->andWhere('_a.dataUkonczenia is not null');
                }
                if ($aktywne) {
                    $query->orderBy("_a.dataDodania", "DESC");
                } else {
                    $query->orderBy("_a.dataUkonczenia", "DESC");
                }
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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'zadanie_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');
    
        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'zadanie_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');
    
       
    
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
    
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
    


        $grid->isReadyForRedirect();
        return $grid;
    }
    
    /**
     * Creates a new Zadanie entity.
     *
     * @Route("/", name="zadanie_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Zadanie:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Zadanie();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'Zadanie został utworzony.');
                return $this->redirect($this->generateUrl('zadanie'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Zadanie entity.
     *
     * @param Zadanie $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Zadanie $entity)
    {
        $form = $this->createForm(new ZadanieType(), $entity, array(
            'action' => $this->generateUrl('zadanie_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz Zadanie', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Zadanie entity.
     *
     * @Route("/new", name="zadanie_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Zadanie();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Zadanie entity.
     *
     * @Route("/{id}", name="zadanie_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zadanie')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zadanie entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Zadanie entity.
     *
     * @Route("/{id}/edit", name="zadanie_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zadanie')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zadanie entity.');
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
    * Creates a form to edit a Zadanie entity.
    *
    * @param Zadanie $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Zadanie $entity)
    {
        $form = $this->createForm(new ZadanieType(), $entity, array(
            'action' => $this->generateUrl('zadanie_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing Zadanie entity.
     *
     * @Route("/{id}", name="zadanie_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Zadanie:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zadanie')->find($id);
        $oldEn = clone $entity;

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zadanie entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            if (($entity->getDataUkonczenia() != null && $oldEn->getDataUkonczenia() == null)
            ) {
                //zmiana daty ukonczenie
                
                $user = $this->get('security.context')->getToken()->getUser();
                $ad = $this->get('ldap_service')->getUserFromAD($user->getUsername());
                $username = trim($ad[0]['name']);
                $entity->setUkonczonePrzez($username);
            }
            if ($entity->getStatus() == "zrealizowany" && $oldEn->getStatus() != "zrealizowany"
            ) {
                //zmiana statusu
                
                $user = $this->get('security.context')->getToken()->getUser();
                $ad = $this->get('ldap_service')->getUserFromAD($user->getUsername());
                $username = trim($ad[0]['name']);
                $entity->setUkonczonePrzez($username);
                if ($entity->getDataUkonczenia() == null) {
                    $entity->setDataUkonczenia(new \Datetime());
                }
            }
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('zadanie_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Zadanie entity.
     *
     * @Route("/{id}", name="zadanie_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Zadanie')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Zadanie entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('zadanie'));
    }

    /**
     * Creates a form to delete a Zadanie entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('zadanie_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Zadanie','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
}
