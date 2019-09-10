<?php

namespace ParpV1\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use ParpV1\MainBundle\Form\WniosekNadanieOdebranieZasobowType;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use ParpV1\MainBundle\Exception\SecurityTestException;
use ParpV1\MainBundle\Entity\Entry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use APY\DataGridBundle\Grid\Column\TextColumn;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\DaneRekord;
use ParpV1\MainBundle\Form\EdycjaUzytkownikaFormType;
use ParpV1\MainBundle\Services\ParpMailerService;

/**
 * BlokowaneKontaController .
 *
 * @Route("/blokowanekonta")
 */
class BlokowaneKontaController extends Controller
{
    /**
     * Lists all zablokowane konta entities.
     *
     * @Route("/lista/{ktorzy}", name="lista_odblokowania", defaults={"ktorzy" : "zablokowane"})
     * @Security("has_role('PARP_ADMIN') or has_role('PARP_BZK_1')")
     * @Template()
     * @param string $ktorzy
     * @return Response
     */
    public function listaAction($ktorzy = "zablokowane" /* nieobecni */)
    {
        $ldap = $this->get('ldap_service');
        $ADUsers = $ldap->getAllFromADIntW($ktorzy);

        if (count($ADUsers) == 0) {
            return $this->render('ParpMainBundle:Default:NoData.html.twig');
        }

        /**
         * FIXME: Poniższe trzeba zmienić na właściwe tworzenie grida użytkowników, np. poprzez zastosowanie modelu
         * FIXME: usługowego.
         */
        $ctrl = new DefaultController();
        $grid = $ctrl->getUserGrid($this->get('grid'), $ADUsers, $ktorzy, $this->getUser()->getRoles());

        $rowAction = new RowAction('<i class="fas fa-pencil"></i> Odblokuj', 'unblock_user');
        $rowAction->setColumn('akcje');
        $rowAction->setRouteParameters(
            array('samaccountname', 'ktorzy' => $ktorzy)
        );
        $rowAction->addAttribute('class', 'btn btn-success btn-xs');

        $grid->addRowAction($rowAction);
        $grid->isReadyForRedirect();

        return $grid->getGridResponse(['ktorzy' => $ktorzy]);
    }

    /**
     * Lists all zablokowane konta entities.
     *
     * @Route("/unblock/{ktorzy}/{samaccountname}", name="unblock_user")
     * @Template()
     * @Security("has_role('PARP_ADMIN') or has_role('PARP_BZK_1')")
     * @param Request $request
     * @param $ktorzy
     * @param $samaccountname
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function unblockAction(Request $request, $ktorzy, $samaccountname)
    {
        $em = $this->getDoctrine()->getManager();
        $ldap = $this->get('ldap_service');
        $ADUser = $ldap->getUserFromAD($samaccountname, null, null, $ktorzy);
        $daneRekord = $em->getRepository(DaneRekord::class)->findOneBy([
            'login' => $samaccountname
        ]);

        $ctrl = new DefaultController();
        $ctrl->setContainer($this->container);
        $form = $this->createForm(EdycjaUzytkownikaFormType::class, null, [
            'entity_manager' => $em,
            'username' => $samaccountname,
            'form_type' => EdycjaUzytkownikaFormType::TYP_EDYCJA,
            'short_form' => false
        ]);

        $departamentRekord = "";
        if ($daneRekord) {
            $departamentRekord = $em->getRepository(Departament::class)->findOneBy([
                'nameInRekord' => $daneRekord->getDepartament()
            ]);
        }
        $form->handleRequest($request);
        if ($request->getMethod() === "POST") {
            $data = $form->getData();
            $ouGoscia = $ADUser[0]['distinguishedname'];
            $zablokowanyByl = false !== strpos($ouGoscia, 'Zablokowane');
            $nieobecnyByl = false !== strpos($ouGoscia, 'Nieobecni');

            if ($zablokowanyByl) {
                //trzeba nadać podstawowe
                $ADUser[] = ['title' => $data['title']->getName()];
                $ADUser = array_merge($ADUser[0], $ADUser[1]);
                $noweGrupy = $ldap->getGrupyUsera($ADUser, $data['department'], $data['info']);
            }


            $nowyDn = str_replace('Zablokowane', $data['department']->getShortname(), $ouGoscia);
            $nowyDn = str_replace('Nieobecni', $data['department']->getShortname(), $nowyDn);


            $ctrl = new DefaultController();
            $ctrl->setContainer($this->container);
            $entry = new Entry();
            $entry->setSamaccountname($samaccountname)
                ->setActivateDeactivated(true)
                ->setIsDisabled(0)
                ->setFromWhen(new \Datetime())
                ->setDistinguishedName($nowyDn)
                ->setCreatedBy($this->getUser()->getUsername())
                ->setOdblokowanieKonta(true)
            ;

            if ($zablokowanyByl) {
                $entry->addGrupyAD($noweGrupy, '+');
            }

            $ctrl->parseUserFormData($data, $entry);

            if ($nieobecnyByl) {
                $daneEmail = [
                    'tytul'          => $entry->getCn(),
                    'imie_nazwisko'  => $entry->getCn(),
                    'login'          => $entry->getSamaccountname(),
                    'departament'    => $entry->getDepartment()->getName(),
                    'manager'        => $entry->getManager(),
                    'odbiorcy'       => [ParpMailerService::EMAIL_DO_GLPI],
                ];
                $this->get('parp.mailer')->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIK_NIEOBECNY_POWROT_BI, $daneEmail);
            }

            $em->persist($entry);
            $em->flush();

            return $this->redirect($this->generateUrl('main'));
        }

        $dane = [
            'samaccountname' => $samaccountname,
            'daneRekord' => $daneRekord,
            'user' => (count($ADUser) > 0 ? $ADUser[0] : null),
            'form' => $form->createView(),
            'departamentRekord' => $departamentRekord
        ];

        return $dane;
    }


    /**
     * Lists all zablokowane konta entities.
     *
     * @Route("/kontaDisabledPrzenies", name="kontaDisabledPrzenies")
     * @Security("has_role('PARP_ADMIN') or has_role('PARP_BZK_1')")
     * @Template()
     * @param Request $request
     * @return
     */
    public function kontaDisabledPrzeniesAction(Request $request)
    {
        $ldap = $this->get('ldap_service');

        $disabled = $ldap->getAllDisabled();

        for ($i = 0; $i < count($disabled); $i++) {
            $d = $disabled[$i];
            $name = $this->get('samaccountname_generator')->ADnameToRekordNameAsArray($d['name']);
            $rekordDane = $this->getDoctrine()->getManager()->getRepository('ParpMainBundle:DaneRekord')->findOneBy(
                [
                    'nazwisko' => $name[0],
                    'imie' => $name[1],
                ]
            );
            $d['daneRekord'] = (array)$rekordDane;
            unset($d['daneRekord']['entries']);
            $disabled[$i] = $d;
            //var_dump($d);
        }



        $ctrl = new DefaultController();
        $grid = $ctrl->getUserGrid($this->get('grid'), $disabled, "nieobecni", $this->getUser()->getRoles());

        $grid->hideColumns(['thumbnailphoto', 'daneRekord', 'akcje']);

        $akcje = new TextColumn(array('id' => 'akcje', 'title' => 'Przenieś do', 'source' => false, 'filterable' => false, 'sortable' => false));

        $grid->addColumn($akcje);

        $ktorzy = "disabled";

        if ($request->getMethod() == "POST") {
            $postData = $request->request->all();
            var_dump($postData);
            die("mam post");
        }

        /*
            // Edycja konta
            $rowAction = new RowAction('<i class="fas fa-pencil"></i> Odblokuj', 'unblock_user');
            $rowAction->setColumn('akcje');
            $rowAction->setRouteParameters(
                    array('samaccountname', 'ktorzy' => $ktorzy)
            );
            $rowAction->addAttribute('class', 'btn btn-success btn-xs');
            $grid->addRowAction($rowAction);
        */
        $grid->isReadyForRedirect();
        //var_dump($rowAction2);

        //print_r($users);
        //die();
        $keys = array_keys($disabled[0]);
        //unset($keys[count($keys) - 1]);
        //var_dump($keys, $disabled[0]); //die();
        return $grid->getGridResponse(['ktorzy' => $ktorzy, 'polaAD' => $keys]);
    }
}
