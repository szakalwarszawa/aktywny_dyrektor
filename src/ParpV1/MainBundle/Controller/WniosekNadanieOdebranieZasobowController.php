<?php

namespace ParpV1\MainBundle\Controller;

use APY\DataGridBundle\Grid\Source\Entity;
use Doctrine\ORM\EntityNotFoundException;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\Zastepstwo;
use ParpV1\MainBundle\Entity\Komentarz;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Entity\AclRole;
use ParpV1\MainBundle\Entity\AclUserRole;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use ParpV1\MainBundle\Exception\SecurityTestException;
use ParpV1\MainBundle\Form\WniosekNadanieOdebranieZasobowType;
use ParpV1\MainBundle\Services\ParpMailerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ParpV1\MainBundle\Form\LsiImportTokenFormType;
use ParpV1\MainBundle\Entity\WniosekStatus;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use DateTime;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * WniosekNadanieOdebranieZasobow controller.
 *
 * @Route("/wnioseknadanieodebraniezasobow")
 */
class WniosekNadanieOdebranieZasobowController extends Controller
{
    protected $debug = false;
    protected $loguj = true;
    protected $logger;

    protected function getLogger()
    {
        $this->logger = $this->get('logger');
    }

    /**
     * @param $msg
     * @param $data
     */
    protected function logg($msg, $data)
    {
        if (!$this->logger) {
            $this->getLogger();
        }
        //$this->logger->critical($msg, $data);
    }

    /**
     * Lists all WniosekNadanieOdebranieZasobow entities.
     *
     * @param Request $request
     * @param string $ktore
     *
     * @Route("/index/{ktore}", name="wnioseknadanieodebraniezasobow", defaults={"ktore" : "oczekujace"})
     *
     * @Template()
     */
    public function indexAction(Request $request, $ktore = WniosekNadanieOdebranieZasobow::WNIOSKI_OCZEKUJACE)
    {
        $xmlHttpRequest = $request->isXmlHttpRequest();
        $ajaxGrid = $this->container->getParameter('ajax_grid_wnioskinadanieodebranie');
        $pustyGrid = false;
        if (WniosekNadanieOdebranieZasobow::WNIOSKI_WSZYSTKIE === $ktore && !$xmlHttpRequest && $ajaxGrid) {
            $pustyGrid = true;
        }

        $wnioskiNadanieOdebranieGrid = $this->get('wnioski_nadanie_odebranie_grid');
        $grid = $wnioskiNadanieOdebranieGrid
             ->setTypWniosku($ktore)
             ->forceWyswietlGrid()
             ->generateGrid($pustyGrid)
         ;

        $entityManager = $this->getDoctrine()->getManager();
        $zastepstwa = $entityManager
            ->getRepository(Zastepstwo::class)
            ->znajdzZastepstwa($this->getUser()->getUsername())
        ;

        $szablonTwig = 'ParpMainBundle:WniosekNadanieOdebranieZasobow:index.html.twig';

        if ($xmlHttpRequest && $ajaxGrid) {
            $szablonTwig = 'ParpMainBundle:Grid:ajax_grid.html.twig';
        }

        return $grid->getGridResponse($szablonTwig, [
                'ktore'      => $ktore,
                'grid'       => $grid,
                'zastepstwa' => $zastepstwa,
                'ajax_grid'  => $ajaxGrid
        ]);
    }

    /**
     * Creates a new WniosekNadanieOdebranieZasobow entity.
     * @Route("/", name="wnioseknadanieodebraniezasobow_create")
     * @Method("POST")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobow:edit.html.twig")
     */
    public function createAction(Request $request)
    {
        $msg = '';
        $entity = new WniosekNadanieOdebranieZasobow();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $jestCoOdebrac = false;

        $listaPracownikowForm = json_decode($entity->getPracownicy(), true);
        $listaPracownikow = [];
        if (null !== $listaPracownikowForm) {
            $resolver = new OptionsResolver();
            $resolver
                ->setRequired([
                    'name',
                    'surname',
                    'email'
                ]);

            foreach ($listaPracownikowForm as $pracownik) {
                $resolver->resolve($pracownik);
                $pracownik = implode(' ', [$pracownik['name'], $pracownik['surname'], $pracownik['email']]);
                $listaPracownikow[] = $pracownik;
            }
        }

        if (null === $listaPracownikowForm) {
            $listaPracownikow = array_filter(explode(',', $entity->getPracownicy()));
        }

        $entity->setPracownicy(implode(',', $listaPracownikow));

        if ($entity->getOdebranie()) {
            $userZasoby = $this
                    ->getDoctrine()
                    ->getRepository(UserZasoby::class)
                    ->findBy(
                        array(
                            'samaccountname' => $listaPracownikow,
                            'czyAktywne' => true
                        )
                    );

            $jestCoOdebrac = count($userZasoby) > 0;
        }

        if (!empty($form->getData()->getPracownicySpozaParp()) && empty($form->getData()->getManagerSpozaParp())) {
            $this->addFlash('danger', 'Nie wybrano managera pracowników spoza PARP');

            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_new', [
                'odebranie' => $entity->getOdebranie()? 1 : 0
            ]));
        }

        if ($entity->getOdebranie() && 1 !== count($listaPracownikow)) {
            $this->addFlash('danger', 'Wniosek o odebranie uprawnień do '
            . 'zasobów można złożyć tylko dla jednej osoby.');

            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_new', array('odebranie' => 1)));
        }

        if ($form->isValid() && (($entity->getOdebranie() && $jestCoOdebrac) || !$entity->getOdebranie())) {
            $entityManager = $this->getDoctrine()->getManager();
            $this->setWniosekStatus($entity, '00_TWORZONY', false);
            $entityManager->persist($entity);
            $entityManager->persist($entity->getWniosek());

            if (count($listaPracownikow) === 0) {
                throw new SecurityTestException(
                    'Nie można złożyć wniosku bez wybrania osób których dotyczy, użyj przycisku wstecz' .
                    ' w przeglądarce i wybierz conajmniej jedną osobę w polu "Pracownicy"!',
                    745
                );
            }

            $entity->ustawPoleZasoby();
            $entityManager->flush();

            $this->addFlash('warning', 'Wniosek został utworzony.');

            $pracownicy = array();
            foreach ($listaPracownikow as $pracownik) {
                $pracownicy[$pracownik] = 1;
            }

            return $this->redirect($this->generateUrl('addRemoveAccessToUsersAction', array(
                'samaccountnames' => json_encode($pracownicy),
                'action'          => ($entity->getOdebranie() ? 'removeResources' : 'addResources'),
                'wniosekId'       => $entity->getId(),
            )));
        } elseif (!(($entity->getOdebranie() && $jestCoOdebrac) || !$entity->getOdebranie())) {
            $this->get('session')
                ->getFlashBag()
                ->set(
                    'warning',
                    'Ten użytkownik nie ma żadnych przypisanych w systemie zasobów, nie ma zatem co odebrać za pomocą wniosku!'
                );

            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow', array()));
        }
        if ($entity->getOdebranie() && !$jestCoOdebrac) {
            $msg =
                ('Nie można utworzyć takiego wniosku bo żadna z osób nie ma dostępu do żadnych zasobów - nie ma co odebrać!!!');
        }

        return array(
            'entity'     => $entity,
            'form'       => $form->createView(),
            'message'    => $msg,
            'userzasoby' => [],
        );
    }

    /**
     * @param $role
     * @return array
     */
    private function getUsersFromADWithRole($role)
    {
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromAD();
        $users = array();
        foreach ($ADUsers as &$u) {
            if (in_array($role, $u['roles'])) {
                $users[$u['samaccountname']] = $u['name'];
            }
        }

        //echo "<pre>"; var_dump($users); die();
        return $users;
    }


    /**
     * @return array
     */
    private function getUsersFromAD()
    {
        $ldap = $this->get('ldap_service');
        $aduser = $this->getUserFromAD($this->getUser()->getUsername());
        $widzi_wszystkich =
            in_array('PARP_WNIOSEK_WIDZI_WSZYSTKICH', $this->getUser()->getRoles()) ||
            in_array('PARP_ADMIN', $this->getUser()->getRoles());

        $ktoreDepartamenty = [mb_strtolower(trim($aduser[0]['department']))];
        if ($this->getUser()->getUsername() == 'monika_standziak') {
            $ktoreDepartamenty[] = 'zarząd';
        }

        $ADUsers = $ldap->getAllFromAD();
        $users = array();

        //temp
        ///$widzi_wszystkich = false;
        //$aduser[0]['department'] = 'Biuro Prezesa';

        foreach ($ADUsers as &$u) {
            //unset($u['thumbnailphoto']);
            //albo ma role ze widzi wszystkich albo widzi tylko swoj departament
            //echo ".".strtolower($aduser[0]['department']).".";
            if ($widzi_wszystkich || in_array(mb_strtolower(trim($u['department'])), $ktoreDepartamenty)) {
                $users[$u['samaccountname']] = $u['name'];
            }
        }

        //echo "<pre>"; var_dump($users); die();
        return $users;
    }

    /**
     * @return array
     */
    protected function getManagerzySpozaPARP()
    {
        $ldap = $this->get('ldap_service');
        $managersSpozaParp =
            $this->getDoctrine()
                ->getManager()
                ->getRepository(AclRole::class)
                ->findOneByName('ROLE_MANAGER_DLA_OSOB_SPOZA_PARP');

        $managerzySpozaParp = [];
        foreach ($managersSpozaParp->getUsers() as $u) {
            $aduser = $this->getUserFromAD($u->getSamaccountname());
            $managerzySpozaParp[$u->getSamaccountname()] = $aduser[0]['name'];
        }

        return $managerzySpozaParp;
    }

    /**
     * Creates a form to create a WniosekNadanieOdebranieZasobow entity.
     *
     * @param WniosekNadanieOdebranieZasobow $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(WniosekNadanieOdebranieZasobow $entity)
    {
        $form =
            $this->createForm(WniosekNadanieOdebranieZasobowType::class, $entity, array(
                'action' => $this->generateUrl('wnioseknadanieodebraniezasobow_create'),
                'method' => 'POST',
                'czy_odebranie' => $entity->getOdebranie(),
                'ad_users' => $this->getUsersFromAD(),
                'managerzy_spoza_parp' => $this->getUsersFromADWithRole('ROLE_MANAGER_DLA_OSOB_SPOZA_PARP'),
            ));

        $form->add(
            'submit',
            SubmitType::class,
            array('label' => 'Przejdź do wyboru zasobów', 'attr' => array('class' => 'btn btn-success'))
        );

        return $form;
    }

    /**
     * Displays a form to create a new WniosekNadanieOdebranieZasobow entity.
     * @Route("/new_dla_zasobow/{zasobyId}", name="wnioseknadanieodebraniezasobow_new_dla_zasobow", defaults={})
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobow:edit.html.twig")
     */
    public function newDlaZasobowAction($zasobyId)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->createEmptyWniosek(false);
        $entity->setPracownicy($this->getUser()->getUsername());
        $em->persist($entity);
        $em->persist($entity->getWniosek());

        $entity->ustawPoleZasoby();
        $em->flush();

        $this->addFlash('warning', 'Wniosek został utworzony.');
        $prs = explode(',', $entity->getPracownicy());
        $pr = array();
        foreach ($prs as $p) {
            $pr[$p] = 1;
        }

        return $this->redirect($this->generateUrl('addRemoveAccessToUsersAction', array(
            'samaccountnames' => json_encode($pr),
            'action'          => ($entity->getOdebranie() ? 'removeResources' : 'addResources'),
            'wniosekId'       => $entity->getId(),
            'zasobyId'        => $zasobyId,
        )));
    }

    /**
     * @param $odebranie
     * @return WniosekNadanieOdebranieZasobow
     */
    protected function createEmptyWniosek($odebranie)
    {
        $ldap = $this->get('ldap_service');
        $ADUser = $this->getUserFromAD($this->getUser()->getUsername());

        $entity = new WniosekNadanieOdebranieZasobow();
        $entity->getWniosek()->setCreatedAt(new \Datetime());
        $entity->getWniosek()->setLockedAt(new \Datetime());
        $entity->getWniosek()->setCreatedBy($this->getUser()->getUsername());
        $entity->getWniosek()->setLockedBy($this->getUser()->getUsername());
        $entity->getWniosek()->setNumer('wniosek w trakcie tworzenia');
        $entity->getWniosek()->setJednostkaOrganizacyjna($ADUser[0]['department']);

        $this->setWniosekStatus($entity, '00_TWORZONY', false);
        //$status = $this->getDoctrine()->getManager()->getRepository('ParpV1\MainBundle\Entity\WniosekStatus')->findOneByNazwaSystemowa('00_TWORZONY');
        //$entity->getWniosek()->setStatus($status);
        $entity->setOdebranie($odebranie);

        return $entity;
    }

    /**
     * Displays a form to create a new WniosekNadanieOdebranieZasobow entity.
     * @Route("/new/{odebranie}", name="wnioseknadanieodebraniezasobow_new", defaults={"odebranie" : 0})
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobow:edit.html.twig")
     */
    public function newAction($odebranie = 0)
    {
        if ($this->getParameter('pusz_to_ad') == true && $odebranie == 1) {
            // $odebranie == 1 /*&& !in_array("PARP_ADMIN", $this->getUser()->getRoles())*/){
        }

        $ldap = $this->get('ldap_service');
        $ADUser = $this->getUserFromAD($this->getUser()->getUsername());

        $status =
            $this->getDoctrine()
                ->getManager()
                ->getRepository(WniosekStatus::class)
                ->findOneByNazwaSystemowa('00_TWORZONY');

        $entity = $this->createEmptyWniosek($odebranie);
        $form = $this->createCreateForm($entity);

        return array(
            'entity'     => $entity,
            'form'       => $form->createView(),
            'message'    => '',
            'userzasoby' => [],
        );
    }

    /**
     * @param $ADUser
     * @return array
     */
    protected function getManagerUseraDoWniosku($ADUser)
    {
        $ldap = $this->get('ldap_service');
        $manager = $this->getDoctrine()->getManager();

        $kogoSzukac = $ldap->kogoBracJakoManageraDlaUseraDoWniosku($ADUser);

        switch ($kogoSzukac) {
            case 'manager':
            case 'prezes':
            case 'p.o. prezesa':
                $ADManager = $ldap->getPrzelozonyJakoTablica($ADUser['samaccountname']);
                break;
            case 'dyrektor':
            default:
                $skrotDepartamentu = $manager->getRepository(Departament::class)
                    ->findOneBy([
                        'name' => $ADUser['department']
                    ]);

                if (null === $skrotDepartamentu) {
                    throw new EntityNotFoundException('Nie mogę znaleźć skrótu departamentu dla '.$ADUser['department']);
                }

                $ADManager = [$ldap->getDyrektoraDepartamentu($skrotDepartamentu->getShortname())];
                break;
        }

        return $ADManager;
    }

    /**
     * @param $msg
     */
    protected function sendMailToAdminRejestru($msg)
    {
        die();
        $mails = ['kamil_jakacki@parp.gov.pl'];

        $em = $this->getDoctrine()->getManager();
        $role = $em->getRepository(AclRole::class)->findOneByName('PARP_ADMIN_REJESTRU_ZASOBOW');
        $users = $em->getRepository(AclUserRole::class)->findByRole($role);
        foreach ($users as $u) {
            $mails[] = $u->getSamaccountname().'@parp.gov.pl';
        }


        $message = \Swift_Message::newInstance()
            ->setSubject('Nie znaleziono użytkownika przy wniosku o nadanie/odebranie uprawnień')
            ->setFrom('intranet@parp.gov.pl')
            //->setFrom("kamikacy@gmail.com")
            ->setTo($mails)
            ->setBody($msg)
            ->setContentType('text/html');

        //var_dump($view);
        $this->container->get('mailer')->send($message);
    }

    /**
     * @param $wniosek
     * @param $statusName
     * @param $rejected
     * @param null $oldStatus
     */
    public function setWniosekStatus($wniosek, $statusName, $rejected, $oldStatus = null)
    {
        $statusWnioskuService = $this->get('status_wniosku_service');
        $statusWnioskuService->setWniosekStatus($wniosek, $statusName, $rejected, $oldStatus);
    }

    /**
     * Finds and displays a WniosekNadanieOdebranieZasobow entity.
     * @Route("/{id}/{isAccepted}/accept_reject/{publishForReal}", name="wnioseknadanieodebraniezasobow_accept_reject",
     *                                                             defaults={"publishForReal" : false})
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function acceptRejectAction(Request $request, $id, $isAccepted, $publishForReal = false)
    {
        $this->logg('=========================================================================START', [
            'url'  => $request->getRequestUri(),
            'user' => $this->getUser()->getUsername(),
        ]);
        $this->logg('acceptRejectAction START!', array(
            'url'            => $request->getRequestUri(),
            'user'           => $this->getUser()->getUsername(),
            'id'             => $id,
            'isAccepted'     => $isAccepted,
            'publishForReal' => $publishForReal,
            'isPost'         => $request->isMethod('POST'),
        ));


        $em = $this->getDoctrine()->getManager();

        $wniosek = $em->getRepository(WniosekNadanieOdebranieZasobow::class)->find($id);
        if ($wniosek !== null) {
            if ($wniosek->getWniosek()->getIsBlocked()) {
                throw new AccessDeniedException('Wniosek jest ostatecznie zablokowany.');
            }
        }

        $zastepstwa = $em->getRepository(Zastepstwo::class)->znajdzKogoZastepuje($this->getUser()->getUsername());
        $czyZastepstwo = (in_array($wniosek->getWniosek()->getLockedBy(), $zastepstwa));

        $acc = $this->checkAccess($wniosek);
        if ($acc['editor'] === null &&
            !($isAccepted == 'publish_lsi' ||
                in_array($this->getUser()->getUsername(), ['marcin_lipinski'])) &&
            !($isAccepted == 'unblock' && ($czyZastepstwo || in_array('PARP_ADMIN_REJESTRU_ZASOBOW', $this->getUser()->getRoles())))
        ) {
            throw new SecurityTestException(
                'Nie możesz zaakceptować wniosku, nie jesteś jego edytorem (nie posiadasz obecnie takich uprawnień, prawdopodobnie już zaakceptowałeś wniosek i jest w on akceptacji u kolejnej osoby!',
                765
            );
        }

        if (!$wniosek) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }
        if ($request->isMethod('POST')) {
            $txt = $request->get('powodZwrotu');
            if (!empty($txt)) {
                $wniosek->setPowodZwrotu($txt);

                $kom = new \ParpV1\MainBundle\Entity\Komentarz();
                $kom->setObiekt('WniosekNadanieOdebranieZasobow');
                $kom->setObiektId($id);
                $kom->setTytul('Wniosek '.($isAccepted == 'return' ? 'zwrócenia' : 'odrzucenia').' z powodu:');
                $kom->setOpis($txt);
                $kom->setSamaccountname($this->getUser()->getUsername());
                $em->persist($kom);
            } elseif (!empty($request->request->get('dataOdebrania'))) {
                if ('acceptAndPublish' === $isAccepted) {
                    try {
                        $dataOdebrania = new DateTime($request->request->get('dataOdebrania'));
                    } catch (\Exception $exception) {
                        $this->addFlash('danger', 'Wprowadzono niepoprawną datę odebrania!');

                        return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array(
                            'id' => $id,
                        )));
                    }
                }
            }
        } else {
            $wniosek->setPowodZwrotu('');
        }

        if (!isset($dataOdebrania)) {
            $dataOdebrania = new DateTime();
        }

        $status = $wniosek->getWniosek()->getStatus()->getNazwaSystemowa();
        if ($isAccepted == 'acceptAndPublish' && !in_array($status, [
                '05_EDYCJA_ADMINISTRATOR',
                '06_EDYCJA_TECHNICZNY',
                '07_ROZPATRZONY_POZYTYWNIE',
                '11_OPUBLIKOWANY',
            ])
        ) {
            $isAccepted =
                'accept'; //byl blad ze ludzie mieli linka do acceptAndPublish i pomijalo wlascicieli i administratorow
        }

        if ($isAccepted == 'unblock') {
            $wniosek->getWniosek()->setLockedBy(null);
            $wniosek->getWniosek()->setLockedAt(null);
        } elseif ($isAccepted == 'reject') {
            //przenosi do status 8
            $this->setWniosekStatus($wniosek, '08_ROZPATRZONY_NEGATYWNIE', true);
            if ($wniosek->getOdebranie()) {
                $odbieranieUprawnienService = $this->get('odbieranie_uprawnien_service');
                $odbieranieUprawnienService->odrzucenieWniosku($wniosek);
            }
        } elseif ($isAccepted == 'publish') {
            //przenosi do status 11
            $showonly = !$publishForReal;
            $kernel = $this->get('kernel');
            $application = new Application($kernel);
            $application->setAutoExit(false);

            $ids = [];
            foreach ($wniosek->getWniosek()->getADEntries() as $e) {
                $ids[] = $e->getId();
            }

            $input = new ArrayInput(array(
                'command'  => 'parp:ldapsave',
                'showonly' => $showonly,
                '--ids'    => implode(',', $ids),
            ));

            // You can use NullOutput() if you don't need the output
            $output = new BufferedOutput(
                OutputInterface::VERBOSITY_NORMAL,
                true // true for decorated
            );
            $application->run($input, $output);

            // return the output, don't use if you used NullOutput()
            $content = $output->fetch();

            $converter = new AnsiToHtmlConverter();
            if ($publishForReal) {
                foreach ($wniosek->getUserZasoby() as $uz) {
                    $uz->setCzyAktywne(!$wniosek->getOdebranie());
                    if ($wniosek->getOdebranie()) {
                        $uz->setDataOdebrania(new DateTime());
                    }

                    $uz->setCzyNadane(true);

                    $em->persist($uz);
                }
                $this->setWniosekStatus($wniosek, '11_OPUBLIKOWANY', false);
            }
            //die('a');
            $em->flush();

            // return new Response(""), if you used NullOutput()
            return $this->render(
                'ParpMainBundle:WniosekNadanieOdebranieZasobow:publish.html.twig',
                array('wniosek' => $wniosek, 'showonly' => $showonly, 'content' => $converter->convert($content))
            );
        } elseif ($isAccepted == 'publish_lsi') {
            $sqls = [];
            foreach ($wniosek->getUserZasoby() as $uz) {
                $moduly = explode(';', $uz->getModul());
                $poziomy = explode(';', $uz->getPoziomDostepu());
                foreach ($moduly as $m) {
                    foreach ($poziomy as $p) {
                        $naborDane = explode('/', $m);
                        $dzialanie = $naborDane[0];
                        $nabor = $naborDane[1];
                        $rola = $p;
                        $sql =
                            "SELECT * FROM uzytkownicy.akd_realizacja_wnioskow('".
                            $uz->getSamaccountname().
                            "', '".
                            $dzialanie.
                            "', '".
                            $nabor.
                            "', '".
                            $rola.
                            "')";
                        $sqls[] = $sql;
                    }
                }
            }

            $response = new Response();

            $response->headers->set('Content-Type', 'application/sql');
            $fileName = $wniosek->getWniosek()->getNumer() . '.sql';
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName);

            $view = $this->renderView('ParpMainBundle:WniosekNadanieOdebranieZasobow:publish_lsi.html.twig', array(
                'query_list' => $sqls
                ));
            $response->setContent($view);

            return $response;
        } else {
            switch ($status) {
                case '00_TWORZONY':
                    switch ($isAccepted) {
                        case 'accept':
                            $this->get('wniosekNumer')->nadajNumer($wniosek, 'wniosekONadanieUprawnien');
                            //klonuje wniosek na male i ustawia im statusy:
                            $przelozeni = array();
                            $zasobyWeWniosku = [];
                            foreach ($wniosek->getUserZasoby() as $uz) {
                                if (!in_array($uz->getZasobOpis(), $zasobyWeWniosku)) {
                                    $zasobyWeWniosku[] = $uz->getZasobOpis();
                                }
                                if ($wniosek->getPracownikSpozaParp()) {
                                    //biore managera z pola managerSpoząParp
                                    $ADManager = $this->getUserFromAD($wniosek->getManagerSpozaParp());
                                    if (count($ADManager) == 0) {
                                        die('Blad 453 Nie moge znalezc przelozonego dla osoby : '.
                                            $uz->getSamaccountname());
                                    }
                                    $przelozeni[$ADManager[0]['samaccountname']][] = $uz;
                                } else {
                                    $ADUser = $this->getUserFromAD($uz->getSamaccountname());
                                    $ADManager = $this->getManagerUseraDoWniosku($ADUser[0]);

                                    if (count($ADManager) == 0) {
                                        die('Blad 657 Nie moge znalezc przelozonego dla osoby : '.
                                            $uz->getSamaccountname());
                                    }
                                    $przelozeni[$ADManager[0]['samaccountname']][] = $uz;
                                }
                            }
                            $wniosek->setZasoby(implode(',', $zasobyWeWniosku));
                            if ($this->debug) {
                                echo '<pre>';
                                \Doctrine\Common\Util\Debug::dump($przelozeni);
                                echo '</pre>';
                            }
                            if (count($przelozeni) > 1) {
                                $numer = 1;
                                //teraz dla kazdego przelozonego tworzy oddzielny wniosek
                                $this->setWniosekStatus($wniosek, '10_PODZIELONY', false);
                                foreach ($przelozeni as $sam => $p) {
                                    if ($this->debug) {
                                        echo '<br><br>Tworzy nowy wniosek dla przelozonego  '.$sam.
                                            ' wzietego z osoby  '.$p[0]->getSamaccountname().
                                            ' :<br><br>';
                                    }


                                    // Fixme: To powinno być zrobione przy pomocy `__clone()`
                                    $wn = new \ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow();
                                    $wn->getWniosek()->setCreatedBy($wniosek->getWniosek()->getCreatedBy());
                                    $wn->getWniosek()->setCreatedAt($wniosek->getWniosek()->getCreatedAt());
                                    $wn->getWniosek()->setLockedBy($wniosek->getWniosek()->getLockedBy());
                                    $wn->getWniosek()->setLockedAt($wniosek->getWniosek()->getLockedAt());
                                    $wn->getWniosek()->setParent($wniosek->getWniosek());
                                    $wn->getWniosek()->setJednostkaOrganizacyjna($wniosek->getWniosek()
                                        ->getJednostkaOrganizacyjna());
                                    $wn->setPracownikSpozaParp($wniosek->getPracownikSpozaParp());
                                    $this->get('wniosekNumer')->nadajPodNumer($wn, $wniosek, $numer++);
                                    $users = array();
                                    foreach ($p as $uz) {
                                        $nuz = clone $uz;
                                        $em->persist($nuz);
                                        $wn->setZasobId($nuz->getZasobId());
                                        $users[$nuz->getSamaccountname()] = $nuz->getSamaccountname();
                                        $nuz->setWniosek($wn);
                                        $wn->addUserZasoby($nuz);
                                    }
                                    $wn->setPracownicy(implode(',', $users));
                                    //klonuje wszystkie historie statusow
                                    foreach ($wniosek->getWniosek()->getStatusy() as $s) {
                                        $s2 = clone $s;
                                        $s2->setWniosek($wn->getWniosek());
                                        $em->persist($s2);
                                    }
                                    $this->setWniosekStatus($wn, '02_EDYCJA_PRZELOZONY', false);
                                    $em->persist($wn->getWniosek());
                                    $em->persist($wn);
                                    $this->get('parp.mailer')->sendEmailWniosekOczekujacy($wn, ParpMailerService::TEMPLATE_OCZEKUJACYWNIOSEK);
                                }
                            } else {
                                $this->setWniosekStatus($wniosek, '02_EDYCJA_PRZELOZONY', false);
                                $this->get('parp.mailer')->sendEmailWniosekOczekujacy($wniosek, ParpMailerService::TEMPLATE_OCZEKUJACYWNIOSEK);
                            }
                            if ($this->debug) {
                                die('<br>wszystko poszlo ok');
                            }
                            break;
                        case 'return':
                            //nie powinno miec miejsca
                            die('blad 5034 nie powinno miec miejsca');
                            break;
                    }
                    break;
                case '01_EDYCJA_WNIOSKODAWCA':
                    switch ($isAccepted) {
                        case 'accept':
                            $this->setWniosekStatus($wniosek, '02_EDYCJA_PRZELOZONY', false);
                            $this->get('parp.mailer')->sendEmailWniosekOczekujacy($wniosek, ParpMailerService::TEMPLATE_OCZEKUJACYWNIOSEK);
                            break;
                        case 'return':
                            die('blad 45 nie powinno miec miejsca');
                            break;
                    }
                    break;
                case '02_EDYCJA_PRZELOZONY':
                    switch ($isAccepted) {
                        case 'accept':
                            //klonuje wniosek na male i ustawia im statusy:
                            $zasoby = array();
                            foreach ($wniosek->getUserZasoby() as $uz) {
                                $zasoby[$uz->getZasobId()][] = $uz;
                            }
                            if (count($zasoby) > 1) {
                                $this->setWniosekStatus($wniosek, '10_PODZIELONY', false);
                                $numer = 1;
                                $zasobyService = $this->get('zasoby_service');
                                //teraz dla kazdego zasobu tworzy oddzielny wniosek
                                foreach ($zasoby as $z) {
                                    if ($this->debug) {
                                        echo '<br><br>Tworzy nowy wniosek dla zasobu '.$z->getZasobId().
                                            '<br><br>';
                                    }
                                    $wn = new WniosekNadanieOdebranieZasobow();
                                    $wn->getWniosek()->setCreatedBy($wniosek->getWniosek()->getCreatedBy());
                                    $wn->getWniosek()->setCreatedAt($wniosek->getWniosek()->getCreatedAt());
                                    $wn->getWniosek()->setLockedBy($wniosek->getWniosek()->getLockedBy());
                                    $wn->getWniosek()->setLockedAt($wniosek->getWniosek()->getLockedAt());
                                    $wn->getWniosek()->setParent($wniosek->getWniosek());
                                    $wn->getWniosek()->setJednostkaOrganizacyjna($wniosek->getWniosek()
                                        ->getJednostkaOrganizacyjna());
                                    $wn->setPracownikSpozaParp($wniosek->getPracownikSpozaParp());
                                    $wn->setManagerSpozaParp($wniosek->getManagerSpozaParp());
                                    $wn->setOdebranie($wniosek->getOdebranie());

                                    $this->get('wniosekNumer')->nadajPodNumer($wn, $wniosek, $numer++);
                                    $users = array();
                                    foreach ($z as $uz) {
                                        if ($wn->getOdebranie()) {
                                            $uz->setWniosekOdebranie($wn);
                                            $wn->setZasobId($uz->getId());
                                            $em->persist($uz);
                                            $users[$uz->getSamaccountname()] = $uz->getSamaccountname();
                                            $userZasobPersist = $uz;
                                            $wn->addUserZasobyOdbierane($uz);
                                        } else {
                                            $nuz = clone $uz;
                                            $nuz->setWniosek($wn);
                                            $em->persist($nuz);
                                            $wn->addUserZasoby($nuz);
                                            $wn->setZasobId($nuz->getId());
                                            $users[$nuz->getSamaccountname()] = $nuz->getSamaccountname();
                                            $userZasobPersist = $nuz;
                                            $wn->addUserZasoby($uz);
                                        }

                                        $wn
                                            ->setZawieraZasobyZAd($zasobyService->czyZasobMaGrupyAd($userZasobPersist))
                                        ;
                                    }

                                    $wn->setPracownicy(implode(',', $users));
                                    foreach ($wniosek->getWniosek()->getStatusy() as $s) {
                                        $s2 = clone $s;
                                        $s2->setWniosek($wn->getWniosek());
                                        $em->persist($s2);
                                    }

                                    $wn->ustawPoleZasoby();
                                    $em->persist($wn->getWniosek());
                                    $em->persist($wn);
                                    $this->setWniosekStatus(
                                        $wn,
                                        ($wniosek->getOdebranie() ? '05_EDYCJA_ADMINISTRATOR' : '03_EDYCJA_WLASCICIEL'),
                                        false
                                    );
                                }
                            } else {
                                $this->setWniosekStatus(
                                    $wniosek,
                                    ($wniosek->getOdebranie() ? '05_EDYCJA_ADMINISTRATOR' : '03_EDYCJA_WLASCICIEL'),
                                    false
                                );
                            }
                            break;
                        case 'return':
                            $this->setWniosekStatus($wniosek, ('01_EDYCJA_WNIOSKODAWCA'), true);
                            break;
                    }
                    break;
                case '03_EDYCJA_WLASCICIEL':
                    switch ($isAccepted) {
                        case 'accept':
                            $maBycIbi = false;
                            foreach ($wniosek->getUserZasoby() as $uz) {
                                $maBycIbi =
                                    $maBycIbi ||
                                    $uz->getUprawnieniaAdministracyjne() ||
                                    $wniosek->getPracownikSpozaParp();
                            }

                            if ($maBycIbi) {
                                $this->setWniosekStatus($wniosek, '04_EDYCJA_IBI', false);
                            } else {
                                $this->setWniosekStatus($wniosek, '05_EDYCJA_ADMINISTRATOR', false);
                            }

                            break;
                        case 'return':
                            $this->setWniosekStatus($wniosek, '02_EDYCJA_PRZELOZONY', true);
                            break;
                    }
                    break;
                case '04_EDYCJA_IBI':
                    switch ($isAccepted) {
                        case 'accept':
                            $this->setWniosekStatus($wniosek, '05_EDYCJA_ADMINISTRATOR', false);
                            break;
                        case 'return':
                            $this->setWniosekStatus($wniosek, '03_EDYCJA_WLASCICIEL', true);
                            break;
                    }
                    break;
                case '05_EDYCJA_ADMINISTRATOR':
                    switch ($isAccepted) {
                        case 'acceptAndPublish':
                            $this->setWniosekStatus($wniosek, '07_ROZPATRZONY_POZYTYWNIE', false, $status);
                            break;
                        case 'accept':
                            $this->setWniosekStatus($wniosek, '06_EDYCJA_TECHNICZNY', false, $status);
                            break;
                        case 'return':
                            if ($wniosek->getOdebranie()) {
                                $this->setWniosekStatus($wniosek, '02_EDYCJA_PRZELOZONY', false);
                                break;
                            }
                            $maBycIbi = false;
                            foreach ($wniosek->getUSerZasoby() as $uz) {
                                $maBycIbi =
                                    $maBycIbi ||
                                    $uz->getUprawnieniaAdministracyjne() ||
                                    $wniosek->getPracownikSpozaParp();
                            }
                            if ($maBycIbi) {
                                $this->setWniosekStatus($wniosek, '04_EDYCJA_IBI', false);
                            } else {
                                $this->setWniosekStatus($wniosek, '03_EDYCJA_WLASCICIEL', false);
                            }

                            break;
                    }
                    break;
                case '06_EDYCJA_TECHNICZNY':
                    switch ($isAccepted) {
                        case 'accept':
                        case 'acceptAndPublish':
                            $isAccepted = 'acceptAndPublish';
                            $this->setWniosekStatus($wniosek, '07_ROZPATRZONY_POZYTYWNIE', false, $status);
                            break;
                        case 'return':
                            $this->setWniosekStatus($wniosek, '05_EDYCJA_ADMINISTRATOR', true);
                            break;
                    }
                    break;
            }

            if ($isAccepted == 'acceptAndPublish' && in_array($status, [
                    '05_EDYCJA_ADMINISTRATOR',
                    '06_EDYCJA_TECHNICZNY',
                    '07_ROZPATRZONY_POZYTYWNIE',
                    '11_OPUBLIKOWANY',
                ])
            ) {
                //dla wnioskow spoza parp szukamy departamentu przelozonego
                if ($wniosek->getPracownikSpozaParp()) {
                    $aduser = $this->getUserFromAD($wniosek->getManagerSpozaParp());

                    $department =
                        $this->getDoctrine()
                            ->getRepository(Departament::class)
                            ->findOneByName(trim($aduser[0]['department']));
                    $biuro = $department->getShortname();
                    //print_r($biuro);    die();
                }

                foreach ($wniosek->getUserZasoby() as $uz) {
                    $z = $em->getRepository(Zasoby::class)->find($uz->getZasobId());
                    $uz->setCzyAktywne(!$wniosek->getOdebranie());

                    if ($wniosek->getOdebranie()) {
                        $uz->setCzyOdebrane(true);
                        if (!$wniosek->getZawieraZasobyZAd()) {
                            $uz->setDataOdebrania($dataOdebrania);
                        }

                        $uz->setKtoOdebral($this->getUser()->getUsername());
                    }

                    if ($z->getGrupyAd()) {
                        $grupy = explode(';', $z->getGrupyAd());

                        $poziomy = str_replace('; ', ';', $z->getPoziomDostepu());
                        $poziomyTekst = str_replace(";", ", ", $poziomy);

                        $dostepnePoziomy = explode(';', $poziomy);

                        if (!in_array($uz->getPoziomDostepu(), $dostepnePoziomy)) {
                            $message = 'Niewłaściwy poziom dostepu dla zasobu \'' . $z->getNazwa() .
                                '\', wybrany poziom to \'' . $uz->getPoziomDostepu() . '\', dostepne poziomy: ' .
                                $poziomyTekst . '. W trakcie tworzenia wniosku zasób uległ zmianie. ' .
                                'Skontaktuj się z właścielem zasobu.'
                            ;
                            $this
                                ->addFlash('danger', $message)
                            ;
                            return $this->redirectToRoute('wnioseknadanieodebraniezasobow_show', ['id' => $wniosek->getId()]);
                        }
                        $indexGrupy = array_search($uz->getPoziomDostepu(), $dostepnePoziomy);

                        //foreach($grupy as $grupa){

                        if ($uz->getPoziomDostepu() === '[BD] Bez dostępu do sieci PARP') {
                            $grupa = '';
                        } else {
                            $grupa = trim($grupy[$indexGrupy]);
                        }

                        if ($grupa != '') {
                            //jesli sa grupy ad to tworzy entry powiazane i daje przycisk opublikuj
                            $aduser = $this->getUserFromAD($uz->getSamaccountname());
                            if ($wniosek->getPracownikSpozaParp()) {
                                $imieNazwisko =
                                    $this->get('samaccountname_generator')->rozbijFullname($uz->getSamaccountname());
                                $aduser[] = [
                                    'samaccountname'    => $this->get('samaccountname_generator')
                                        ->generateSamaccountname($imieNazwisko['imie'], $imieNazwisko['nazwisko']),
                                    'name'              => $this->get('samaccountname_generator')
                                        ->generateFullname($imieNazwisko['imie'], $imieNazwisko['nazwisko']),
                                    'distinguishedname' => $this->get('samaccountname_generator')
                                        ->generateDN($imieNazwisko['imie'], $imieNazwisko['nazwisko'], $biuro),
                                ];
                                //print_r($aduser); die();
                            }
                            if (!$wniosek->getPracownikSpozaParp()) {
                                $entry = new \ParpV1\MainBundle\Entity\Entry($this->getUser()->getUsername());
                                $entry->setWniosek($wniosek->getWniosek());
                                $entry->setFromWhen(new \Datetime());
                                $entry->setSamaccountname($aduser[0]['samaccountname']);
                                $symbol = $wniosek->getOdebranie() ? '-' : '+';
                                $entry->setMemberOf($symbol.$grupa);
                                $entry->setIsImplemented(0);
                                $entry->setDistinguishedName($aduser[0]['distinguishedname']);
                                $em->persist($entry);
                            }
                        }
                        //}
                    } else {
                        //bez grup ad tworzymy zadanie i maila do admina
                        $this->get('uprawnienia_service')->wyslij(
                            array(
                                'cn'             => '',
                                'samaccountname' => $uz->getSamaccountname(),
                                'fromWhen'       => new \Datetime(),
                            ),
                            array(),
                            array($z->getNazwa()),
                            'Zasoby',
                            $uz->getZasobId(),
                            ($status ==
                            '05_EDYCJA_ADMINISTRATOR' ? $z->getAdministratorZasobu() : $z->getAdministratorTechnicznyZasobu()),
                            $wniosek
                        );

                        $uz->setCzyAktywne(!$wniosek->getOdebranie());

                        $uz->setCzyNadane(true);
                    }
                }

                if ($wniosek->getOdebranie()) {
                    $flashMessage = 'Odnotowałem odebranie wskazanych uprawnień.';
                    if (null === $wniosek->getDataOdebrania() && $wniosek->getZawieraZasobyZAd()) {
                        $flashMessage.= ' Data odebrania zostanie ustawiona po opublikowaniu zmian w AD!';
                    }
                    $this->addFlash('danger', $flashMessage);
                }
            }
        }

        $this->logg('=========================================================================END', [
            'url'  => $request->getRequestUri(),
            'user' => $this->getUser()->getUsername(),
        ]);
        //temp badam sqle przy akceptacji wniosku Grzesia
        $em->flush();
        //die('a');
        //return new Response("<html><head></head><body>aaa</body></html>");

        if ($isAccepted == 'unblock') {
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow', array()));
        } elseif ($wniosek->getWniosek()->getStatus()->getNazwaSystemowa() == '00_TWORZONY') {
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow', array()));
        } else {
            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array(
                'id' => $id,
            )));
        }
    }

    /**
     * @param $entity
     * @param bool $onlyEditors
     * @param null $username
     * @return array
     */
    protected function checkAccess($entity, $onlyEditors = false, $username = null)
    {
        $statusWnioskuService = $this->get('status_wniosku_service');

        return $statusWnioskuService->checkAccess($entity, $onlyEditors, $username);
    }

    /**
     * Finds and displays a WniosekNadanieOdebranieZasobow entity.
     * @Route("/{id}/show", name="wnioseknadanieodebraniezasobow_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $ldap = $this->get('ldap_service');
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(WniosekNadanieOdebranieZasobow::class)->find($id);

        $access = $this->checkAccess($entity);

        if (!$access['viewer'] && !$access['editor'] && !in_array('PARP_ADMIN', $this->getUser()->getRoles())) {
            return $this->render(
                'ParpMainBundle:WniosekNadanieOdebranieZasobow:denied.html.twig',
                array('wniosek' => $entity, 'viewer' => 0)
            );
        }
        $uzs = $em->getRepository(UserZasoby::class)->findByWniosekWithZasob($entity);
        //die(count($uzs).">");
        $editor = $access['editor'];

        if (substr($entity->getWniosek()->getStatus()->getNazwaSystemowa(), 0, 1) == '1') {
            $editor = false;
        }

        $grupyAD = [];
        $userGroups = [];
        foreach ($entity->getWniosek()->getADentries() as $e) {
            if (!isset($userGroups[$e->getSamaccountname()])) {
                $userGroups[$e->getSamaccountname()] = $ldap->getAllUserGroupsRecursivlyFromAD($e->getSamaccountname());


                //echo "<pre>"; print_r($e->getMemberOf()); print_r( $userGroups[$e->getSamaccountname()]); die();
            }
            $szukanaGrupaDane = $ldap->getGrupa(substr($e->getMemberOf(), 1));

            //echo "<pre>"; print_r($szukanaGrupaDane); die();
            $szukanaGrupa = $szukanaGrupaDane['distinguishedname'];//"CN="..$patch;
            $czyMaByc = substr($e->getMemberOf(), 0, 1) == '+';


            $czyJest = false;
            foreach ($userGroups[$e->getSamaccountname()] as $ug) {
                if (is_array($ug)) {
                    if ($ug['dn'] == $szukanaGrupa[0]) {
                        $czyJest = true;
                    }
                }
            }

            $grupyAD[] = [
                'entry'     => $e,
                'nadanawAD' => $czyJest,
                'maBycwAD'  => $czyMaByc,
            ];
        }

        $czyLsi = false;
        $userzasobyRozbite = [];
        foreach ($uzs as $uz) {
            $moduly = explode(';', $uz->getModul());
            $poziomy = explode(';', $uz->getPoziomDostepu());
            foreach ($moduly as $m) {
                foreach ($poziomy as $p) {
                    $nowyUzs = clone $uz;
                    $nowyUzs->setModul($m);
                    $nowyUzs->setPoziomDostepu($p);
                    $userzasobyRozbite[] = $nowyUzs;
                    $czyLsi = $uz->getZasobId() == 4420;
                }
            }
        }

        $deleteForm = $this->createDeleteForm($id);
        $comments =
            $em->getRepository(Komentarz::class)
                ->getCommentCount('WniosekNadanieOdebranieZasobow', $entity->getId());

        $zastepstwa = $em->getRepository(Zastepstwo::class)->znajdzKogoZastepuje($this->getUser()->getUsername());

        $lsiImportTokenForm = null;
        if ($czyLsi) {
            $lsiImportTokenForm = $this->createForm(LsiImportTokenFormType::class, null, array(
                'wniosek_nadanie_odebranie_zasobow' => $entity,
                'action' => $this->generateUrl('lsi_import_token_generate'),
            ));

            $lsiImportTokenForm = $lsiImportTokenForm->createView();
        }

        $potrzebnaDataOdebrania = function () use ($entity) {
            $edycjaAdministratora = (
                $entity
                    ->getWniosek()
                    ->getStatus()
                    ->getNazwaSystemowa() === '05_EDYCJA_ADMINISTRATOR'
            );
            $wniosekOdebranie = $entity->getOdebranie();
            $zawieraZasobyZAd = !$entity->getZawieraZasobyZAd();

            if ($edycjaAdministratora && $wniosekOdebranie && $zawieraZasobyZAd) {
                return true;
            }

            return false;
        };

        return array(
            'grupyAD'               => $grupyAD,
            'entity'                => $entity,
            'delete_form'           => $deleteForm->createView(),
            'userzasoby'            => $uzs,
            'editor'                => $editor,
            'canReturn'             => ($entity->getWniosek()->getStatus()->getNazwaSystemowa() != '00_TWORZONY' &&
                $entity->getWniosek()->getStatus()->getNazwaSystemowa() !=
                '01_EDYCJA_WNIOSKODAWCA'),
            'canUnblock'            => ($entity->getWniosek()->getLockedBy() == $this->getUser()->getUsername()),
            'czyZastepstwo'         => (in_array($entity->getWniosek()->getLockedBy(), $zastepstwa)),
            'userzasobyRozbite'     => $userzasobyRozbite,
            'czyLsi'                => $czyLsi,
            'lsi_import_token_form' => $lsiImportTokenForm,
            'comments'              => $comments,
            'potrzebna_data_odebrania' => $potrzebnaDataOdebrania(),
        );
    }

    /**
     * @Route("/generate_lsi_import_token", name="lsi_import_token_generate")
     *
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function generateLsiImportTokenAction(Request $request)
    {
        $formData = $request->request->all();

        $lsiImportService = $this->get('lsi_import_service');

        return $lsiImportService->createOrFindToken($formData);
    }

    /**
     * Finds and displays a WniosekNadanieOdebranieZasobow entity.
     * @Route("/skasuj/{id}", name="wnioseknadanieodebraniezasobow_delete")
     * @Method("GET")
     * @Template()
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(WniosekNadanieOdebranieZasobow::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing WniosekNadanieOdebranieZasobow entity.
     * @Route("/{id}/delete_uz", name="wnioseknadanieodebraniezasobow_delete_uz")
     * @Method("GET")
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     * @Template()
     */
    public function deleteUzAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(UserZasoby::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find UserZasoby entity.');
        }
        if ($entity->getWniosek()->getWniosek()->getIsBlocked()) {
            throw new AccessDeniedException('Wniosek jest ostatecznie zablokowany.');
        }
        $wniosekId = $entity->getWniosek()->getId();
        $entity->getWniosek()->removeUserZasoby($entity);
        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array('id' => $wniosekId)));
    }

    /**
     * Displays a form to edit an existing WniosekNadanieOdebranieZasobow entity.
     * @Route("/{id}/edit", name="wnioseknadanieodebraniezasobow_edit")
     * @Method("GET")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobow:edit.html.twig")
     */
    public function editAction($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository(WniosekNadanieOdebranieZasobow::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }

        $accessCheckerService = $this->get('check_access');
        $wniosekZablokowany = $accessCheckerService
            ->checkWniosekIsBlocked($entity, null, true);

        $access = $this->checkAccess($entity);
        if (!$access['editor']) {
            return $this->render(
                'ParpMainBundle:WniosekNadanieOdebranieZasobow:denied.html.twig',
                array('wniosek' => $entity, 'viewer' => 0)
            );
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        $uzs = $entityManager->getRepository(UserZasoby::class)->findByWniosekWithZasob($entity);

        return array(
            'entity'      => $entity,
            'form'        => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'userzasoby'  => $uzs,
        );
    }

    /**
     * Creates a form to edit a WniosekNadanieOdebranieZasobow entity.
     *
     * @param WniosekNadanieOdebranieZasobow $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(WniosekNadanieOdebranieZasobow $entity)
    {
        $form =
            $this->createForm(WniosekNadanieOdebranieZasobowType::class, $entity, array(
                'action' => $this->generateUrl(
                    'wnioseknadanieodebraniezasobow_update',
                    array('id' => $entity->getId())
                ),
                'method' => 'PUT',
                'ad_users' => $this->getUsersFromAD(),
                'managerzy_spoza_parp' => $this->getUsersFromADWithRole('ROLE_MANAGER_DLA_OSOB_SPOZA_PARP'),
            ));

        $form->add(
            'submit',
            SubmitType::class,
            array('label' => 'Zapisz zmiany', 'attr' => array('class' => 'btn btn-success'))
        );

        return $form;
    }

    /**
     * Edits an existing WniosekNadanieOdebranieZasobow entity.
     * @Route("/{id}", name="wnioseknadanieodebraniezasobow_update")
     * @Method("PUT")
     * @Template("ParpMainBundle:WniosekNadanieOdebranieZasobow:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(WniosekNadanieOdebranieZasobow::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }

        $accessCheckerService = $this->get('check_access');
        $wniosekZablokowany = $accessCheckerService
            ->checkWniosekIsBlocked($entity, null, true);

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            if (strpos($entity->getPracownicy(), ',') !== false) {
                $osoby = explode(',', $entity->getPracownicy());
            } else {
                $osoby = explode(';', $entity->getPracownicy());
            }

            if ($entity->getOdebranie() && 1 !== count($osoby)) {
                $this->addFlash('danger', 'Wniosek o odebranie uprawnień do '
                . 'zasobów można złożyć tylko dla jednej osoby.');

                return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array('id' => $id)));
            }

            foreach ($entity->getUserZasoby() as $uz) {
                if (!in_array($uz->getSamaccountname(), $osoby)) {
                    $em->remove($uz);
                }
            }


            $entity->ustawPoleZasoby();
            $em->flush();
            $this->addFlash('warning', 'Zmiany zostały zapisane');

            return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow_show', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a WniosekNadanieOdebranieZasobow entity.
     * @Route("/skasuj/{id}", name="wnioseknadanieodebraniezasobow_delete_form")
     * @Method("DELETE")
     */
    public function deleteFormAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository(WniosekNadanieOdebranieZasobow::class)->find($id);

            $accessCheckerService = $this->get('check_access');
            $wniosekZablokowany = $accessCheckerService
                ->checkWniosekIsBlocked($entity, null, true);

            if ($entity->getWniosek()->getCreatedBy() !== $this->getUser()->getUsername()) {
                $this->addFlash('danger', 'Możesz usunąć tylko swój wniosek.');

                return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow'));
            }

            if ('00_TWORZONY' !== $entity->getWniosek()->getStatus()->getNazwaSystemowa()) {
                $this->addFlash('danger', 'Można usunąć tylko wnioski ze statusem Tworzony');

                return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow'));
            }

            $userZasobyWniosku = $em
                ->getRepository(UserZasoby::class)
                ->findBy([
                    'wniosekOdebranie' => $id
                ]);

            foreach ($userZasobyWniosku as $userZasob) {
                $userZasob
                    ->setPowodOdebrania(null)
                    ->setWniosekOdebranie(null)
                ;

                $em->persist($userZasob);
            }

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
            }

            $this->addFlash('warning', 'Wniosek został skasowany.');
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('wnioseknadanieodebraniezasobow'));
    }

    /**
     * Creates a form to delete a WniosekNadanieOdebranieZasobow entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('wnioseknadanieodebraniezasobow_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Skasuj wniosek', 'attr' => array('class' => 'btn btn-danger')))
            ->getForm();
    }

    /**
     * Końcowa blokada wniosku - uniemożliwia edytowanie go lub wprowadzanie zmian na powiązanych
     * obiektach np. komentarzach w tym wniosku.
     *
     * @Route("/zablokujwniosekkoncowo/{wniosek}/{status}/{komentarz}", name="zablokuj_wniosek_koncowo")
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     *
     * @param WniosekNadanieOdebranieZasobow $wniosek
     * @param string $status
     * @param string $komentarz
     *
     * @return Response
     */
    public function zablokujWniosekKoncowo(WniosekNadanieOdebranieZasobow $wniosek, $status, $komentarz = null)
    {
        $responseRedirect = $this->redirect(
            $this->generateUrl('wnioseknadanieodebraniezasobow_show', array(
                'id' => $wniosek->getId()
            ))
        );
        $wniosekZakonczony = $wniosek->getWniosek()->getStatus()->getFinished();

        if (WniosekStatus::ANULOWANO_ADMINISTRACYJNIE === $status && $wniosekZakonczony) {
            $this->addFlash('warning', 'Wniosek jest zakończony, nie można anulować.');

            return $responseRedirect;
        }

        if (WniosekStatus::ODEBRANO_ADMINISTRACYJNIE === $status && !$wniosekZakonczony) {
            $this->addFlash('warning', 'Wniosek nie jest zakończony, nie można odebrać.');

            return $responseRedirect;
        }

        $uprawnieniaService = $this->get('uprawnienia_service');
        $uprawnieniaService->zablokujKoncowoWniosek($wniosek, $status, $komentarz, true);

        $this->addFlash('danger', 'Zablokowano wniosek.');

        return $responseRedirect;
    }

    /**
     * Prywatna funkcja zwracająca użytkownika z AD. Jeżeli dany użytkownik nie zostanie znaleziony w pierwszym
     * przebiegu (użytkowników aktywnych), pobierany jest z użytkowników nieaktywnych.
     *
     * @param string $samaccountname
     *
     * @return array
     */
    private function getUserFromAD($samaccountname)
    {
        $ldap = $this->get('ldap_service');
        $aduser = $ldap->getUserFromAD($samaccountname);
        if ($aduser === null || count($aduser) === 0) {
            $aduser = $ldap->getUserFromAD($samaccountname, null, null, 'nieobecni');
        }

        if (empty($aduser)) {
            echo "Problem z ".$samaccountname."<br/>";
            echo "<pre>";
            var_dump(debug_backtrace(null, 1));
        }
        return $aduser;
    }
}
