<?php

namespace ParpV1\MainBundle\Controller;

use ParpV1\MainBundle\Entity\UserEngagement;
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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use ParpV1\MainBundle\Entity\Engagement;
use ParpV1\MainBundle\Form\EngagementType;

/**
 * Engagement controller.
 *
 * @Route("/engagement")
 */
class EngagementController extends Controller
{

    /**
     * Lists all Engagement entities.
     *
     * @Route("/index", name="engagement")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        //$entities = $em->getRepository('ParpMainBundle:Engagement')->findAll();

        $source = new Entity('ParpMainBundle:Engagement');

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
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'engagement_edit');
        $rowAction2->setColumn('akcje');
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-delete"></i> Skasuj', 'engagement_delete');
        $rowAction3->setColumn('akcje');
        $rowAction3->addAttribute('class', 'btn btn-danger btn-xs');



        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));



        $grid->isReadyForRedirect();
        return $grid->getGridResponse();
    }
    /**
     * Creates a new Engagement entity.
     *
     * @Route("/", name="engagement_create")
     * @Method("POST")
     * @Template("ParpMainBundle:Engagement:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Engagement();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('warning', 'Engagement został utworzony.');
                return $this->redirect($this->generateUrl('engagement'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Engagement entity.
     *
     * @param Engagement $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Engagement $entity)
    {
        $form = $this->createForm(new EngagementType(), $entity, array(
            'action' => $this->generateUrl('engagement_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Utwórz Zaangażowanie', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }

    /**
     * Displays a form to create a new Engagement entity.
     *
     * @Route("/new", name="engagement_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Engagement();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Engagement entity.
     *
     * @Route("/show/{id}", name="engagement_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Engagement')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Engagement entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Engagement entity.
     *
     * @Route("/{id}/edit", name="engagement_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Engagement')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Engagement entity.');
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
    * Creates a form to edit a Engagement entity.
    *
    * @param Engagement $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Engagement $entity)
    {
        $form = $this->createForm(new EngagementType(), $entity, array(
            'action' => $this->generateUrl('engagement_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success' )));

        return $form;
    }
    /**
     * Edits an existing Engagement entity.
     *
     * @Route("/{id}", name="engagement_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:Engagement:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ParpMainBundle:Engagement')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Engagement entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');
            return $this->redirect($this->generateUrl('engagement_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Engagement entity.
     *
     * @Route("/{id}", name="engagement_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ParpMainBundle:Engagement')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Engagement entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('engagement'));
    }

    /**
     * Creates a form to delete a Engagement entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('engagement_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Skasuj Engagement','attr' => array('class' => 'btn btn-danger' )))
            ->getForm()
        ;
    }
    /**
     * Import excel engagement
     *
     * @Route("/import", name="engagement_import")
     * @Security("has_role('PARP_IMPORT_ZAANGAZOWANIE')")
     * @Template()
     */
    public function importAction(Request $request)
    {
        $lata = [];
        for ($i = date('Y'); $i < date('Y') + 10; $i++) {
            $lata[$i] = $i;
        }


        $form = $this->createFormBuilder()->add('plik', 'file', array(
            'required' => false,
            'label_attr' => array(
                'class' => 'col-sm-4 control-label',
            ),
            'attr' => array('class' => 'filestyle',
                'data-buttonBefore' => 'false',
                'data-buttonText' => 'Wybierz plik',
                'data-iconName' => 'fa fa-file-excel-o',
            ),
            'constraints' => array(
                new NotBlank(array('message' => 'Nie wybrano pliku')),
                new File(array(
                    'maxSize' => 1024 * 1024 * 10,
                    'maxSizeMessage' => 'Przekroczono rozmiar wczytywanego pliku',
                    'mimeTypes' => array('text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/msexcel', 'application/xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/octet-stream'),
                    'mimeTypesMessage' => 'Niewłaściwy typ pliku. Proszę wczytac plik z rozszerzeniem csv'
                )),
            ),
            'mapped' => false,
        ))
            ->add('rok', 'choice', ['choices' => $lata])
            ->add('dodanieZaangazowan', 'checkbox', array(
                'required' => false,
                'label'      => 'Dodaj do słownika brakujące zaangażowania'
                ))
            ->add('wczytaj', 'submit', array('attr' => array(
                'class' => 'btn btn-success col-sm-12',
            )))
            ->getForm();

        $form->handleRequest($request);
        if ($request->getMethod() == 'POST') {
            if ($form->isValid()) {
                $file = $form->get('plik')->getData();
                $name = $file->getClientOriginalName();
                $bledy = $this->parsePlik($file, $form);


                if (empty($bledy)) {
                    $this->get('session')->getFlashBag()->add('notice', 'Plik został wczytany');
                }

                foreach ($bledy as $blad) {
                    $this->get('session')->getFlashBag()->add('warning', $blad['error']);
                }
                return $this->redirectToRoute('engagement_import');
            }
        }

        return [
            'form' => $form->createView()
        ];
    }

    protected $mapowanieKolumn = [
        'B' => 'name',
        'C' => 'etat',
        'D' => 'id',
        'E' => 'program',
        'K' => '1',
        'L' => '2',
        'M' => '3',
        'N' => '4',
        'O' => '5',
        'P' => '6',
        'Q' => '7',
        'R' => '8',
        'S' => '9',
        'T' => '10',
        'U' => '11',
        'V' => '12',
    ];

    protected function parsePlik($fileObject, $form)
    {
        $wynik = [];
        $file = $fileObject->getPathname();
        if (!file_exists($file)) {
            die('nie ma pliku');
        }
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);


        for ($i = 2; $i < count($sheetData)+1; $i++) {
            $dane = [];
            $j = 0;
            foreach ($this->mapowanieKolumn as $k => $f) {
                $dana = $sheetData[$i][$k];

                if ($f == 'program') {
                    $dana = $this->parseNazwaProgramu($dana);
                }
                if ($j > 3) {
                    //var_dump($dana);
                    $dana = floatval($dana);

                    $dana = (int) round($dana * 10000);
                }

                $dane[$f] = $dana;
                $j++;
            }
            $wynik[$dane['name']][] = $dane;
        }

        $bledy = $this->parseWyniki($wynik, $form);

        return $bledy;
    }
    protected function correctWyniki($dane)
    {
        $ret = [];

        foreach ($dane as $person => $data) {
            $programy = [];

            for ($i = 1; $i < 13; $i++) {
                foreach ($data as $d) {
                    $program = $d['program'];
                    //if (!isset($programy[$program][$i])) {
                    //    $programy[$program][$i] = floatval($d[$i]);
                    //} else {
                    //    $programy[$program][$i] += floatval($d[$i]);
                    //}

                    if (!isset($programy[$program][$i])) {
                        $programy[$program][$i] = ($d[$i]);
                    } else {
                        $programy[$program][$i] += ($d[$i]);
                    }
                }
            }
            /*    for($i = 1; $i < 13; $i++) {
                    $percent = 0;
                    if (isset($programy[$d['program']])) {
                        $percent = floatval($programy[$d['program']]);
                    }
                    $percent += floatval($d[$i]);
                    $d[$i] = $percent;
                }
                $programy[$d['program']] = $d;
            }*/
            $ret[$d['name']] = $programy;
        }
        return $ret;
    }
    protected function parseWyniki($dane, $form)
    {
        //var_dump($dane);
        $dane = $this->correctWyniki($dane);
        //echo "<pre>" . print_r($dane, true). "</pre>";
        //die();
        $em = $this->getDoctrine()->getManager();

        $programy = [];
        $ps = $em->getRepository('ParpMainBundle:Engagement')->findAll();
        foreach ($ps as $p) {
            $programy[$p->getName()] = $p;
        }

        $rok = $form->getData()['rok'];
        $bledy = [];
        //var_dump($dane); die();
        foreach ($dane as $id => $d) {
            //$daneRekord = $em->getRepository('ParpMainBundle:DaneRekord')->findOneBySymbolRekordId($id);
            $rozbite = $this->get('samaccountname_generator')->rozbijFullname($id);
            $daneRekord = $em->getRepository('ParpMainBundle:DaneRekord')->findOneBy([
                'nazwisko' => $rozbite['nazwisko'],
                'imie' => $rozbite['imie'],
            ]);
            if ($daneRekord === null) {
                // błędy o braku danych z rekorda przykrywają te z braku zaangazowań
                // a nie wiem czy są/bedą potrzebne
                //$bledy[] = [
                //    'error' => 'Brak danych o osobie '.$id,
                //    'dane' => $d
                //];
            } else {
                $usereng = new UserEngagement();
                $usereng->setSamaccountname($daneRekord->getLogin());
                $usereng->setYear($rok);
                foreach ($d as $program => $wpis) {
                    for ($i = 1; $i < 13; $i++) {
                        $zaangazownie = $em->getRepository('ParpMainBundle:Engagement')->findOneByName($program);
                        if (null == $zaangazownie) {
                            if ($form->getData()['dodanieZaangazowan']) {
                                $program2 = new Engagement();
                                $program2->setName($program);
                                $em->persist($program2);
                                $em->flush();
                                $this->get('session')->getFlashBag()->add('warning', 'W słowniku brak zaangażownia o nazwie (utworzono nowe): ' . $program2);
                            } else {
                                $bledy[] = [
                                    'error' => 'W słowniku brak zaangażownia o nazwie ' . $program,
                                    'dane' => $program
                                ];
                                break;
//                                continue;
                            }
                        }

                        $zaangazownie = $em->getRepository('ParpMainBundle:Engagement')->findOneByName($program);
                        
                        $ug = $em->getRepository('ParpMainBundle:UserEngagement')->findOneByCryteria(
                            $daneRekord->getLogin(),
                            $zaangazownie->getId(),
                            $i,
                            $rok
                        );

                        if (null == $ug) {
                            $ug = clone $usereng;
                            $ug->setMonth($i);
                            $p = $wpis[$i];
                            $ug->setPercent($p);
                            $ug->setCzyNowy(true);
                            $ug->setEngagement($zaangazownie);
                        } elseif ($ug->getPercent() != $wpis[$i]) {
                            $ugnew = clone $usereng;
                            $ugnew->setId(null);
                            $ugnew->setMonth($i);
                            $p = $wpis[$i];
                            $ugnew->setPercent($p);
                            $ugnew->setEngagement($zaangazownie);
                            $ugnew->setCzyNowy(true);

                            $ug->setCzyNowy(false);
                            $ug->setKiedyUsuniety(new \DateTime());
                            $ug->setKtoUsunal($this->getUser()->getUsername());
                            $em->persist($ugnew);
                        }
/*
                        if (isset($programy[$program])) {
                            $program2 = $programy[$program];
                        } else {
                            $program2 = new Engagement();
                            $program2->setName($program);
                            $programy[$program] = $program2;
                            $em->persist($program2);
                        }
                        $ug->setEngagement($program2);
*/
                        $em->persist($ug);
                    }
                    if (!empty($bledy)) {
                        break;
                    }
                }
            }
            if (!empty($bledy)) {
                break;
            }
        }

        if (!empty($bledy)) {
            return $bledy;
        }

        $em->flush();

        return $bledy;
    }

    protected function parseNazwaProgramu($program)
    {
        $pattern = ['/[,\d]+%/i', '/[\d]+,[\d]+/i', '/--/'];
        $replacement = ['', '', '-'];
        $program =  preg_replace($pattern, $replacement, $program);
        $program = trim($program);
        $program = trim($program, '-');

        /*
        if(strpos($program, "%") !== false || strpos($program, ",") !== false){
            //mamy procent
            $p = explode('-', $program);
            $p2 = "";
            for($i = 0; $i < count($p); $i++){
                if(strpos("%", $p[$i]) !== false || strpos(",", $p[$i]) !== false) {
                    $p2 .= ($i == 0 ? "" : "-") . $p[$i];
                }
            }
            $program = $p2;
        }
*/
        return $program;
    }
}
