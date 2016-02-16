<?php

namespace Parp\MainBundle\Controller;

use Parp\MainBundle\Entity\Engagement;
use Parp\MainBundle\Entity\Entry;
use Parp\MainBundle\Entity\UserEngagement;
use Parp\MainBundle\Form\EngagementType;
use Parp\MainBundle\Form\UserEngagementType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\APYDataGridBundle;
use APY\DataGridBundle\Grid\Source\Vector;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Parp\MainBundle\Entity\UserZasoby;

class DefaultController extends Controller
{

    /**
     * @Route("/", name="main")
     * @Template()
     */
    public function indexAction()
    {
        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();

        $source = new Vector($ADUsers);

        $grid = $this->get('grid');

        $grid->setSource($source);

        $grid->hideColumns(array(
            'manager',
            //'info',
            'description',
            'division',
            //            'thumbnailphoto',
            'useraccountcontrol',
            'samaccountname',
            'initials'
        ));

        // Konfiguracja nazw kolumn

        $grid->getColumn('samaccountname')
                ->setTitle('Nazwa użytkownika')
                ->setOperators(array("like"))
                ->setOperatorsVisible(false)
                ->isPrimary();
        $grid->getColumn('name')
                ->setTitle("Nazwisko imię")
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('initials')
                ->setTitle("Inicjały")
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('title')
                ->setTitle("Stanowisko")
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('department')
                ->setTitle("Jednostka")
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('info')
                ->setTitle("Sekcja")
                ->setFilterType('select')
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('lastlogon')
                ->setTitle('Ostatnie logowanie')
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('accountexpires')
                ->setTitle('Umowa wygasa')
                ->setOperators(array("like"))
                ->setOperatorsVisible(false);
        $grid->getColumn('thumbnailphoto')
                ->setTitle('Zdj.')
                ->setFilterable(false);

        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);

        // Zdejmujemy filtr
        $grid->getColumn('akcje')
                ->setFilterable(false)
                ->setSafe(true);

        // Edycja konta
        $rowAction2 = new RowAction('<i class="glyphicon glyphicon-pencil"></i> Edycja', 'userEdit');
        $rowAction2->setColumn('akcje');
        $rowAction2->setRouteParameters(
                array('samaccountname')
        );
        $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction3 = new RowAction('<i class="fa fa-sitemap"></i> Struktura', 'structure');
        $rowAction3->setColumn('akcje');
        $rowAction3->setRouteParameters(
                array('samaccountname')
        );
        $rowAction3->addAttribute('class', 'btn btn-success btn-xs');

        // Edycja konta
        $rowAction4 = new RowAction('<i class="fa fa-database"></i> Zasoby', 'resources');
        $rowAction4->setColumn('akcje');
        $rowAction4->setRouteParameters(
                array('samaccountname')
        );
        $rowAction4->addAttribute('class', 'btn btn-success btn-xs');

//        $grid->addRowAction($rowAction1);
        $grid->addRowAction($rowAction2);
        $grid->addRowAction($rowAction3);
        $grid->addRowAction($rowAction4);

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));

        return $grid->getGridResponse();
    }

    /**
     * @param $samaccountName
     * @Route("/user/{samaccountname}/getphoto", name="userGetPhoto")
     */
    public function photoGetAction($samaccountname)
    {
        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);

        $picture = $ADUser[0]["thumbnailphoto"];

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'image/jpg');
        $response->headers->set('Content-Length', strlen($picture));
        $response->setContent($picture);
        return $response;
    }

    /**
     * @param $samaccountName
     * @Route("/user/{samaccountname}/photo", name="userPhoto")
     * @Template();
     */
    public function photoAction($samaccountname)
    {
        return array(
            'account' => $samaccountname,
        );
    }

    /**
     * @Route("/user/{samaccountname}/edit", name="userEdit");
     * @Template();
     */
    function editAction($samaccountname, Request $request)
    {
        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);

        $ADManager = $ldap->getUserFromAD(null, substr($ADUser[0]['manager'], 0, stripos($ADUser[0]['manager'], ',')));

        // wyciagnij imie i nazwisko managera z nazwy domenowej
        $ADUser[0]['manager'] = mb_substr($ADUser[0]['manager'], mb_stripos($ADUser[0]['manager'], '=') + 1, (mb_stripos($ADUser[0]['manager'], ',OU')) - (mb_stripos($ADUser[0]['manager'], '=') + 1));

        $defaultData = $ADUser[0];

        // pobierz uprawnienia poczatkowe
        $initialrights = $this->getDoctrine()->getRepository('ParpMainBundle:UserGrupa')->findOneBy(array('samaccountname' => $ADUser[0]['samaccountname']));
        if (!empty($initialrights)) {
            $defaultData['initialrights'] = $initialrights->getGrupa();
        }else{
            $defaultData['initialrights'] = null;
        }
        $previousData = $defaultData;

        // Pobieramy listę stanowisk
        $titlesEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Position')->findBy(array(), array('name' => 'asc'));
        $titles = array();
        foreach ($titlesEntity as $tmp) {
            $titles[$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Biur i Departamentów
        $departmentsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findBy(array(), array('name' => 'asc'));
        $departments = array();
        foreach ($departmentsEntity as $tmp) {
            $departments[$tmp->getName()] = $tmp->getName();
        }
        // Pobieramy listę Sekcji
        $sectionsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Section')->findBy(array(), array('name' => 'asc'));
        $sections = array();
        foreach ($sectionsEntity as $tmp) {
            $sections[$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Uprawnien
        $rightsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:GrupyUprawnien')->findBy(array(), array('opis' => 'asc'));
        $rights = array();
        foreach ($rightsEntity as $tmp) {
            $rights[$tmp->getKod()] = $tmp->getOpis();
        }

        $form = $this->createFormBuilder($defaultData)
                ->add('samaccountname', 'text', array(
                    'required' => false,
                    'read_only' => true,
                    'label' => 'Nazwa konta',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))
                ->add('name', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Imię i nazwisko',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))
                ->add('initials', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Inicjały',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))
                ->add('title', 'choice', array(
//                'class' => 'ParpMainBundle:Position',
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Stanowisko',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'data' => $defaultData["title"],
                    'choices' => $titles,
//                'mapped'=>false,
                ))
                ->add('info', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Sekcja',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $sections,
                    'data' => $defaultData['info'],
                ))
                ->add('department', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Biuro / Departament',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $departments,
                    'data' => $defaultData["department"],
                ))
                ->add('zapisz', 'submit', array(
                    'attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                    ),
                ))
                ->add('manager', 'text', array(
                    'required' => false,
                    'read_only' => true,
                    'label' => 'Przełożony',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'data' => $defaultData['manager']
                ))
                ->add('fromWhen', 'text', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
//                'widget' => 'single_text',
                    'label' => 'Data zmiany',
//                'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-lg-4 control-label',
                    ),
                    'required' => false,
                ))
                ->add('initialrights', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Uprawnienia początkowe',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $rights,
                    'data' => $defaultData["initialrights"],
                ))
                ->setMethod('POST')
                ->getForm();



        $form->handleRequest($request);

        if ($form->isValid()) {
            if (0 < count(array_diff($form->getData(), $previousData))) {
                //  Mamy zmianę, teraz trzeba wyodrebnić co to za zmiana
                // Tworzymy nowy wpis w bazie danych
                $entry = new Entry();
                $entry->setSamaccountname($samaccountname);
                $entry->setDistinguishedName($previousData["distinguishedname"]);
                $newData = array_diff($form->getData(), $previousData);

                foreach ($newData as $key => $value) {
                    switch ($key) {
                        case "name":
                            $entry->setCn($value);
                            break;
                        case "initials":
                            $entry->setInitials($value);
                            break;
                        case "accountexpires":
                            $entry->setAccountexpires($value);
                            break;
                        case "title":
                            $entry->setTitle($value);
                            break;
                        case "info":
                            $entry->setInfo($value);
                            break;
                        case "department":
                            $entry->setDepartment($value);
                            break;
                        case "manager":
                            $entry->setManager($value);
                            break;
                        case "fromWhen":
                            $entry->setFromWhen(new \DateTime($value));
                            break;
                        case "initialrights":
                            $entry->setInitialrights($value);
                            break;
                    }
                }
                if (!$entry->getFromWhen())
                    $entry->setFromWhen(new \DateTime('tomorrow'));

                $this->getDoctrine()->getManager()->persist($entry);
                $this->getDoctrine()->getManager()->flush();

                return $this->redirect($this->generateUrl('main'));
            }
        }

        return array(
            'user' => $ADUser[0],
            'form' => $form->createView(),
                //'manager' => isset($ADManager[0]) ? $ADManager[0] : "",
        );
    }

    /**
     * @Route("/user/add", name="userAdd")
     * @Template()
     */
    public function addAction(Request $request)
    {
        // Sięgamy do AD:
        // $ldap = $this->get('ldap_service');
        // $ADUser = $ldap->getUserFromAD($samaccountname);
        //$ADManager = $ldap->getUserFromAD(null, substr($ADUser[0]['manager'], 0, stripos($ADUser[0]['manager'], ',')));
        //$defaultData = $ADUser[0];
        //$previousData = $defaultData;
        $em = $this->getDoctrine()->getManager();

        // Pobieramy listę stanowisk
        $titlesEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Position')->findBy(array(), array('name' => 'asc'));
        $titles = array();
        foreach ($titlesEntity as $tmp) {
            $titles[$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Biur i Departamentów
        $departmentsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findBy(array(), array('name' => 'asc'));
        $departments = array();
        foreach ($departmentsEntity as $tmp) {
            $departments[$tmp->getName()] = $tmp->getName();
        }
        // Pobieramy listę Sekcji
        $sectionsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:Section')->findBy(array(), array('name' => 'asc'));
        $sections = array();
        foreach ($sectionsEntity as $tmp) {
            $sections[$tmp->getName()] = $tmp->getName();
        }

        // Pobieramy listę Uprawnien
        $rightsEntity = $this->getDoctrine()->getRepository('ParpMainBundle:GrupyUprawnien')->findBy(array(), array('opis' => 'asc'));
        $rights = array();
        foreach ($rightsEntity as $tmp) {
            $rights[$tmp->getKod()] = $tmp->getOpis();
        }

        $entry = new Entry();
        $form = $this->createFormBuilder($entry)
                ->add('samaccountname', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Nazwa konta',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    )
                ))->add('cn', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Imię i nazwisko',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))->add('department', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Biuro / Departament',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $departments,
                        //'data' => $defaultData["department"],
                ))->add('initials', 'text', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Inicjały',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))->add('title', 'choice', array(
//                'class' => 'ParpMainBundle:Position',
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Stanowisko',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    //'data' => $defaultData["title"],
                    'choices' => $titles,
//                'mapped'=>false,
                ))
                ->add('info', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Sekcja',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $sections,
                        //'data' => $defaultData['info'],
                ))
                ->add('manager', 'text', array(
                    'required' => false,
                    'read_only' => true,
                    'label' => 'Przełożony',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                ))
                ->add('accountExpires', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'widget' => 'single_text',
                    'label' => 'Data wygaśnięcia konta',
                    'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-lg-4 control-label',
                    ),
                    'required' => false,
                ))
                ->add('fromWhen', 'datetime', array(
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'widget' => 'single_text',
                    'label' => 'Data zmiany',
                    'format' => 'dd-MM-yyyy',
//                'input' => 'datetime',
                    'label_attr' => array(
                        'class' => 'col-lg-4 control-label',
                    ),
                    'required' => false,
                ))
                /* ->add('rights', 'choice', array(
                  'constraints' => array(
                  new NotBlank(array('message' => 'Nie wybrano uprawnień początkowych'))),
                  'mapped' => false,
                  'required' => false,
                  'read_only' => false,
                  'label' => 'Uprawnienia początkowe',
                  'label_attr' => array(
                  'class' => 'col-sm-4 control-label',
                  ),
                  'attr' => array(
                  'class' => 'form-control',
                  ),
                  'choices' => $rights,
                  )) */
                ->add('initialrights', 'choice', array(
                    'required' => false,
                    'read_only' => false,
                    'label' => 'Uprawnienia początkowe',
                    'label_attr' => array(
                        'class' => 'col-sm-4 control-label',
                    ),
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'choices' => $rights,
                ))
                ->add('zapisz', 'submit', array(
                    'attr' => array(
                        'class' => 'btn btn-success col-sm-12',
                    ),
                ))->setMethod('POST')
                ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // perform some action, such as saving the task to the database
            // utworz distinguishedname
            $tab = explode(".", $this->container->getParameter('ad_domain'));
            $ou = explode(".", $this->container->getParameter('ad_ou'));
            $department = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findOneByName($entry->getDepartment());

            $distinguishedname = "CN=" . $entry->getCn() . ", OU=" . $department->getShortname() . ",".$ou.", DC=" . $tab[0] . ",DC=" . $tab[1];

            $entry->setDistinguishedName($distinguishedname);
            $em->persist($entry);
            $em->flush();
            return $this->redirect($this->generateUrl('main'));
        }

        return $this->render('ParpMainBundle:Default:add.html.twig', array('form' => $form->createView()));
    }

    /**
     * @Route("/structure/{samaccountname}", name="structure")
     * @Template()
     */
    public function structureAction($samaccountname)
    {
        $ldap = $this->get('ldap_service');
        // Pobieramy naszego pracownika
        $ADUser = $ldap->getUserFromAD($samaccountname);

        // Pobieramy naszego przełożonego

        $ADManager = $ldap->getUserFromAD(null, substr($ADUser[0]['manager'], 0, stripos($ADUser[0]['manager'], ',')));

        // Pobieramy wszystkich jego pracowników (w których występuje jako przełożony)
        $ADWorkers = $ldap->getUserFromAD(null, "manager=" . $ADUser[0]["distinguishedname"]);

        return array(
            'przelozony' => isset($ADManager[0]) ? $ADManager[0] : "",
            'pracownik' => $ADUser[0],
            'pracownicy' => $ADWorkers,
        );
    }

    /**
     * @Route("/engage/{samaccountname}/{rok}", name="engageUser")
     * @Route("/engage/{samaccountname}", name="engageUser")
     * @Template()
     */
    public function engagementAction($samaccountname, $rok = null, Request $request)
    {
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);
        $engagements = $this->getDoctrine()->getRepository('ParpMainBundle:Engagement')->findAll();
        $userEngagements = $this->getDoctrine()->getRepository('ParpMainBundle:UserEngagement')->findBy(array('samaccountname' => $samaccountname));

        $em = $this->getDoctrine()->getManager();

        $year = $request->query->get('year');

        if (empty($year)) {
            $date = new \DateTime();
            $year = $date->format("Y");
        }

        if ($request->getMethod() == "POST") {

            $dane = $this->get('request')->request->all();
            //var_dump($dane);
            $year = !empty($dane['year']) ? $dane['year'] : $year;

            foreach ($dane['angaz'] as $key_angaz => $value_angaz) {
                // var_dump($key_angaz);                
                $engagement = $em->getRepository('ParpMainBundle:Engagement')->findOneBy(array('name' => $key_angaz));

                $last_value_angaz = "";
                $last_month = null;
                //petla po miesiacach
                foreach ($value_angaz as $key_month => $value_month) {

                    $userEngagement = $em->getRepository('ParpMainBundle:UserEngagement')->findOneByCryteria($samaccountname, $engagement->getId(), $this->getMonthFromStr($key_month), $year);

                    // obsluga atomatycznego uzupełniania
                    // czysc przy nowym zaangazowaniu
                    if ($last_value_angaz !== $value_angaz) {
                        $last_value_angaz = $value_angaz;
                        $last_month = null;
                    }
                    $last_month = !empty($value_month) ? $value_month : $last_month;

                    if (empty($userEngagement)) {
                        $userEngagement = new UserEngagement();
                        $userEngagement->setSamaccountname($samaccountname);
                        $userEngagement->setEngagement($engagement);
                        $userEngagement->setYear($year);
                        $userEngagement->setMonth($this->getMonthFromStr($key_month));
                        //$percent = (!empty($value_month)) ? $value_month : null;
                        //$userEngagement->setPercent($percent);
                        $userEngagement->setPercent($last_month);

                        $em->persist($userEngagement);
                        $em->flush();
                    } else {
                        //$percent = (!empty($value_month)) ? $value_month : null;
                        //$userEngagement->setPercent($percent);
                        $userEngagement->setPercent($last_month);
                        $em->persist($userEngagement);
                        $em->flush();
                    }
                }
            }
            return $this->redirect($this->generateUrl('engageUser', array('samaccountname' => $samaccountname, 'year' => $year)));
        }

        $userEngagements = $em->getRepository('ParpMainBundle:UserEngagement')->findBySamaccountnameAndYear($samaccountname, $year);

        $dane = array();
        foreach ($userEngagements as $userEngagement) {
            //echo $userEngagement->getSamaccountname() . ' ' . $userEngagement->getEngagement() . ' ' . $userEngagement->getPercent() . ' ' . $userEngagement->getMonth() . ' ' . $userEngagement->getYear() . "<br>";
            // zbuduj tablice z danymi
            $engagement = (string) $userEngagement->getEngagement();
            $month = $this->getStrFromMonth($userEngagement->getMonth());
            $percent = $userEngagement->getPercent();
            $dane[$engagement][$month] = $percent;
        }

        // policz sumy
        $sumy = array(
            'sumSty' => 0,
            'sumLut' => 0,
            'sumMar' => 0,
            'sumKwi' => 0,
            'sumMaj' => 0,
            'sumCze' => 0,
            'sumLip' => 0,
            'sumSie' => 0,
            'sumWrz' => 0,
            'sumPaz' => 0,
            'sumLis' => 0,
            'sumGru' => 0,
        );

        foreach ($userEngagements as $userEngagement) {

            $month = $this->getStrFromMonth($userEngagement->getMonth());
            $percent = $userEngagement->getPercent();
            $percent = !empty($percent) ? $percent : 0;

            switch ($month) {
                case "sty":
                    $sumy['sumSty'] += $percent;
                    break;
                case "lut":
                    $sumy['sumLut'] += $percent;
                    break;
                case "mar":
                    $sumy['sumMar'] += $percent;
                    break;
                case "kwi":
                    $sumy['sumKwi'] += $percent;
                    break;
                case "maj":
                    $sumy['sumMaj'] += $percent;
                    break;
                case "cze":
                    $sumy['sumCze'] += $percent;
                    break;
                case "lip":
                    $sumy['sumLip'] += $percent;
                    break;
                case "sie":
                    $sumy['sumSie'] += $percent;
                    break;
                case "wrz":
                    $sumy['sumWrz'] += $percent;
                    break;
                case "paz":
                    $sumy['sumPaz'] += $percent;
                    break;
                case "lis":
                    $sumy['sumLis'] += $percent;
                    break;
                case "gru":
                    $sumy['sumGru'] += $percent;
                    break;
            }
        }

//        if($form->isValid()){
//            $realEngagment = $this->getDoctrine()
//                ->getRepository('ParpMainBundle:UserEngagement')
//                ->findOneBy(array(
//                    'samaccountname'=>$samaccountname,
//                    'engagement'=>$userEngagement->getEngagement()));
//            if(!$realEngagment)
//                $realEngagment = new UserEngagement();
//            $realEngagment->setSamaccountname($samaccountname);
//            $realEngagment->setPercent($userEngagement->getPercent());
//            $realEngagment->setEngagement($userEngagement->getEngagement());
//            $this->getDoctrine()->getManager()->persist($realEngagment);
//            $this->getDoctrine()->getManager()->flush();
//
//            return $this->redirect($this->generateUrl('engageUser',array('samaccountname'=>$samaccountname)));
//        }

        return array(
            'engagements' => $engagements,
            'userEngagements' => $userEngagements,
            'user' => $ADUser[0],
            'dane' => $dane,
            'year' => $year,
            'sumy' => $sumy,
//            'form' => $form->createView(),
        );
    }

    protected function getMonthFromStr($month)
    {
        $tab = array('sty' => 1,
            'lut' => 2,
            'mar' => 3,
            'kwi' => 4,
            'maj' => 5,
            'cze' => 6,
            'lip' => 7,
            'sie' => 8,
            'wrz' => 9,
            'paz' => 10,
            'lis' => 11,
            'gru' => 12);
        return $tab[$month];
    }

    protected function getStrFromMonth($month)
    {
        $tab = array(
            1 => 'sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'paz', 'lis', 'gru'
        );
        return $tab[$month];
    }

    /**
     * @Route("/suggestinitials", name="suggest_initials")
     * 
     */
    public function ajaxSuggestInitials(Request $request)
    {
        $post = ($request->getMethod() == 'POST');
        $ajax = $request->isXMLHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
        if ((!$ajax) OR ( !$post)) {
            return null;
        }

        $samaccountname = $request->get('samaccountname', null);
        if (empty($samaccountname)) {
            throw new \Exception('Nie przekazano nazwy konta!');
        }
        $department = $request->get('department', null);
        $cn = $request->get('cn', null);

        $ldap = $this->get('ldap_service');

        if (empty($department)) {
            $ADUser = $ldap->getUserFromAD($samaccountname);
            // jezeli juz ma to zwrćc ma
            if (!empty($ADUser[0]['initials'])) {
                $initials = $ADUser[0]['initials'];
                return $this->render('ParpMainBundle:Default:suggestinitials.html.twig', array('initials' => $initials));
            }
            $description = $ADUser[0]['description'];
        } else {
            $description = $this->getDoctrine()->getRepository('ParpMainBundle:Departament')->findOneByName($department)->getShortname();
        }

        //pobierz userow z biura
        $users = $ldap->getUsersFromOU($description);

        $temp0 = !empty($ADUser[0]['name']) ? $ADUser[0]['name'] : $cn;
        // rozbijaj imie i nazwisko na 2 lub 3 części
        $temp1 = split(" ", $temp0);
        $words = array();
        $temp2 = "";
        if (strpos($temp1[0], '-') !== false) {
            $temp2 = split('-', $temp1[0]);
            $words[1] = $temp2[0];
            $words[2] = $temp2[1];
            $words[0] = $temp1[1];
        } else {
            $words[0] = $temp1[1];
            $words[1] = $temp1[0];
        }

        $j = 1;
        $initials = "";
        $czy_znaleziono = false;
        while (true) {
            // zeby nie zablokować skryptu po 100 iteracji skonczymy
            if ($j > 100)
                break;
            // stworz inicjały
            $initials = "";
            $czy_znaleziono = false;
            for ($k = 0; $k < count($words); $k++) {
                if ($k == 1) {
                    $letter = mb_substr($words[$k], 0, $j, 'UTF-8');
                    $initials .= $letter;
                } else {
                    $letter = mb_substr($words[$k], 0, 1, 'UTF-8');
                    $initials .= $letter;
                }
            }

            // sprawdz czy isnieje w tablicy z inicjałami z biura
            foreach ($users as $user) {
                if ($user['initials'] == $initials) {
                    $czy_znaleziono = true;
                    break;
                }
            }

            // jezeli nie znaleziono wyjdz z petli
            if ($czy_znaleziono == false) {
                break;
            }
            $j++;
        }
        $initials = mb_strtoupper($initials, 'UTF-8');

        return $this->render('ParpMainBundle:Default:suggestinitials.html.twig', array('initials' => $initials));
    }

    /**
     * @Route("/findmanager", name="find_manager")
     * 
     */
    public function ajaxFindManager(Request $request)
    {

        $post = ($request->getMethod() == 'POST');
        $ajax = $request->isXMLHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
        if ((!$ajax) OR ( !$post)) {
            return null;
        }

        $imienazwisko = $request->get('imienazwisko', null);
        if (empty($imienazwisko)) {
            throw new \Exception('Nie przekazano imieni i nazwiska!');
        }

        $ldap = $this->get('ldap_service');

        $ADUsers = $ldap->getAllFromAD();

        $dane = array();
        $i = 0;
        foreach ($ADUsers as $user) {

            if (mb_stripos($user['name'], $imienazwisko, 0, 'UTF-8') !== FALSE) {
                $dane[$i] = $user['name'];
                $i++;
            }
        }
        return $this->render('ParpMainBundle:Default:findmanager.html.twig', array('dane' => $dane));
    }

    /**
     * @Route("/file_ecm", name="form_file_ecm")
     * @Template()
     */
    public function formFileEcmAction(Request $request)
    {

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
                            'mimeTypes' => array('text/csv', 'text/plain'),
                            'mimeTypesMessage' => 'Niewłaściwy typ plku. Proszę wczytac plik z rozszerzeniem csv'
                                )),
                    ),
                    'mapped' => false,
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

                //$path = $file->getClientPathName();
                //var_dump($file->getPathname());
                // var_dump($name);

                if ($this->wczytajPlik($file)) {
                    $this->get('session')->getFlashBag()->set('warning', 'Plik został wczytany poprawnie.');
                    return $this->redirect($this->generateUrl('main'));
                }
            }
        }

        return $this->render('ParpMainBundle:Default:formfileecm.html.twig', array('form' => $form->createView()));
    }

    protected function ldap_escape($subject, $dn = FALSE, $ignore = NULL)
    {

        // The base array of characters to escape
        // Flip to keys for easy use of unset()
        $search = array_flip($dn ? array('\\', ',', '=', '+', '<', '>', ';', '"', '#') : array('\\', '*', '(', ')', "\x00"));

        // Process characters to ignore
        if (is_array($ignore)) {
            $ignore = array_values($ignore);
        }
        for ($char = 0; isset($ignore[$char]); $char++) {
            unset($search[$ignore[$char]]);
        }

        // Flip $search back to values and build $replace array
        $search = array_keys($search);
        $replace = array();
        foreach ($search as $char) {
            $replace[] = sprintf('\\%02x', ord($char));
        }

        // Do the main replacement
        $result = str_replace($search, $replace, $subject);

        // Encode leading/trailing spaces in DN values
        if ($dn) {
            if ($result[0] == ' ') {
                $result = '\\20' . substr($result, 1);
            }
            if ($result[strlen($result) - 1] == ' ') {
                $result = substr($result, 0, -1) . '\\20';
            }
        }

        return $result;
    }

    protected function wczytajPlik($file)
    {
        $dane = file_get_contents($file->getPathname());
        // $xxx = iconv('windows-1250', 'utf-8', $dane );

        $list = explode("\n", $dane);
        $ldap = $this->get('ldap_service');

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('delete from Parp\MainBundle\Entity\UserZasoby');
        $numDeleted = $query->execute();

        $tablica = array();
        foreach ($list as $wiersz) {
            // ostatni wiersz w pliku może być pusty!
            if (!empty($wiersz)) {
                //echo $wiersz ."\n";

                $wiersz = iconv('cp1250', 'utf-8//IGNORE', $wiersz);
                $dane = explode(";", $wiersz);
                if($dane[1] != "" && $dane[1] != ""){
                    $cnname = $this->ldap_escape($dane[1]) . '*' . $this->ldap_escape($dane[0]);
                    //echo ".".$wiersz.".<br>";
                    $ADUser = $ldap->getUserFromAD(null, $cnname);
                }
                if ($dane[1] != "" && $dane[1] != "" && !empty($ADUser)) {
                    // znajdz zasob                    
                    $zasob = $this->getDoctrine()->getRepository('ParpMainBundle:Zasoby')->findOneByNazwa(trim($dane[2]));
                    if (!$zasob) {
                        //echo "nie znaleziono $dane[2] " . "<br>";
                        //nie rób nic na razie
                    } else {
                        // sprawdz czy istnieje
                        /*
                          $userZasob = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findByAccountnameAndResource($ADUser[0]['samaccountname'], $zasob->getId());
                          if($ADUser[0]['samaccountname'] == 'andrzej_trocewicz'){
                          var_dump($userZasob);
                          var_dump($zasob->getId());

                          }
                          if (!$userZasob) {
                          // jezeli nie ma powiazania to utworz
                          //echo "brak" . "<br>";

                          $newUserZasob = new UserZasoby();
                          $newUserZasob->setSamaccountname($ADUser[0]['samaccountname']);
                          $newUserZasob->setZasobId($zasob->getId());
                          $em->persist($newUserZasob);

                          } */

                        // stworz tablice  bez powtorzen 
                        $samaccountname = $ADUser[0]['samaccountname'];
                        $zasobid = $zasob->getId();

                        if (key_exists($samaccountname, $tablica)) {
                            $klucz = $tablica[$samaccountname];
                            if (!in_array($zasobid, $klucz)) {
                                $tablica[$samaccountname][] = $zasobid;
                            }
                        } else {
                            $tablica[$samaccountname][] = $zasobid;
                        }
                    }
                }
            }
        }

        foreach ($tablica as $key => $values) {
            foreach ($values as $value) {
                //echo $key . ' ' . $value . "<br>";
                $newUserZasob = new UserZasoby();
                $newUserZasob->setSamaccountname($key);
                $newUserZasob->setZasobId($value);
                $em->persist($newUserZasob);
            }
        }
        $em->flush();

        return true;
    }

    /**
     * @Route("/resources/{samaccountname}", name="resources")
     * 
     */
    public function showResources($samaccountname, Request $request)
    {

        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);

        // Pobieramy listę stanowisk
        $userZasoby = $this->getDoctrine()->getRepository('ParpMainBundle:UserZasoby')->findNameByAccountname($samaccountname);

        return $this->render('ParpMainBundle:Default:resources.html.twig', array('user' => $ADUser[0]['name'], 'zasoby' => $userZasoby));
    }

    /**
     * @Route("/test", name="test")
     * 
     */
    public function test()
    {
        $em = $this->getDoctrine()->getManager();
        $userEngagements = $em->getRepository('ParpMainBundle:UserUprawnienia')->findSekcja('lolek_lolek');
        var_dump($userEngagements);
    }

}
