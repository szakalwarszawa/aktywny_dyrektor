<?php

namespace ParpV1\MainBundle\Controller;

use APY\DataGridBundle\Grid\Action\MassAction;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Export\ExcelExport;
use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\Source\Vector;
use Doctrine\ORM\EntityNotFoundException;
use ParpV1\MainBundle\Entity\AclUserRole;
use ParpV1\MainBundle\Entity\AclRole;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\UserUprawnienia;
use ParpV1\MainBundle\Entity\Section;
use ParpV1\MainBundle\Entity\GrupyUprawnien;
use ParpV1\MainBundle\Entity\Uprawnienia;
use ParpV1\MainBundle\Entity\UserEngagement;
use ParpV1\MainBundle\Entity\Engagement;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\DaneRekord;
use ParpV1\MainBundle\Entity\UserGrupa;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Entity\Position;
use ParpV1\MainBundle\Exception\SecurityTestException;
use ParpV1\MainBundle\Services\ParpMailerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use ParpV1\MainBundle\Form\EdycjaUzytkownikaFormType;
use ParpV1\MainBundle\Services\EdycjaUzytkownikaService;
use ParpV1\MainBundle\Services\EdycjaUzytkownikaFormService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\MainBundle\Constants\AdUserConstants;

/**
 * Class DefaultController
 * @package ParpV1\MainBundle\Controller
 */
class DefaultController extends Controller
{
    protected $col2month = array('G', 'I', 'K', 'M', 'O', 'Q', 'S', 'U', 'W', 'Y', 'AA', 'AC');
    protected $ADUsers = array();

    /**
     * @Route("/index/{ktorzy}", name="main", defaults={"ktorzy": "usersFromAd"})
     * @Route("/", name="main_home")
     * @Template()
     * @param string $ktorzy
     *
     * @return Export[]|Response
     */
    public function indexAction($ktorzy = 'usersFromAd')
    {
        //$this->get('check_access')->checkAccess('USER_MANAGEMENT');

        $ldap = $this->get('ldap_service');
        $ldap->setDodatkoweOpcje('ekranEdycji');

        // Sięgamy do AD:
        if ($ktorzy === 'usersFromAd' || $ktorzy === 'usersFromAdFull') {
            $aduser = $ldap->getUserFromAD($this->getUser()->getUsername());
            $widzi_wszystkich =
                in_array('PARP_BZK_1', $this->getUser()->getRoles(), true) ||
                in_array('PARP_BZK_2', $this->getUser()->getRoles(), true) ||
                in_array('PARP_ADMIN', $this->getUser()->getRoles(), true) ||
                in_array('PARP_ADMIN_REJESTRU_ZASOBOW', $this->getUser()->getRoles(), true) ||
                in_array('PARP_AZ_UPRAWNIENIA_BEZ_WNIOSKOW', $this->getUser()->getRoles(), true);
            if ($ktorzy === 'usersFromAdFull' && in_array('PARP_AZ_UPRAWNIENIA_BEZ_WNIOSKOW', $this->getUser()->getRoles(), true)) {
                $ADUsersTemp = $ldap->getAllFromAD('wszyscy');
            } else {
                $ADUsersTemp = $ldap->getAllFromAD(false, false, null, true);
            }
            $ADUsers = array();
            foreach ($ADUsersTemp as $u) {
                //albo ma role ze widzi wszystkich albo widzi tylko swoj departament
                if ($widzi_wszystkich ||
                    (!empty($u['department']) &&
                    mb_strtolower(trim($aduser[0]['department'])) === mb_strtolower(trim($u['department'])))
                ) {
                    $ADUsers[] = $u;//['name'];
                }
            }
        } else {
            $ADUsers = $this->getDoctrine()->getRepository(Entry::class)->getTempEntriesAsUsers($ldap);
        }

        if (count($ADUsers) === 0) {
            return $this->render('ParpMainBundle:Default:NoData.html.twig');
        }

        $grid = $this->getUserGrid($this->get('grid'), $ADUsers, $ktorzy, $this->getUser()->getRoles());


        if ($grid->isReadyForExport()) {
            return $grid->getExportResponse();
        }

//        if ($grid->isReadyForRedirect()) {
//            //return new \Symfony\Component\HttpFoundation\RedirectResponse($grid->getRouteUrl());
//        }

        //return $grid->getGridResponse(['ktorzy' => $ktorzy]);
        return $grid->getGridResponse(['ktorzy' => $ktorzy]);
    }

    /**
     * @param Grid $grid
     * @param $ADUsers
     * @param $ktorzy
     * @param $roles
     *
     * @todo przenieść do osobnej klasy
     *
     *
     * @return Grid
     */
    public function getUserGrid(Grid $grid, $ADUsers, $ktorzy, $roles)
    {
        $source = new Vector($ADUsers);
        $source->setId('samaccountname');
        $grid->setSource($source);

        if (count($ADUsers) > 0) {
            //echo "<pre>"; print_r($ADUsers); die();
            $grid->hideColumns(array(
                'manager',
                //'accountDisabled',
                //'info',
                'description',
                'division',
                //            'thumbnailphoto',
                'useraccountcontrol',
                //'samaccountname',
                'initials',
                'accountExpires',
                'accountexpires',
                'email',
                'lastlogon',
                'cn',
                'distinguishedname',
                'memberOf',
                'roles',
            ));
            // Konfiguracja nazw kolumn

            $grid->getColumn('samaccountname')
                ->setTitle('Nazwa użytkownika')
                ->setOperators(array('like'))
                ->setOperatorsVisible(false)
                ->setPrimary(true);
            $grid->getColumn('name')
                ->setTitle('Nazwisko imię')
                ->setOperators(array('like'))
                ->setOperatorsVisible(false);
            $grid->getColumn('initials')
                ->setTitle('Inicjały')
                ->setOperators(array('like'))
                ->setOperatorsVisible(false);
            $grid->getColumn('title')
                ->setTitle('Stanowisko')
                ->setOperators(array('like'))
                ->setOperatorsVisible(false);
            $grid->getColumn('department')
                ->setTitle('Jednostka')
                ->setOperators(array('like'))
                ->setOperatorsVisible(false);
            $grid->getColumn('info')
                ->setTitle('Sekcja')
                ->setOperators(array('like'))
                ->setOperatorsVisible(false);
            $grid->getColumn('lastlogon')
                ->setTitle('Ostatnie logowanie')
                ->setOperators(array('like'))
                ->setOperatorsVisible(false);
            $grid->getColumn('accountexpires')
                ->setTitle('Umowa wygasa')
                ->setOperators(array('like'))
                ->setOperatorsVisible(false);
            $grid->getColumn('thumbnailphoto')
                ->setTitle('Zdj.')
                ->setFilterable(false);
            $grid->getColumn('isDisabled')
                ->setTitle('Konto wyłączone')
                ->setOperators(array('like'))
                ->setOperatorsVisible(false);
        }


        // Dodajemy kolumnę na akcje
        $actionsColumn = new ActionsColumn('akcje', 'Działania');
        $grid->addColumn($actionsColumn);

        // Zdejmujemy filtr
        $grid->getColumn('akcje')
            ->setFilterable(false)
            ->setSafe(true);


        if ($ktorzy === 'usersFromAd' || $ktorzy === 'usersFromAdFull') {
            // Edycja konta
            $rowAction2 = new RowAction('<i class="fas fa-pencil"></i> Edycja', 'userEdit');
            $rowAction2->setColumn('akcje');
            $rowAction2->setRouteParameters(
                array('samaccountname')
            );
            $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

            // Edycja konta
            $rowAction4 = new RowAction('<i class="fas fa-database"></i> Zasoby', 'resources');
            $rowAction4->setColumn('akcje');
            $rowAction4->setRouteParameters(
                array('samaccountname')
            );
            $rowAction4->addAttribute('class', 'btn btn-success btn-xs');

            //        $grid->addRowAction($rowAction1);
            $grid->addRowAction($rowAction2);
            $grid->addRowAction($rowAction4);
        } elseif ($ktorzy === 'zablokowane2') {
            $rowAction = new RowAction('<i class="fas fa-pencil"></i> Odblokuj', 'unblock_user');
            $rowAction->setColumn('akcje');
            $rowAction->setRouteParameters(
                array('samaccountname', 'ktorzy' => $ktorzy)
            );
            $rowAction->addAttribute('class', 'btn btn-success btn-xs');

            $grid->addRowAction($rowAction);
        } elseif ($ktorzy !== 'zablokowane' && $ktorzy !== 'nieobecni') {
            // Edycja konta
            $rowAction2 =
                new RowAction('<i class="fas fa-pencil"></i> Zobacz użytkownika', 'show_uncommited');
            $rowAction2->setColumn('akcje');
            $rowAction2->setRouteParameters(
                array('id')
            );
            $rowAction2->addAttribute('class', 'btn btn-success btn-xs');

            // Edycja konta
            $rowAction3 = new RowAction('<i class="fas fa-sitemap"></i> Zaangażowania', 'engageUser');
            $rowAction3->setColumn('akcje');
            $rowAction3->setRouteParameters(
                array('samaccountname')
            );
            $rowAction3->addAttribute('class', 'btn btn-success btn-xs');

            $grid->addRowAction($rowAction2);
            $grid->addRowAction($rowAction3);
        }

        if ((in_array('PARP_AZ_UPRAWNIENIA_BEZ_WNIOSKOW', $roles, true) ||
                in_array('PARP_ADMIN', $roles, true))
            && ($ktorzy !== 'zablokowane' && $ktorzy !== 'nieobecni')
        ) {
            $massAction1 =
                new MassAction(
                    'Przypisz dodatkowe zasoby',
                    'ParpMainBundle:Default:processMassAction',
                    true,
                    array('action' => 'addResources')
                );
            $grid->addMassAction($massAction1);

            $massAction2 =
                new MassAction(
                    'Odbierz prawa do zasobów',
                    'ParpMainBundle:Default:processMassAction',
                    true,
                    array('action' => 'removeResources')
                );
            $grid->addMassAction($massAction2);
        }
        if (in_array('PARP_ADMIN', $roles, true) && ($ktorzy !== 'zablokowane' && $ktorzy !== 'nieobecni')) {
            $massAction3 =
                new MassAction(
                    'Przypisz dodatkowe uprawnienia',
                    'ParpMainBundle:Default:processMassAction',
                    false,
                    array('action' => 'addPrivileges')
                );
            //$massAction3->setParameters(array('action' => 'addPrivileges', 'samaccountname' => 'samaccountname'));
            $grid->addMassAction($massAction3);
            $massAction4 =
                new MassAction(
                    'Odbierz uprawnienia',
                    'ParpMainBundle:Default:processMassAction',
                    false,
                    array('action' => 'removePrivileges')
                );
            //'ParpMainBundle:Default:processMassAction', false, array('action' => 'removePrivileges'));
            $grid->addMassAction($massAction4);
        }

        $grid->setLimits(array(20 => '20', 50 => '50', 100 => '100', 500 => '500', 1000 => '1000'));


        if ($ktorzy === 'usersFromAdFull') {
            $grid->setLimits(2000);
        }

        $grid->addExport(new ExcelExport('Eksport do pliku', 'Plik'));

        //$grid->isReadyForRedirect();


        return $grid;
    }

    /**
     * @Route("/mass", name="mass_action", defaults={"action" : ""})
     * @param string $action
     *
     * @return Response
     * @internal param null $primaryKeys
     * @internal param null $allPrimaryKeys
     * @internal param null $session
     */
    public function processMassActionAction($action = '')
    {
        if (isset($_POST)) {
            $array = array_shift($_POST);
            $actiond = '';
            if (isset($array['__action_id'])) {
                $action_id = $array['__action_id'];
            }
            if (isset($array['__action'])) {
                $actiond = $array['__action'];
            }
            $a = json_encode($actiond);
            $url =
                $this->generateUrl('addRemoveAccessToUsersAction', array('action' => $action, 'samaccountnames' => $a));
            //var_dump($a, $action, $url); die('mam posta');
            //$url = $this->generateUrl("wnioseknadanieodebraniezasobow"); //die($url);
            //$url = "/app_dev.php/wnioseknadanieodebraniezasobow/index";
            return $this->redirect($url);
            /*
            $response = $this->forward('ParpMainBundle:NadawanieUprawnienZasobow:addRemoveAccessToUsers', array(
                'action' => $action,
                'samaccountnames' => $a
            ));
            //var_dump($response); die();
            return $response;
            */
        }

        $url = $this->generateUrl('wnioseknadanieodebraniezasobow');

        return new RedirectResponse($url);
    }

    /**
     * @param $samaccountname
     *
     * @return Response
     * @internal param $samaccountName
     * @Route("/user/{samaccountname}/getphoto", name="userGetPhoto")
     */
    public function photoGetAction($samaccountname)
    {
        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname, null, null, 'wszyscyWszyscy');

        $picture = $ADUser[0]['thumbnailphoto'];

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'image/jpg');
        $response->headers->set('Content-Length', strlen($picture));
        $response->setContent($picture);

        return $response;
    }

    /**
     * @param $samaccountname
     *
     * @return array
     * @internal param $samaccountName
     * @Route("/user/{samaccountname}/photo", name="userPhoto")
     */
    public function photoAction($samaccountname)
    {
        return array(
            'account' => $samaccountname,
        );
    }

    /**
     * @Route("/show_uncommited/{id}", name="show_uncommited");
     * @param $id
     *
     * @return Response
     */
    public function showUncommitedAction($id)
    {
        $entry = $this->getDoctrine()->getManager()->getRepository(Entry::class)->find($id);

        return $this->render('ParpMainBundle:Default:show.html.twig', array(
            'entry' => $entry,
        ));
    }

    /**
     * Akcja edycji użytkownika.
     *
     * @Route("/user/{samaccountname}/edit", name="userEdit")
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW') or has_role('PARP_BZK_1')")
     *
     * @param Request $request
     * @param string $samaccountname
     *
     * @return Response
     */
    public function editAction(Request $request, string $samaccountname): Response
    {
        $samaccountname = strtolower($samaccountname);
        $entityManager = $this->getDoctrine()->getManager();

        $shortForm = true;
        if ($this->getUser()->hasRole('PARP_ADMIN_REJESTRU_ZASOBOW')) {
            $shortForm = false;
        }

        $form = $this->createForm(EdycjaUzytkownikaFormType::class, null, [
            'entity_manager' => $entityManager,
            'username' => $samaccountname,
            'form_type' => EdycjaUzytkownikaFormType::TYP_EDYCJA,
            'short_form' => $shortForm
        ]);

        $isDisabled = $form
            ->get(AdUserConstants::WYLACZONE)
            ->getData()
        ;

        if ($isDisabled) {
            $this->addFlash('info', 'Konto pracownika jest nieaktywne lub zablokowane.');

            return $this->redirectToRoute('main');
        }


        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this
                    ->addFlash('warning', 'Formularz zawiera błędy!' . (string) $form->getErrors(false, true))
                ;
            }

            if ($form->isValid()) {
                $edycjaUzytkownikaFormService = $this->get('edycja_uzytkownika_service');
                $saveEntryResult = $edycjaUzytkownikaFormService
                    ->setForm($form)
                    ->saveEditEntry()
                ;

                if (false !== $saveEntryResult) {
                    $entityManager->flush();

                    $this
                        ->addFlash('success', 'Utworzono wpis zmiany.');
                }

                if (false === $saveEntryResult) {
                    $this
                        ->addFlash('warning', 'Nie znaleziono zmian w formularzu.');
                }

                return $this->redirectToRoute('userEdit', [
                    'samaccountname' => $samaccountname
                ]);
            }
        }


        $userChanges = $entityManager
            ->getRepository(Entry::class)
            ->findUserChanges($samaccountname, true)
        ;

        $rekordData = $entityManager
            ->getRepository(DaneRekord::class)
            ->findOneBy([
                'login' => $samaccountname
            ])
        ;

        $userResources =
            $entityManager
                ->getRepository(UserZasoby::class)
                ->findZasobyUzytkownika($samaccountname);

        $viewData = array(
            'username' => $samaccountname,
            'form' => $form->createView(),
            'zasoby_uzytkownika' => $userResources,
            'user_changes' => $userChanges,
            'dane_rekord' => $rekordData
        );

        return $this->render('ParpMainBundle:Default:edit.html.twig', $viewData);
    }

    /**
     * @param $samaccountname
     * @param $ndata
     * @param $odata
     * @param $ustawUprawnieniaPoczatkowe
     * @return RedirectResponse
     */
    protected function parseUserKadry($samaccountname, $ndata, $odata, $ustawUprawnieniaPoczatkowe)
    {
        $ldap = $this->get('ldap_service');
        $diff = $this->arrayDiff($ndata, $odata);
        unset($diff['samaccountname']);
        unset($diff['initials']);
        unset($diff['title']);
        unset($diff['department']);
        unset($diff['cn']);
        unset($diff['roles']);
        unset($diff['samaccountname']);
        unset($diff['initialrights']);
        unset($diff['fromWhen']);


        //var_dump($ndata, $odata, $diff); die();
        if (count($diff) > 0) {
            $aduser = $ldap->getUserFromAD($samaccountname);
            $entry = new Entry($this->getUser()->getUsername());
            $entry->setFromWhen(new \Datetime());
            $entry->setSamaccountname($samaccountname);
            $entry->setDistinguishedName($aduser[0]['distinguishedname']);

            //zmiana sekcji
            if (isset($diff['info'])) {
                $entry->setInfo($ndata['info']);
            }
            //zmiana przelozonego
            if (isset($diff['manager'])) {
                $entry->setManager($ndata['manager']);
            }
            //data wygasniecia
            if (isset($diff['accountExpires'])) {
                $entry->setAccountExpires(new \Datetime($ndata['accountExpires']));
                if ($entry->getAccountExpires()) {
                    $entry->setAccountExpires($entry->getAccountExpires()->setTime(23, 59));
                }
            }
            //konto wylaczone
            if (isset($diff['isDisabled'])) {
                $entry->setIsDisabled($ndata['isDisabled']);
                $entry->setDisableDescription($ndata['disableDescription']);
                if ($ndata['disableDescription'] === 'Konto wyłączono z powodu rozwiązania stosunku pracy') {
                    $grupyWszystkie = $aduser[0]['memberOf'];
                    $entry->addGrupyAD($grupyWszystkie, '-');

                    $dane = [
                        'imie_nazwisko'                       => $aduser[0]['name'],
                        'login'                               => $aduser[0]['samaccountname'],
                        'tytul'                               => $aduser[0]['samaccountname'],
                        'odbiorcy'                            => [ParpMailerService::EMAIL_DO_GLPI],
                        'usuniete_int'                        => preg_grep('/^INT/i', $grupyWszystkie),
                        'usuniete_ext'                        => preg_grep('/^EXT/i', $grupyWszystkie),
                    ];
                    $this->get('parp.mailer')->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIEBI, $dane);
                }
            }
            if ($ustawUprawnieniaPoczatkowe) {
                $this->nadajUprawnieniaPoczatkowe($aduser, $entry, $ndata);
            }

            $this->getDoctrine()->getManager()->persist($entry);
            $this->getDoctrine()->getManager()->flush();
            //powod wylaczenia
            $msg = 'Zmiany wprowadzono.';
        } else {
            $msg = 'Nie było zmian do wprowadzenia.';
        }

        $this->addFlash('warning', $msg);

        return $this->redirect($this->generateUrl('userEdit', array('samaccountname' => $samaccountname)));
    }

    /**
     * @param $a1
     * @param $a2
     * @return array
     */
    public function arrayDiff($a1, $a2)
    {
        $ret = array();
        foreach ($a1 as $k => $v1) {
            if (isset($a2[$k]) && $a2[$k] != $a1[$k]) {
                $ret[$k] = $a1[$k];
            } elseif (!isset($a2[$k])) {
                $ret[$k] = $a1[$k];
            }
        }

        return $ret;
    }

    /**
     * Przywraca uprawnienia początkowe użytkownika.
     *
     * @param array $ADUser
     * @param Entry $entry
     * @param array $newData
     *
     * @return void
     */
    private function nadajUprawnieniaPoczatkowe(array $ADUser, Entry $entry, array $newData)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $departament = $ADUser[0]['description'];
        $section = $entityManager
                    ->getRepository(Section::class)
                    ->findOneBy(
                        array(
                            'shortname' => trim($ADUser[0]['division'])
                        )
                    );
        $section = ($section ? $section->getShortname() : '');

        $grupyNaPodstawieSekcjiOrazStanowiska = $ADUser[0]['memberOf'];
        $entry->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, '-');

        if (isset($newData['department'])) {
            $department = $entityManager
                    ->getRepository(Departament::class)
                    ->findOneBy(
                        array(
                        'name' => $newData['department']
                        )
                    );
            $departament = $department->getShortname();
        }

        if (isset($newData['info'])) {
            $section = $entityManager
                    ->getRepository(Section::class)
                    ->findOneBy(
                        array(
                            'name' => $newData['info']
                        )
                    );
            $section = $section->getShortname();
        }

        $ldapService = $this->container->get('ldap_service');
        $grupyNaPodstawieSekcjiOrazStanowiska = $ldapService->getGrupyUsera($ADUser[0], $departament, $section);
        $entry->addGrupyAD($grupyNaPodstawieSekcjiOrazStanowiska, '+');
    }

    /**
     * @param $newData
     * @param $entry
     */
    public function parseUserFormData($newData, &$entry)
    {
        foreach ($newData as $key => $value) {
            switch ($key) {
                case 'isDisabled':
                    $entry->setIsDisabled($value);
                    break;
                case 'disableDescription':
                    $entry->setDisableDescription($value);
                    break;
                case 'name':
                    $entry->setCn($value);
                    break;
                case 'initials':
                    $entry->setInitials($value);
                    break;
                case 'accountExpires':
                    if ($value) {
                        $entry->setAccountexpires(new \DateTime($value));
                        if ($entry->getAccountExpires()) {
                            $entry->setAccountExpires($entry->getAccountExpires()->setTime(23, 59));
                        }
                    } else {
                        $entry->setAccountexpires(new \DateTime('3000-01-01 00:00:00'));
                    }

                    break;
                case 'title':
                    $entry->setTitle($value);
                    break;
                case 'info':
                    $entry->setInfo($value);
                    break;
                case 'department':
                    $entry->setDepartment($value);
                    break;
                case 'manager':
                    $entry->setManager($value);
                    break;
                case 'fromWhen':
                    $entry->setFromWhen(new \DateTime($value));
                    break;
                case 'initialrights':
                    $value = implode(',', $value);
                    $entry->setInitialrights($value);

                    break;
            }
        }
    }

    /**
     * @param Form $form
     * @return array
     */
    private function getErrorMessages(Form $form)
    {
        $errors = array();

        foreach ($form->getErrors() as $key => $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    /**
     * @return false|string
     */
    protected function generateGUID()
    {
        return date('YmdhIs');
    }

    /**
     * @Route("/user/add", name="userAdd")
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW') or has_role('PARP_BZK_1')")
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     * @throws SecurityTestException
     */
    public function addAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $form = $this->createForm(EdycjaUzytkownikaFormType::class, null, [
            'entity_manager' => $entityManager,
            'username' => '',
            'form_type' => EdycjaUzytkownikaFormType::TYP_NOWY,
            'short_form' => false
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $edycjaUzytkownikaFormService = $this->get('edycja_uzytkownika_service');
                $saveEntryResult = $edycjaUzytkownikaFormService
                    ->setForm($form)
                    ->saveNewEntry()
                ;

                if (!$saveEntryResult->hasErrors()) {
                    $entityManager->flush();
                    $this
                        ->addFlash('sucess', 'Utworzono użytkownika');

                    return $this->redirectToRoute('main');
                }

                $this
                    ->addFlash('danger', 'Wystąpił błąd. ' . $saveEntryResult->getErrorsAsString());
            }
        }


        return $this->render(
            'ParpMainBundle:Default:add.html.twig',
            array('form' => $form->createView(), 'guid' => $this->generateGUID())
        );
    }

    /**
     * @Route("/structure/{samaccountname}", name="structure")
     * @Template()
     * @param $samaccountname
     *
     * @return array
     */
    public function structureAction($samaccountname)
    {
        $ldap = $this->get('ldap_service');
        // Pobieramy naszego pracownika
        $ADUser = $ldap->getUserFromAD($samaccountname);

        // Prezes nie ma przełożonego...
        if ($ADUser[0]['title'] === 'p.o. prezesa' ||
            $ADUser[0]['title'] === 'prezes') {
            $ADManager = '';
        } else {
            // Pobieramy przełożonego
            $ADManager = $ldap->getPrzelozony($samaccountname);
        }

        // Pobieramy wszystkich jego pracowników (w których występuje jako przełożony)
        $ADWorkers = $ldap->getUserFromAD(null, null, 'manager='.$ADUser[0]['distinguishedname'].'');

        return array(
            'przelozony' => $ADManager,
            'pracownik'  => $ADUser[0],
            'pracownicy' => $ADWorkers,
        );
    }

    /**
     * @Route("/engage/{samaccountname}/{rok}", name="engageUser")
     * @Route("/engage/{samaccountname}", name="engageUser")
     * @param Request $request
     * @param         $samaccountname
     * @Template()
     * @return array|RedirectResponse
     */
    public function engagementAction(Request $request, $samaccountname)
    {
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname);
        $engagements = $this->getDoctrine()->getRepository(Engagement::class)->findAll();
        $userEngagements =
            $this->getDoctrine()
                ->getRepository(UserEngagement::class)
                ->findBy(array('samaccountname' => $samaccountname));

        $em = $this->getDoctrine()->getManager();

        $year = $request->query->get('year');

        if (empty($year)) {
            $date = new \DateTime();
            $year = $date->format('Y');
        }

        if ($request->getMethod() === 'POST') {
            $request = $this
                ->get('request_stack')
                ->getCurrentRequest()
            ;
            $dane = $request->request->all();
            //var_dump($dane);
            $year = !empty($dane['year']) ? $dane['year'] : $year;

            foreach ($dane['angaz'] as $key_angaz => $value_angaz) {
                // var_dump($key_angaz);
                $engagement = $em->getRepository(Engagement::class)->findOneBy(array('name' => $key_angaz));

                $last_value_angaz = '';
                $last_month = null;
                //petla po miesiacach
                foreach ($value_angaz as $key_month => $value_month) {
                    $userEngagement =
                        $em->getRepository(UserEngagement::class)
                            ->findOneByCryteria(
                                $samaccountname,
                                $engagement->getId(),
                                $this->getMonthFromStr($key_month),
                                $year
                            );

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
                        $userEngagement->setPercentFormat($last_month);
                        $userEngagement->setCzyNowy(true);

                        $em->persist($userEngagement);
                        $em->flush();
                    } else {
                        //$percent = (!empty($value_month)) ? $value_month : null;
                        //$userEngagement->setPercent($percent);
                        //$userEngagement->setPercent($last_month);
                        //$em->persist($userEngagement);
                        //$em->flush();

                        if ((int) $userEngagement->getPercentFormat() !== (int) ($last_month)) {
                            $ue = clone $userEngagement;
                            $ue->setId(null);
                            $ue->setCzyNowy(true);
                            $ue->setPercentFormat($last_month);

                            $userEngagement->setCzyNowy(false);
                            $userEngagement->setKiedyUsuniety(new \DateTime());
                            $userEngagement->setKtoUsunal($this->getUser()->getUsername());

                            $em->persist($ue);
                            $em->persist($userEngagement);
                        }

                        $em->flush();
                    }
                }
            }

            return $this->redirect($this->generateUrl(
                'engageUser',
                array('samaccountname' => $samaccountname, 'year' => $year)
            ));
        }

        $userEngagementsRepo = $em->getRepository(UserEngagement::class);
        $userEngagements = $userEngagementsRepo->findBySamaccountnameAndYear($samaccountname, $year);

        $dane = array();
        foreach ($userEngagements as $userEngagement) {
            //echo $userEngagement->getSamaccountname() . ' ' . $userEngagement->getEngagement() . ' ' . $userEngagement->getPercent() . ' ' . $userEngagement->getMonth() . ' ' . $userEngagement->getYear() . "<br>";
            // zbuduj tablice z danymi
            $engagement = (string) $userEngagement->getEngagement();
            $month = $this->getStrFromMonth($userEngagement->getMonth());
            $percent = $userEngagement->getPercentFormat();
            $dane[$engagement][$month]['procent'] = $percent;

            $dane[$engagement][$month]['historia'] = $userEngagementsRepo->findOneNieaktywneByCryteria(
                $samaccountname,
                $userEngagement->getEngagement()->getId(),
                $userEngagement->getMonth(),
                $userEngagement->getYear()
            );
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
            $percent = $userEngagement->getPercentFormat();
            $percent = !empty($percent) ? $percent : 0;

            switch ($month) {
                case 'sty':
                    $sumy['sumSty'] += $percent;
                    break;
                case 'lut':
                    $sumy['sumLut'] += $percent;
                    break;
                case 'mar':
                    $sumy['sumMar'] += $percent;
                    break;
                case 'kwi':
                    $sumy['sumKwi'] += $percent;
                    break;
                case 'maj':
                    $sumy['sumMaj'] += $percent;
                    break;
                case 'cze':
                    $sumy['sumCze'] += $percent;
                    break;
                case 'lip':
                    $sumy['sumLip'] += $percent;
                    break;
                case 'sie':
                    $sumy['sumSie'] += $percent;
                    break;
                case 'wrz':
                    $sumy['sumWrz'] += $percent;
                    break;
                case 'paz':
                    $sumy['sumPaz'] += $percent;
                    break;
                case 'lis':
                    $sumy['sumLis'] += $percent;
                    break;
                case 'gru':
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
        //var_dump($sumy);
        //die();
        return array(
            'engagements'     => $engagements,
            'userEngagements' => $userEngagements,
            'samaccountname'  => $samaccountname,
            'user'            => $ADUser[0],
            'dane'            => $dane,
            'year'            => $year,
            'sumy'            => $sumy,
            //            'form' => $form->createView(),
        );
    }

    /**
     * @param $month
     * @return mixed
     */
    protected function getMonthFromStr($month)
    {
        $tab = array(
            'sty' => 1,
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
            'gru' => 12,
        );

        return $tab[$month];
    }

    /**
     * @param $month
     * @return mixed
     */
    protected function getStrFromMonth($month)
    {
        $tab = array(
            1 => 'sty',
            2 => 'lut',
            3 => 'mar',
            4 => 'kwi',
            5 => 'maj',
            6 => 'cze',
            7 => 'lip',
            8 => 'sie',
            9 => 'wrz',
            10 => 'paz',
            11 => 'lis',
            12 => 'gru',
        );

        return $tab[$month];
    }

    /**
     * @Route("/suggestinitials", name="suggest_initials", options={"expose"=true})
     * @param Request $request
     *
     * @return null|Response
     */
    public function ajaxSuggestInitials(Request $request)
    {
        $post = ($request->getMethod() === 'POST');
        $ajax = $request->isXmlHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
        if ((!$ajax) or (!$post)) {
            return null;
        }
        $p = explode(' ', $request->get('cn'));
        $initials = substr($p[1], 0, 1).substr($p[0], 0, 1);
        /*

        $samaccountname = $request->get('samaccountname', null);
        if (empty($samaccountname)) {
            throw new \Exception('Nie przekazano nazwy konta!');
        }
        $department = $request->get('department', null);
        $cn = $request->get('samaccountname', null);

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
        //print_r($temp0);
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
*/
        $initials = mb_strtoupper($initials, 'UTF-8');

        return $this->render('ParpMainBundle:Default:suggestinitials.html.twig', array('initials' => $initials));
    }

    /**
     * @Route("/findmanager", name="find_manager", options={"expose"=true})
     * @param Request $request
     *
     * @return Response
     * @throws \Exception
     */
    public function ajaxFindManager(Request $request)
    {

        $post = ($request->getMethod() === 'POST');
        $ajax = $request->isXmlHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
        if ((!$ajax) or (!$post)) {
            throw new MethodNotAllowedHttpException(['POST'], 'Dopuszczalne tylko POST oraz wywołanie AJAX');
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
            if (mb_stripos($user['name'], $imienazwisko, 0, 'UTF-8') !== false) {
                $dane[$i] = $user['name'];
                $i++;
            }
        }

        return $this->render('ParpMainBundle:Default:findmanager.html.twig', array('dane' => $dane));
    }

    /**
     * @Route("/file_ecm", name="form_file_ecm")
     * @Template()
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function formFileEcmAction(Request $request)
    {

        $form = $this->createFormBuilder()->add('plik', FileType::class, array(
            'required'    => false,
            'label_attr'  => array(
                'class' => 'col-sm-4 control-label',
            ),
            'attr'        => array(
                'class'             => 'filestyle',
                'data-buttonBefore' => 'false',
                'data-buttonText'   => 'Wybierz plik',
                'data-iconName'     => 'fas fa-file-excel-o',
            ),
            'constraints' => array(
                new NotBlank(array('message' => 'Nie wybrano pliku')),
                new File(array(
                    'maxSize'          => 1024 * 1024 * 10,
                    'maxSizeMessage'   => 'Przekroczono rozmiar wczytywanego pliku',
                    'mimeTypes'        => array(
                        'text/csv',
                        'text/plain',
                        'application/vnd.ms-excel',
                        'application/msexcel',
                        'application/xls',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ),
                    'mimeTypesMessage' => 'Niewłaściwy typ plku. Proszę wczytac plik z rozszerzeniem csv',
                )),
            ),
            'mapped'      => false,
        ))
            ->add(
                'rok',
                TextType::class,
                array('required' => false, 'label' => 'Przy imporcie zaangażowań podaj rok', 'data' => date('Y'))
            )
            ->add('wczytaj', SubmitType::class, array(
                'attr' => array(
                    'class' => 'btn btn-success col-sm-12',
                ),
            ))
            ->getForm();

        $form->handleRequest($request);
        if ($request->getMethod() === 'POST') {
            if ($form->isValid()) {
                $file = $form->get('plik')->getData();
                $name = $file->getClientOriginalName();

                //$path = $file->getClientPathName();
                //var_dump($file->getPathname());
                // var_dump($name);
                $ret = $this->wczytajPlik($file);
                if ($ret) {
                    $msg = 'Plik został wczytany poprawnie.';
                    if (is_array($ret)) {
                        $msg = 'Plik został wczytany poprawnie. ';
                        $w = array();
                        foreach ($ret as $k => $v) {
                            $w[] = "$k : $v";
                        }
                        $msg .= implode(', ', $w);
                    }
                    $this->addFlash('warning', $msg);

                    return $this->redirect($this->generateUrl('main'));
                }
            }
        }

        return $this->render('ParpMainBundle:Default:formfileecm.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param $file
     * @return array|bool
     */
    protected function wczytajPlik($file)
    {
        $dane = file_get_contents($file->getPathname());
        // $xxx = iconv('windows-1250', 'utf-8', $dane );

        $ext = $file->guessExtension();
        //print_r($ext);die();

        if ($ext === 'xlsx') {
            $ret = $this->wczytajPlikZaangazowania($file->getPathname());
        } else {
            $list = explode("\n", $dane);
            $ldap = $this->get('ldap_service');

            $em = $this->getDoctrine()->getManager();

            //!!! tego sie pozbywam
            //$query = $em->createQuery('delete from ParpV1\MainBundle\Entity\UserZasoby');
            //$numDeleted = $query->execute();

            $pierwszyWiersz = explode(';', $list[0]);
            $komorka = $pierwszyWiersz[0];
            //print_r($komorka); die();
            if ($komorka === 'Nazwa zasobu') {
                $ret = $this->wczytajPlikZasoby($file);
            } else {
                $ret = $this->wczytajPlikZasobyUser($file);
            }
        }

        return $ret;
    }

    /**
     * @param $file
     * @return bool
     */
    protected function wczytajPlikZaangazowania($file)
    {
        $requestStack = $this->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        $rok = $request->request->all()['form']['rok'];
        $this->ADUsers = $this->get('ldap_service')->getAllFromAD();

        $dane = array();

        $phpExcelObject = new \PHPExcel(); //$this->get('phpexcel')->createPHPExcelObject();
        //$file = $this->get('kernel')->getRootDir()."/../web/uploads/import/membres.xlsx";
        if (!file_exists($file)) {
            //exit("Please run 05featuredemo.php first." );
            die('nie ma pliku');
        }
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        //$EOL = "\r\n";
        //echo date('H:i:s') , " Iterate worksheets" , $EOL;
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

        $progs = $this->get('doctrine')->getManager()->getRepository(Engagement::class)->findAll();
        $programy = array();
        foreach ($progs as $p) {
            $programy[$p->getName()] = $p;
        }

        $userdane = array();
        foreach ($sheetData as $row) {
            //pomijamy pierwszy rzad
            if ($row['A'] !== 'D/B') {
                $pr = trim($row['F']);
                $userdane[$row['B']][$pr] = $this->getMonthsFromRow($row);
            }
        }
        //print_r($programy);
        $ret = array();
        foreach ($userdane as $user => $angaz) {
            $u = $this->findUserByName($user);
            if ($u == null) {
                $this->addFlash('notice', 'Pomijam usera "'.$user.'" bo go nie ma w systemie');
            } else {
                foreach ($angaz as $prog => $year) {
                    if (!isset($programy[$prog])) {
                        $this->addFlash('notice', 'Pomijam program "'.$prog.'" bo go nie ma w systemie');
                    } else {
                        //$pid = $programy[$prog]->getId();
                        foreach ($year as $m => $proc) {
                            $pars = array(
                                'samaccountname' => $u['samaccountname'],
                                //'percent' => $proc*100,
                                'engagement'     => $programy[$prog],
                                'month'          => $m,
                                'year'           => $rok,
                            );
                            $ue =
                                $this->get('doctrine')
                                    ->getManager()
                                    ->getRepository(UserEngagement::class)
                                    ->findOneBy($pars);
                            if ($ue == null) {
                                //print_r($pars);
                                //die('a');
                                $ue = new UserEngagement();
                                $this->get('doctrine')->getManager()->persist($ue);
                            }

                            $ue->setSamaccountname($pars['samaccountname']);
                            $ue->setPercent($proc * 100);
                            $ue->setEngagement($programy[$prog]);
                            $ue->setMonth($pars['month']);
                            $ue->setYear($pars['year']);

                            $ret[] = $pars;
                        }
                    }
                }
            }
        }
        $this->get('doctrine')->getManager()->flush();

        //die();
        return true;
        //print_r($ret); die();
        /*

                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
                    //echo 'Worksheet - ' , $worksheet->getTitle() , $EOL;
                    foreach ($worksheet->getRowIterator() as $row) {
                        //echo '    Row number - ' , $row->getRowIndex() , $EOL;
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                        foreach ($cellIterator as $cell) {
                            if (!is_null($cell)) {
                                //echo '        Cell - ' , $cell->getCoordinate() , ' - ' , $cell->getCalculatedValue() , $EOL;
                            }
                        }
                    }
                }
        */
    }

    /**
     * @param $row
     * @return array
     */
    protected function getMonthsFromRow($row)
    {
        $months = array();
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = array();
            $val = trim($row[$this->col2month[$i - 1]]);
            $val = $val === '' ? 0 : floatval($val);
            $months[$i] = $val;
        }

        return $months;
    }

    /**
     * @param $imienazwisko
     * @return mixed
     */
    protected function findUserByName($imienazwisko)
    {
        foreach ($this->ADUsers as $user) {
            if (mb_stripos($user['name'], $imienazwisko, 0, 'UTF-8') !== false) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @param $file
     * @return bool
     */
    protected function wczytajPlikZasoby($file)
    {
        //$dane = file_get_contents($file->getPathname());

        $handle = fopen($file->getPathname(), 'r');
        $ldap = $this->get('ldap_service');

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('delete from ParpV1\MainBundle\Entity\UserZasoby');
        $numDeleted = $query->execute();
        $wiersz2getter = array(
            1  => 'WlascicielZasobu',
            2  => 'AdministratorZasobu',
            3  => 'AdministratorTechnicznyZasobu',
            4  => 'Uzytkownicy',
            5  => 'DaneOsobowe',
            6  => 'KomorkaOrgazniacyjna',
            7  => 'MiejsceInstalacji',
            8  => 'OpisZasobu',
            9  => 'ModulFunkcja',
            10 => 'PoziomDostepu',
            11 => 'DataZakonczeniaWdrozenia',
            12 => 'Wykonawca',
            13 => 'NazwaWykonawcy',
            14 => 'AsystaTechniczna',
            15 => 'DataWygasnieciaAsystyTechnicznej',
            16 => 'DokumentacjaFormalna',
            17 => 'DokumentacjaProjektowoTechniczna',
            18 => 'Technologia',
            19 => 'TestyBezpieczenstwa',
            20 => 'TestyWydajnosciowe',
            21 => 'DataZleceniaOstatniegoPrzegladuUprawnien',
            22 => 'InterwalPrzegladuUprawnien',
            23 => 'DataZleceniaOstatniegoPrzegladuAktywnosci',
            24 => 'InterwalPrzegladuAktywnosci',
            25 => 'DataOstatniejZmianyHaselKontAdministracyjnychISerwisowych',
            26 => 'InterwalZmianyHaselKontaAdministracyjnychISerwisowych',
        );
        $tablica = array();
        $out = $this->poprawPlikCsv($file);
        $out = iconv('windows-1250', 'utf-8', $out);
        $list = explode("\n", $out);
        foreach ($list as $wiersz) {
            //while ( ($wiersz = fgetcsv($handle, 0, ";", '"') ) !== FALSE ) {
            // ostatni wiersz w pliku może być pusty!
            /*
                        if($wiersz[0] == "CMS-EXPO"){
                            print_r($wiersz); die();
                            }
            */
            if (!empty($wiersz[0])) {
                //echo $wiersz ."\n";

                //$wiersz = $wiersz[0];//$wiersz = iconv('cp1250', 'utf-8//IGNORE', $wiersz);
                //print_r($wiersz);
                $dane = explode(';', $wiersz);//$wiersz;//
                //print_r($dane); die();
                if ($dane[1] !== '' && $dane[1] !== '') {
                    // znajdz zasob
                    $zasob =
                        $this->getDoctrine()->getRepository(Zasoby::class)->findOneBy(['name' => trim($dane[0])]);
                    if (!$zasob) {
                        //echo "nie znaleziono $dane[2] " . "<br>";
                        //nie rób nic na razie
                        $zasob = new Zasoby();
                        $zasob
                            ->setOpis(trim($dane[8]))
                            ->setBiuro(trim($dane[6]))
                            ->setNazwa(trim($dane[0]))
                        ;
                    }
                    foreach ($dane as $k => $v) {
                        $v = trim($v);
                        //echo ".".$v.".";
                        if ($k >= 1 && $v !== '' && $k < 27) {
                            $setter = $wiersz2getter[$k];
                            if (strstr($setter, 'Data') !== false) {
                                //echo " <br>.".$value['dane'][1]." ".$value['dane'][2]." ".$v.".";
                                $v = \DateTime::createFromFormat('D M d H:i:s e Y', $v);
                                //print_r($v);
                                //die();
                            }
                            if ($v) {
                                $zasob->{'set'.$setter}($v);
                            }
                        }
                    }
                    $em->persist($zasob);
                }
            }
        }

        $em->flush();

        return true;
    }

    /**
     * @param $file
     * @return string
     */
    public function poprawPlikCsv($file)
    {
        $dane = file_get_contents($file->getPathname());
        // $xxx = iconv('windows-1250', 'utf-8', $dane );

        $list = explode("\n", $dane);
        $out = '';
        $buffer = '';
        $inTheMiddle = false;
        foreach ($list as $line) {
            $c = substr_count($line, '"');
            if ($c % 2 == 1) {
                if ($inTheMiddle) {
                    $buffer .= $line;
                    $out .= $buffer."\n";
                    $inTheMiddle = false;
                    $buffer = '';
                } else {
                    $inTheMiddle = true;
                    //$buffer = "";
                    $buffer = $line."\\n";
                }
            } elseif ($inTheMiddle) {
                $buffer .= $line."\\n";
            } else {
                $out .= $line."\n";
            }
        }//die($out);
        return $out;
    }

    /**
     * @param $file
     * @return array
     */
    protected function wczytajPlikZasobyUser($file)
    {
        $wynik = array('utworzono' => 0, 'zmieniono' => 0, 'nie zmieniono' => 0, 'skasowano' => 0);
        $dane = file_get_contents($file->getPathname());
        $zamianaSlownikaKanalDostepu = array(
            'DZ_O - Zdalny, za pomocą komputera nie będącego własnością PARP' => 'DZ_O - Zdalny - za pomocą komputera nie będącego własnością PARP',
            'DZ_P - Zdalny, za pomocą komputera będącego własnością PARP'     => 'DZ_P - Zdalny - za pomocą komputera będącego własnością PARP',
            'WK - Wewnętrzny kablowy'                                         => 'WK - Wewnętrzny kablowy',
            'WR - Wewnętrzny radiowy'                                         => 'WR - Wewnętrzny radiowy',
            'WRK - Wewnętrzny radiowy i kablowy'                              => 'WRK - Wewnętrzny radiowy i kablowy',
        );
        // $xxx = iconv('windows-1250', 'utf-8', $dane );

        foreach ($zamianaSlownikaKanalDostepu as $f => $r) {
            $dane = str_replace(iconv('utf-8//IGNORE', 'cp1250', $f), iconv('utf-8//IGNORE', 'cp1250', $r), $dane);
        }
        $list = explode("\n", $dane);
        $list = $this->parseMultiRowsUserZasoby($list);
        //print_r($list); die();
        $ldap = $this->get('ldap_service');

        $em = $this->getDoctrine()->getManager();
        //$query = $em->createQuery('delete from ParpV1\MainBundle\Entity\UserZasoby uz where uz.importedFromEcm = 1');
        //$numDeleted = $query->execute();
        $wiersz2getter = array(
            3  => 'LoginDoZasobu',
            4  => 'Modul',
            5  => 'PoziomDostepu',
            6  => 'AktywneOd',
            7  => 'Bezterminowo',
            8  => 'AktywneDo',
            9  => 'KanalDostepu',
            10 => 'UprawnieniaAdministracyjne',
            11 => 'OdstepstwoOdProcedury',
        );
        $tablica = array();
        foreach ($list as $wiersz) {
            // ostatni wiersz w pliku może być pusty!
            if (!empty($wiersz)) {
                //echo $wiersz ."\n";

                $wiersz = iconv('cp1250', 'utf-8//IGNORE', $wiersz);

                $dane = explode(';', $wiersz);
                if ($dane[1] !== '' && $dane[1] !== '') {
                    $cnname = $this->ldapEscape($dane[1]).'*'.$this->ldapEscape($dane[0]);
                    //echo ".".$wiersz.".<br>";
                    $ADUser = $ldap->getUserFromAD(null, $cnname);
                }
                if ($dane[1] !== '' && $dane[1] !== '' && !empty($ADUser)) {
                    // znajdz zasob
                    $zasob =
                        $this->getDoctrine()->getRepository(Zasoby::class)->findOneBy(['name' => trim($dane[2])]);
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
                        $dane['zasobId'] = $zasobid;
                        if (array_key_exists($samaccountname, $tablica)) {
                            $klucz = $tablica[$samaccountname];
                            if (!in_array($zasobid, $klucz, true)) {
                                $tablica[$samaccountname][] = array('zasobId' => $zasobid, 'dane' => $dane);
                            }
                        } else {
                            $tablica[$samaccountname][] = array('zasobId' => $zasobid, 'dane' => $dane);
                        }
                    }
                }
            }
        }
        $pomijacKolumnyPrzyPorownaniu = array(3, 4, 5, 7, 9, 10, 11);
        $uzsIdPokryteWimporcie = array();


        //print_r($tablica); die();
        foreach ($tablica as $samaccountname => $values) {
            $samsUserZasoby =
                $this->getDoctrine()
                    ->getRepository(UserZasoby::class)
                    ->findByAccountnameAndEcm($samaccountname);
            foreach ($values as $value) {
                //echo $key . ' ' . $value . "<br>";

                //echo "<br><br><br>szukam DUBLA $samaccountname ".$value['zasobId']."<br><br><br>";
                $newUserZasob = null;
                //szukam czy istnieje taki zasob
                foreach ($samsUserZasoby as $uzs) {
                    //uznajemy ze to ten sam userZasoby jesli rowne sa: (samaccountname) zasobId, poziomDostepu, aktywneOd i aktywneDo, kanalDostepu

                    $equal = $uzs->getZasobId() == $value['zasobId'];
                    if ($equal) {
                        foreach ($wiersz2getter as $col => $getter) {
                            if (!in_array($col, $pomijacKolumnyPrzyPorownaniu, true)) {
                                $val = $uzs->{'get'.$getter}();
                                if ($col == 6 || $col == 8) {
                                    $val = $val->format('D M d H:i:s T Y');
                                }
                                $equal = $equal && ($val == trim($value['dane'][$col]));
                                //echo "<br>porownuje $getter <br>.".$val .".<br>.". $value['dane'][$col].".<br> wynik ".($val == $value['dane'][$col])." ".$equal."<br>";
                            }
                        }
                    }
                    if ($equal) {
                        //echo('mam dubla');
                        $newUserZasob = $uzs;
                        $uzsIdPokryteWimporcie[] = $uzs->getId();
                        $wynik['nie zmieniono'] += 1;
                    }
                }
                if ($newUserZasob == null) {
                    //echo "<br><br><br>NIE MAM DUBLA <br><br><br>";

                    $newUserZasob = new UserZasoby();
                    $newUserZasob->setImportedFromEcm(true);
                    $newUserZasob->setAktywneOd(null);
                    $newUserZasob->setAktywneDo(null);
                    $newUserZasob->setCzyAktywne(true);
                    $newUserZasob->setPowodNadania('na podstawie wniosku z ECM-PARP');
                    $newUserZasob->setSamaccountname($samaccountname);
                    $newUserZasob->setZasobId($value['zasobId']);
                    foreach ($value['dane'] as $k => $v) {
                        $v = trim($v);
                        if ($k >= 3 && $v !== '') {
                            $setter = $wiersz2getter[$k];
                            if ($k == 6 || $k == 8) {
                                //echo " <br>.".$value['dane'][1]." ".$value['dane'][2]." ".$v.".";
                                $v = \DateTime::createFromFormat('D M d H:i:s T Y', $v);
                                //print_r($v);
                                //die();
                            }
                            if ($v) {
                                $newUserZasob->{'set'.$setter}($v);
                            }
                        }
                    }
                    $wynik['utworzono'] += 1;
                }
                $em->persist($newUserZasob);
            }
        }

        foreach ($samsUserZasoby as $uzs) {
            if (!in_array($uzs->getId(), $uzsIdPokryteWimporcie, true)) {
                $uzs->setPowodOdebrania('na podstawie wniosku z ECM-PARP');
                $em->remove($uzs);
                $wynik['skasowano'] += 1;
            }
        }
        $em->flush();

        return $wynik;
    }

    /**
     * @param $list
     * @return array
     */
    protected function parseMultiRowsUserZasoby($list)
    {
        $ret = array();
        $multirows = array(5, 6, 7, 8, 9);
        foreach ($list as $wiersz) {
            //$wiersz = iconv('cp1250', 'utf-8//IGNORE', $wiersz);
            $dane = explode(';', $wiersz);
            $rowcount = 0;
            foreach ($multirows as $r) {
                $rowcount = substr_count($dane[$r], ',') > $rowcount ? substr_count($dane[$r], ',') : $rowcount;
            }
            //die(".".$multirow);
            if ($rowcount > 0) {
                //ąecho $wiersz."<br>";
                for ($i = 0; $i < $rowcount + 1; $i++) {
                    $nd = $dane;
                    foreach ($multirows as $r) {
                        //echo "<br>".$r." ".$i."<br>";
                        $v = explode(',', $dane[$r]);
                        $nd[$r] = $v[$i];
                    }
                    $ret[] = implode(';', $nd);
                }
            } else {
                $ret[] = $wiersz;
            }
        }

        return $ret;
    }

    /**
     * @param $subject
     * @param bool $dn
     * @param null $ignore
     * @return mixed|string
     */
    protected function ldapEscape($subject, $dn = false, $ignore = null)
    {

        // The base array of characters to escape
        // Flip to keys for easy use of unset()
        $search =
            array_flip($dn ? array('\\', ',', '=', '+', '<', '>', ';', '"', '#') : array('\\', '*', '(', ')', "\x00"));

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
            if ($result[0] === ' ') {
                $result = '\\20'.substr($result, 1);
            }
            if ($result[strlen($result) - 1] === ' ') {
                $result = substr($result, 0, -1).'\\20';
            }
        }

        return $result;
    }

    /**
     * @Route("/resources/{samaccountname}", name="resources")
     * @param $samaccountname
     *
     * @return Response
     */
    public function showResourcesAction($samaccountname)
    {
        // Sięgamy do AD:
        $ldap = $this->get('ldap_service');
        $uprawnieniaService = $this->get('uprawnienia_service');

        $ADUser = $ldap->getUserFromAD($samaccountname);

        // Pobieramy listę zasobow
        $userZasoby =
            $this
                ->getDoctrine()
                ->getRepository(UserZasoby::class)
                ->findNameByAccountname($samaccountname);

        if (in_array('PARP_ADMIN', $this->getUser()->getRoles(), true)) {
            $i = 0;
            foreach ($userZasoby as $zasob) {
                $userZasoby[$i]['poziomDostepuNapraw'] = $uprawnieniaService->sprawdzPrawidlowoscPoziomuDostepu($zasob['poziomDostepu'], $zasob['zid'], true);
                $i++;
            }
        }

        return $this->render(
            'ParpMainBundle:Default:resources.html.twig',
            array('user' => $ADUser[0]['name'], 'zasoby' => $userZasoby)
        );
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     * @Route("/user/suggest/", name="userSuggest")
     */
    public function userSuggestAction(Request $request)
    {

        $post = ($request->getMethod() === 'POST');
        $ajax = $request->isXmlHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
        /*
                if ((!$ajax) OR ( !$post)) {
                    return null;
                }
        */

        $imienazwisko = $request->get('term', null);
        if (empty($imienazwisko)) {
            throw new \Exception('Nie przekazano imienia i nazwiska!');
        }

        $ldap = $this->get('ldap_service');

        $ADUsers = $ldap->getAllFromAD();

        $dane = array();
        $i = 0;
        foreach ($ADUsers as $user) {
            if (mb_stripos($user['name'], $imienazwisko, 0, 'UTF-8') !== false) {
                //$dane[$i] = $user['name'];
                $dane[$i] = $user['name'];//$this->get('renameService')->fixImieNazwisko($user['name']);
                $i++;
            }
        }

        //$vals = array("Kamil Jakacki", "Kamamamama", "Costam");
        $term = json_encode($dane);
        die($term);
    }


    /**
     * @param Request $request
     *
     * @throws \Exception
     * @internal param $term
     * @Route("/user/suggestLogin/", name="userSuggestLoginAction", options={"expose"=true})
     */
    public function userSuggestLoginAction(Request $request)
    {
        $post = ($request->getMethod() === 'POST');
        $ajax = $request->isXmlHttpRequest();

        // Sprawdzenie, czy akcja została wywołana prawidłowym trybem.
        /*
                if ((!$ajax) OR ( !$post)) {
                    return null;
                }
        */

        $imienazwisko = $request->get('name', null);
        if (empty($imienazwisko)) {
            throw new \Exception('Nie przekazano imienia i nazwiska!');
        }
        $parts = $this->get('samaccountname_generator')->ADnameToRekordNameAsArray($imienazwisko);
        $login = $this->get('samaccountname_generator')->generateSamaccountname($parts[1], $parts[0], true);
        die($login);
    }

    /**
     * Usuwa zbędny wpis ze zmian oczekujących na implementację do AD
     *
     * @param int $id
     *
     * @Route("/delete_pending/{id}", name="delete_pending")
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW') or has_role('PARP_BZK_1')")
     *
     * @return RedirectResponse
     *
     * @throws EntityNotFoundException
     */
    public function deletePendingAction($id): Response
    {
        $entityManager = $this
            ->getDoctrine()
            ->getManager()
        ;
        $entry = $entityManager
            ->getRepository(Entry::class)
            ->find($id)
        ;

        if (null === $entry) {
            throw new EntityNotFoundException('Nie ma takiego wpisu w bazie.');
        }

        if ($entry->getIsImplemented()) {
            $this->addFlash('danger', 'Nie można usunąć zaimplementowanej zmiany.');

            return $this->redirectToRoute('userEdit', ['samaccountname' => $entry->getSamaccountname()]);
        }

        $entityManager->remove($entry);
        $entityManager->flush();

        $this->addFlash('danger', 'Usunięto oczekujący wpis');

        return $this->redirectToRoute('userEdit', ['samaccountname' => $entry->getSamaccountname()]);
    }

    /**
     * Cofa wpis do problematycznych.
     *
     * @Route("/cofnij_do_problematycznych/{id}", name="cofnij_do_problematycznych")
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW') or has_role('PARP_BZK_1')")
     *
     * @throws EntityNotFoundException Nie ma takiego wpisu w bazie.
     *
     * @param Entry|null $entry
     *
     * @return Response
     *
     */
    public function cofnijDoProblematycznych(?Entry $entry = null): Response
    {
        if (null === $entry) {
            throw new EntityNotFoundException('Nie ma takiego wpisu w bazie.');
        }

        $daneRekord = $entry->getDaneRekord();
        $daneRekord
            ->setNewUnproccessed($daneRekord->getStaticStatusNumber())
        ;

        $entityManager = $this
            ->getDoctrine()
            ->getManager()
        ;
        $entityManager->persist($daneRekord);
        $entityManager->remove($entry);
        $entityManager->flush();

        $this->addFlash('danger', sprintf('Cofnięto użytkownika do problematycznych (ID: %s)', $daneRekord->getId()));

        return $this->redirectToRoute('przejrzyjnowych');
    }

    /**
     * Zwraca użytkowników z AD z grupami uprawnień (na potrzeby śledzenia zmian w AD).
     *
     * @Route("/usersAdData/{showall}", defaults={"showall" : 0})
     *
     * @param boolean $showall
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getUsersAdDataAction($showall)
    {
        $ldap = $this->container->get('ldap_service');

        if ($showall) {
            $usersFromAD = $ldap->getAllFromAD('wszyscy', false, null, true);
        } else {
            $usersFromAD = $ldap->getAllFromAD(false, false, null, true);
        }

        if (empty($usersFromAD)) {
            return new Json404NotFoundResponse('Nie znaleziono użytkowników.');
        }

        $users = [];
        foreach ($usersFromAD as $user) {
            $samaccountname = $user['samaccountname'];
            unset($user['thumbnailphoto']);
            unset($user['lastlogon']);
            unset($user['samaccountname']);
            unset($user['roles']);
            unset($user['email']);
            sort($user['memberOf']);
            $user['memberOf'] = implode(';', $user['memberOf']);
            $users[$samaccountname] = $user;
        }

        return new JsonResponse($users);
    }
}
