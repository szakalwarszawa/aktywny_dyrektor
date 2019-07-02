<?php

namespace ParpV1\JasperReportsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use ParpV1\JasperReportsBundle\Form\PathFormType;
use Symfony\Component\HttpFoundation\Request;
use ParpV1\JasperReportsBundle\Entity\Path;
use ParpV1\JasperReportsBundle\Form\RolePrivilegeFormType;
use ParpV1\JasperReportsBundle\Entity\RolePrivilege;
use Symfony\Component\HttpFoundation\Response;
use ParpV1\JasperReportsBundle\Grid\PathsGrid;
use ParpV1\JasperReportsBundle\Grid\PathRoleGrid;

class DefaultController extends Controller
{
    /**
     * Wyświetla dostępne raporty.
     *
     * @Route("/reports/list", name="reports_list")
     *
     * @return Response
     */
    public function reportsList(): Response
    {
        $grid = $this
            ->get('jasper.reports_grid')
            ->generateForUser($this->getUser())
        ;

        return $grid->getGridResponse('@ParpJasperReports/reports_list.html.twig', []);
    }

    /**
     * Drukuje raport.
     *
     * @Route("/reports/print/{reportUri}", name="report_print", requirements={"reportUri"=".+"})
     *
     * @param Request $request
     * @param string $reportUri
     *
     * @return Response
     */
    public function printReport(string $reportUri): Response
    {
        $printer = $this->get('jasper.report_print');
        $response = new Response($printer->printReport($reportUri));
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }

    /**
     * Panel zarządzania dodanymi raportami i konfiguracją ról.
     *
     * @Route("/management", name="management")
     *
     * @return Response
     */
    public function management(): Response
    {
        $entityManager = $this
            ->getDoctrine()
            ->getManager()
        ;
        $pathsGridData = $entityManager
            ->getRepository(Path::class)
            ->findDataToGrid()
        ;
        $gridClass = $this->get('grid');
        $pathsGrid = new PathsGrid($gridClass, $pathsGridData);
        $pathsGrid = $pathsGrid->getGrid();

        $pathRoleGridData = $entityManager
            ->getRepository(RolePrivilege::class)
            ->findDataToGrid()
        ;
        $gridClass = $this->get('grid');
        $pathRoleGrid = new PathRoleGrid($gridClass, $pathRoleGridData);
        $pathRoleGrid = $pathRoleGrid->getGrid();

        return $gridClass->getGridResponse('@ParpJasperReports/management.html.twig', [
            'paths_grid' => $pathsGrid,
            'path_role_grid' => $pathRoleGrid,
        ]);
    }

    /**
     * Dodaje nową ścięzkę raportu.
     *
     * @Route("/path/add", name="add_path")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addNewPath(Request $request): Response
    {
        $form = $this->createForm(PathFormType::class, new Path());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this
                ->getDoctrine()
                ->getManager()
            ;
            $entityManager->persist($form->getData());
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Dodano nową ścieżkę raportu.'
            );

            return $this->redirectToRoute('reports_list');
        }

        return $this->render('@ParpJasperReports/Path/add_edit_path.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Dodaje nowe ustawienie rola <-> raport.
     *
     * @Route("/role_privilege/add", name="add_role_privilege")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addNewRolePrivilege(Request $request): Response
    {
        $entityManager = $this
            ->getDoctrine()
            ->getManager()
        ;

        $form = $this->createForm(
            RolePrivilegeFormType::class,
            new RolePrivilege(),
            [
                'entity_manager' => $entityManager
            ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($form->getData());
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Dodano nowe ustawienie roli.'
            );

            return $this->redirectToRoute('reports_list');
        }

        return $this->render('@ParpJasperReports/add_edit_role_privilege.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
