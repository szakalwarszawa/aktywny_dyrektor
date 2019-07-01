<?php

namespace ParpV1\JasperReportsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use ParpV1\JasperReportsBundle\Form\PathFormType;
use Symfony\Component\HttpFoundation\Request;
use ParpV1\JasperReportsBundle\Entity\Path;
use ParpV1\JasperReportsBundle\Form\RolePrivilegeFormType;
use ParpV1\JasperReportsBundle\Entity\RolePrivilege;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/reports/list", name="reports_list")
     */
    public function reportsList()
    {
        $grid = $this
            ->get('jasper.reports_grid')
            ->generateForUser($this->getUser())
        ;

        return $grid->getGridResponse('@ParpJasperReports/reports_list.html.twig', []);
    }

    /**
     * @Route("/reports/print/{reportUri}", name="report_print", requirements={"reportUri"=".+"})
     */
    public function printReport(Request $request, string $reportUri)
    {
        $printer = $this->get('jasper.report_print');
        $response = new Response($printer->printReport($reportUri));
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }

    /**
     * @Route("/path/add", name="add_path")
     */
    public function addNewPath(Request $request)
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
     * @Route("/role_privilege/add", name="add_role_privilege")
     */
    public function addNewRolePrivilege(Request $request)
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
