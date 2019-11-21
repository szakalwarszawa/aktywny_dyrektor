<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Exception;

/**
 * Spis uprawnień własnych pracownika oraz uprawnień pracowników D/B
 */
class SpisUprawnienController extends Controller
{
    /**
     * @Route("/spis_uprawnien", name="spis_uprawnien")
     */
    public function index()
    {
        return $this->render('ParpMainBundle:SpisUprawnien:index.html.twig', [
            'test' => 'SpisUprawnienController',
        ]);
    }

    /**
     * Zestawienie uprawnień do zasobów dla zalogowanego użytkownika.
     *
     * @Route("/zasoby_uzytkownika/{aktywne}", name="zasoby_uzytkownika", defaults={"aktywne" : true})
     * @Route("/", name="zasoby_uzytkownika_home")
     *
     * @param bool $aktywne
     *
     * @return Response
     */
    public function zasobyUzytkownikaAction(bool $aktywne = true): Response
    {
        $grid = $this
            ->get('zasoby_uzytkownika_grid')
            ->generateForUser($this->getUser()->getUsername(), $aktywne);

        return $grid->getGridResponse('ParpMainBundle:SpisUprawnien:lista_zasobow_uzytkownika_grid.html.twig', [
            'grid' => $grid,
            'aktywne' => $aktywne
        ]);
    }

    /**
     * Zestawienie uprawnień do zasobów dla wybranego użytkownika.
     *
     * @Route("/zasoby_pracownika/{samaccountname}/{aktywne}", name="zasoby_pracownika", defaults={"aktywne" : true})
     *
     * @Security("has_role('PARP_D_DYREKTOR')")
     *
     * @param string $samaccountname
     * @param bool   $aktywne
     *
     * @throws Exception
     *
     * @return Response
     */
    public function zasobyPracownikaAction(string $samaccountname, bool $aktywne = true): Response
    {
        $ldap = $this->get('ldap_service');
        $adDyrektor = $ldap->getUserFromAD($this->getUser()->getUsername());
        $adPracownik = $ldap->getUserFromAD($samaccountname);

        if (trim($adDyrektor[0]['department']) !== trim($adPracownik[0]['department'])) {
            throw new Exception('Nie możesz przeglądać uprawnień pracowników z innych D/B.');
        }

        $grid = $this
            ->get('zasoby_uzytkownika_grid')
            ->generateForUser($samaccountname, $aktywne);

        return $grid->getGridResponse('ParpMainBundle:SpisUprawnien:lista_zasobow_pracownikaDB_grid.html.twig', [
            'grid' => $grid,
            'aktywne' => $aktywne,
            'samaccountname' => $samaccountname
        ]);
    }

    /**
     * Zestawienie pracowników D/B - lista dla Dyrektora
     *
     * @Route("/pracownicy_db", name="pracownicy_db")
     *
     * @Security("has_role('PARP_D_DYREKTOR')")
     *
     * @throws Exception
     */
    public function pracownicyDbAction()
    {
        $ldap = $this->get('ldap_service');
        $aduser = $ldap->getUserFromAD($this->getUser()->getUsername());

        $skrotDb = trim($aduser[0]['description']);
        $departament = trim($aduser[0]['department']);

        if (!preg_match("/^[A-Z]{2,3}$/", $skrotDb)) {
            throw new Exception(
                sprintf(
                    'Błędny D/B: "%s" u pracownika: "%s"',
                    $skrotDb,
                    $this->getUser()->getUsername()
                    )
            );
        }

        $pracownicyOu = $ldap->getPracownicyDepartamentu($skrotDb);

        $grid = $this
            ->get('pracownicy_db_grid')
            ->getUserGrid(
                $pracownicyOu,
                $departament,
                $this->getUser()->getRoles()
            );

        if ($grid->isReadyForExport()) {
            return $grid->getExportResponse();
        }

        return $grid->getGridResponse('ParpMainBundle:SpisUprawnien:index.html.twig', [
            'departament' => $departament
        ]);
    }
}
