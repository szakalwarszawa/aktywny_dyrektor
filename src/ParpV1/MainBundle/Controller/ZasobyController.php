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
use ParpV1\MainBundle\Grid\ParpExcelExport;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Exception\ZasobNotFoundException;
use ParpV1\MainBundle\Form\ZasobyType;
use ParpV1\MainBundle\Exception\SecurityTestException;
use ParpV1\MainBundle\Grid\ListaZasobowGrid;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Zasoby controller.
 *
 * @Route("/zasoby")
 */
class ZasobyController extends Controller
{
    protected $czyJestWlascicielemLubPowiernikiem = false;
    protected $niemozeEdytowac = false;

    /**
     * Lists all Zasoby entities.
     *
     * @Route("/index/{aktywne}", name="zasoby", defaults={"aktywne" : true})
     * @Template()
     */
    public function indexAction($aktywne = true)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $siatkaUsluga = $this->get('grid');
        $zasobyService = $this->get('zasoby_service');

        $parametry = array(
            'aktywne'        => $aktywne,
            'uzytkownik'     => $this->getUser(),
            'zasoby_service' => $zasobyService
        );

        $siatkaZasoby = new ListaZasobowGrid($siatkaUsluga, $entityManager, $parametry);


        return $siatkaUsluga->getGridResponse(
            "ParpMainBundle:Zasoby:index.html.twig",
            array(
                'grid' => $siatkaZasoby->generate(),
                'aktywne' => $aktywne
            )
        );
    }

    protected function sprawdzDostep($zasob)
    {

        if ($zasob) {
            $wlascicieleIPowirnicy = array_merge(explode(",", $zasob->getWlascicielZasobu()), explode(",", $zasob->getPowiernicyWlascicielaZasobu()));


            $this->czyJestWlascicielemLubPowiernikiem = in_array($this->getUser()->getUsername(), $wlascicieleIPowirnicy);
            $this->niemozeEdytowac = !in_array("PARP_ADMIN", $this->getUser()->getRoles()) &&
                !in_array("PARP_ADMIN_REJESTRU_ZASOBOW", $this->getUser()->getRoles()) &&
                !in_array("PARP_ADMIN_ZASOBOW", $this->getUser()->getRoles()) &&
                !$this->czyJestWlascicielemLubPowiernikiem;
        } else {
            $this->niemozeEdytowac = !in_array("PARP_ADMIN", $this->getUser()->getRoles()) && !in_array("PARP_ADMIN_REJESTRU_ZASOBOW", $this->getUser()->getRoles());
        }

        if ($this->niemozeEdytowac
        ) {
            $link = "<br><br><a class='btn btn-success' href='".$this->generateUrl("wniosekutworzeniezasobu_new")."'>Utwórz wniosek o utworzenie/zmianę/usunięcie zasobu</a><br><br>";
            throw new SecurityTestException("Tylko administrator AkD (lub właściciel lub powiernika właściciela zasobu) może aktualizować zmiany w zasobach AkD, pozostali użytkownicy muszą skorzystać z wniosku o utworzenie/zamianę/usunięcie wniosku, w celu utworzenia wniosku tutaj: ".$link, 721);
        }
    }
    /**
     * Creates a new Zasoby entity.
     *
     * @Route("/", name="zasoby_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Zasoby:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->sprawdzDostep();
        $entity = new Zasoby();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);



        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'Zasoby został utworzony.');
                return $this->redirect($this->generateUrl('zasoby'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Zasoby entity.
     *
     * @param Zasoby $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Zasoby $entity)
    {
        $form = $this->createForm(new ZasobyType($this), $entity, array(
            'action' => $this->generateUrl('zasoby_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz Zasoby', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Zasoby entity.
     *
     * @Route("/new", name="zasoby_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Zasoby();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }


    /**
     * Displays a form to edit an existing Zasoby entity.
     *
     * @Route("/{id}/edit", name="zasoby_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
        }
        $this->sprawdzDostep($entity);
        $grupy = explode(",", $entity->getGrupyAD());
        $grupyAd = array();
        $ldap = $this->get('ldap_service');
        $ldap->setDodatkoweOpcje('ekranEdycji');

        foreach ($grupy as $g) {
            if ($g != "") {
                $grupyAd[$g] = array(
                    'exists' => $ldap->checkGroupExistsFromAD($g),
                    'members' => $ldap->getMembersOfGroupFromAD($g)
                );
            }
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        $em = $this->getDoctrine()->getManager();
        $uzs = $em->getRepository('ParpV1\MainBundle\Entity\UserZasoby')->findUsersByZasobId($id);
        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'users' => $uzs,
            'grupy' => $grupy,
            'grupyAd' => $grupyAd
        );
    }

    private function getUsersFromAD()
    {
        $ldap = $this->get('ldap_service');
        $aduser = $ldap->getUserFromAD($this->getUser()->getUsername());
        $widzi_wszystkich = in_array("PARP_WNIOSEK_WIDZI_WSZYSTKICH", $this->getUser()->getRoles()) || in_array("PARP_ADMIN", $this->getUser()->getRoles());

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
    /**
    * Creates a form to edit a Zasoby entity.
    *
    * @param Zasoby $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Zasoby $entity)
    {
        $form = $this->createForm(new ZasobyType($this, "Nazwa", $this->niemozeEdytowac, $this->czyJestWlascicielemLubPowiernikiem), $entity, array(
            'action' => $this->generateUrl('zasoby_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Edits an existing Zasoby entity.
     *
     * @Route("/{id}", name="zasoby_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Zasoby:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
        }
        $this->sprawdzDostep($entity);
        if (!in_array('PARP_ADMIN', $this->getUser()->getRoles()) && !in_array('PARP_ADMIN_REJESTRU_ZASOBOW', $this->getUser()->getRoles()) && !$this->czyJestWlascicielemLubPowiernikiem) {
            die("nie masz uprawnien do edycji zasobow.");
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('zasoby_edit', array('id' => $id)));
        } else {
            die($editForm->getErrorsAsString());
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Zasoby entity.
     *
     * @Route("/delete/{id}/{published}", name="zasoby_delete", defaults={"published" : 0})
     * @Security("has_role('PARP_ADMIN') or has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     *
     * @param int $id
     * @param int $published
     *
     * @return Response
     *
     */
    public function deleteAction($id, $published = 0)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $zasob = $entityManager
            ->getRepository(Zasoby::class)
            ->findOneBy(array(
                'id' => $id,
            ));

        if (null === $zasob) {
            throw new ZasobNotFoundException();
        }

        $this->sprawdzDostep($zasob);

        $zasob->setPublished($published);

        $entityManager->flush();

        $nazwaZasobu = $zasob->getNazwa();
        $this->addFlash('danger', 'Zasób (' . $nazwaZasobu . ') został zdezaktywowany!');

        return $this->redirectToRoute('zasoby', array ('aktywne' => 0));
    }

    /**
     * Aktywuje nieaktywny zasób.
     *
     * @Route("/aktywuj_zasob/{id}", name="zasoby_aktywuj")
     * @Security("has_role('PARP_ADMIN') or has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     *
     * @param int $id
     *
     * @return Response
     */
    public function aktywujZasobAction($id)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $zasob = $entityManager
            ->getRepository(Zasoby::class)
            ->findOneBy(array(
                'id' => $id,
            ));

        if (null === $zasob) {
            throw new ZasobNotFoundException();
        }

        $this->sprawdzDostep($zasob);
        $nazwaZasobu = $zasob->getNazwa();

        if (false !== $zasob->getPublished()) {
            $this->addFlash('warning', 'Zasób (' . $nazwaZasobu . ') jest już aktywowany!');
        } else {
            $zasob->setPublished(1);

            $entityManager->flush();
            $this->addFlash('success', 'Zasób (' . $nazwaZasobu . ') został aktywowany!');
        }

        return $this->redirectToRoute('zasoby', array ('aktywne' => 1));
    }

    /**
     * Creates a form to delete a Zasoby entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('zasoby_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Zasoby','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }

    private function getManagers()
    {

        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllManagersFromAD();
        $users = array();
        foreach ($ADUsers as $u) {
            $users[$u['samaccountname']] = $u['name'];
        }
        return $users;
    }

    /**
     * Finds and displays a Zasoby entity.
     *
     * @Route("/{id}/show", name="zasoby_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Zasoby')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Zasoby entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }
}
