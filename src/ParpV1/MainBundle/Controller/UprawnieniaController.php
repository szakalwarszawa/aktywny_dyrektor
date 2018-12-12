<?php

namespace ParpV1\MainBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use ParpV1\MainBundle\Entity\UserZasoby;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use ParpV1\MainBundle\Entity\Uprawnienia;
use ParpV1\MainBundle\Form\UprawnieniaType;
use ParpV1\MainBundle\Entity\UserUprawnienia;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Uprawnienia controller.
 *
 * @Route("/uprawnienia")
 */
class UprawnieniaController extends Controller
{

    /**
     * Lists all Uprawnienia entities.
     *
     * @Route("/index", name="uprawnienia")
     * @Template()
     */
    public function indexAction()
    {
        $source = new Entity(Uprawnienia::class);

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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'uprawnienia_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'uprawnienia_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');

        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));
        $grid->isReadyForRedirect();

        return $grid->getGridResponse();
    }

    /**
     * Creates a new Uprawnienia entity.
     *
     * @Route("/", name="uprawnienia_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Uprawnienia:new.html.twig")
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction(Request $request)
    {
        $entity = new Uprawnienia();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'Uprawnienia został utworzony.');
                return $this->redirect($this->generateUrl('uprawnienia'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Uprawnienia entity.
     *
     * @param Uprawnienia $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Uprawnienia $entity)
    {
        $form = $this->createForm(new UprawnieniaType(), $entity, array(
            'action' => $this->generateUrl('uprawnienia_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Utwórz Uprawnienia', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Uprawnienia entity.
     *
     * @Route("/new", name="uprawnienia_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Uprawnienia();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Uprawnienia entity.
     *
     * @Route("/{id}", name="uprawnienia_show")
     * @Method("GET")
     * @Template()
     * @param $id
     * @return array
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Uprawnienia')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Uprawnienia entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Uprawnienia entity.
     *
     * @Route("/{id}/edit", name="uprawnienia_edit")
     * @Method("GET")
     * @Template()
     * @param $id
     * @return array
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Uprawnienia')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Uprawnienia entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        $uzs = $em->getRepository(UserUprawnienia::class)->findUsersByUprawnienieId($id);
        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'users' => $uzs
        );
    }

    /**
    * Creates a form to edit a Uprawnienia entity.
    *
    * @param Uprawnienia $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Uprawnienia $entity)
    {
        $form = $this->createForm(new UprawnieniaType(), $entity, array(
            'action' => $this->generateUrl('uprawnienia_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Edits an existing Uprawnienia entity.
     *
     * @Route("/{id}", name="uprawnienia_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Uprawnienia:edit.html.twig")
     * @param Request $request
     * @param $id
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Uprawnienia')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Uprawnienia entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->setGrupyHistoriaZmian();
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('uprawnienia_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Uprawnienia entity.
     *
     * @Route("/{id}", name="uprawnienia_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Uprawnienia')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Uprawnienia entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('uprawnienia'));
    }

    /**
     * Creates a form to delete a Uprawnienia entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('uprawnienia_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array(
                'label' => 'Skasuj Uprawnienia',
                'attr' => array('class' => 'btn btn-danger'
                )
            ))
            ->getForm()
        ;
    }

    /**
     * @Route("/zamien/{obecnyPoziomDostepuIdB64}/{obecnyPoziomDostepuB64}/{nowyPoziomDostepuB64}", name="uprawnienia_zamien")
     *
     * @param $obecnyPoziomDostepuB64
     * @param $nowyPoziomDostepuB64
     */
    public function zamienPoziomDostepuAction($obecnyPoziomDostepuIdB64, $obecnyPoziomDostepuB64, $nowyPoziomDostepuB64)
    {
        $manager = $this->getDoctrine()->getManager();
        $uprawnieniaService = $this->get('uprawnienia_service');
        $obecnyUserZasobId = $this->stringUnCode($obecnyPoziomDostepuIdB64);
        $obecnyPoziomDostepuString = $this->stringUnCode($obecnyPoziomDostepuB64);
        $nowyPoziomDostepuString = $this->stringUnCode($nowyPoziomDostepuB64);

        /** @var UserZasoby $obecnyPoziomDostepu */
        $obecnyUserZasob= $manager->getRepository('ParpMainBundle:UserZasoby')->find($obecnyUserZasobId);

        if (null === $obecnyUserZasob) {
            throw new EntityNotFoundException('Nie ma UserZasobu o takim identyfikatorze!');
        }

        $obecnePoziomyDostepu = array_unique(
            $uprawnieniaService->zwrocUprawnieniaJakoTablica($obecnyUserZasob->getPoziomDostepu())
        );

        $poziomDoPodmiany = array_search($obecnyPoziomDostepuString, $obecnePoziomyDostepu);
        // Najpierw minimalizujemy - jest kłopot z wielością tej samej wartości

        $obecnePoziomyDostepu[$poziomDoPodmiany] = $nowyPoziomDostepuString;

        $nowePoziomyDostepu = implode(';', array_unique($obecnePoziomyDostepu));

        $obecnyUserZasob->setPoziomDostepu($nowePoziomyDostepu);

        $manager->flush();

        return $this->redirectToRoute('resources', ['samaccountname' => $obecnyUserZasob->getSamaccountname()]);
    }

    /**
     * @param string $string
     * @return string
     */
    private function stringUnCode($string)
    {
        return base64_decode(urldecode($string));
    }
}
