<?php declare(strict_types=1);

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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use ParpV1\JasperReportsBundle\Helper\FileHeadersHelper;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

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
     * @Route("/reports/print/{reportUri}.{format}", name="report_print", requirements={"reportUri"=".+"})
     *
     * @param string $reportUri
     * @param string $format
     *
     * @return Response
     */
    public function printReport(string $reportUri, string $format): Response
    {
        $printer = $this->get('jasper.report_print');
        $response = new Response($printer->printReport($this->getUser(), $reportUri, $format));
        $response
            ->headers
            ->add(FileHeadersHelper::resolve($format))
        ;

        return $response;
    }

    /**
     * Panel zarządzania dodanymi raportami i konfiguracją ról.
     *
     * @Route("/management", name="jasper_management")
     *
     * @Security("has_role('PARP_ADMIN')")
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
     * Usunięcie ściezki raportu.
     *
     * @Route("/path/remove/{path}", name="remove_path")
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param Path $path
     *
     * @return Response
     */
    public function removePath(Path $path): Response
    {
        $entityManager = $this
            ->getDoctrine()
            ->getManager()
        ;
        $pathId = $path->getId();
        $entityManager->remove($path);
        try {
            $entityManager->flush();
        }   catch (ForeignKeyConstraintViolationException $exception) {
            $this->addFlash(
                'danger',
                'Nie można usunąć ścieżki która jest powiązana z uprawnieniem.'
            );

            return $this->redirectToRoute('jasper_management');
        }


        $this->addFlash(
            'danger',
            'Usunięto wpis ścieżki. (ID: ' . $pathId . ')'
        );

        return $this->redirectToRoute('jasper_management');
    }

    /**
     * Edycja ściezki raportu.
     *
     * @Route("/path/edit/{path}", name="edit_path")
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param Request $request
     * @param Path $path
     *
     * @return Response
     */
    public function editPath(Request $request, Path $path): Response
    {
        $form = $this->createForm(PathFormType::class, $path);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this
                ->getDoctrine()
                ->getManager()
            ;
            $entityManager->persist($form->getData());
            $entityManager->flush();

            $this->addFlash(
                'warning',
                'Zmodyfikowano wpis ścieżki. (ID: ' . $path->getId() . ')'
            );

            return $this->redirectToRoute('jasper_management');
        }

        return $this->render('@ParpJasperReports/Path/add_edit_path.html.twig', [
            'form' => $form->createView()
        ]);
    }

     /**
     * Usunięcie wpisu uprawnienia do ściezki.
     *
     * @Route("/role_privilege/remove/{rolePrivilege}", name="remove_role_privilege")
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param RolePrivilege $path
     *
     * @return Response
     */
    public function removeRolePrivilege(RolePrivilege $rolePrivilege)
    {
        $entityManager = $this
            ->getDoctrine()
            ->getManager()
        ;

        $rolePrivilegeId = $rolePrivilege->getId();
        $entityManager->remove($rolePrivilege);
        $entityManager->flush();

        $this->addFlash(
            'danger',
            'Usunięto wpis uprawnienia do ścieżki. (ID: ' . $rolePrivilegeId . ')'
        );

        return $this->redirectToRoute('jasper_management');
    }

    /**
     * Zmienia nowe ustawienie rola <-> raport.
     *
     * @Route("/role_privilege/edit/{rolePrivilege}", name="edit_role_privilege")
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param Request $request
     * @param RolePrivilege $rolePrivilege
     *
     * @return Response
     */
    public function editRolePrivilege(Request $request, RolePrivilege $rolePrivilege): Response
    {
        $entityManager = $this
            ->getDoctrine()
            ->getManager()
        ;

        $form = $this->createForm(
            RolePrivilegeFormType::class,
            $rolePrivilege,
            ['entity_manager' => $entityManager]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($form->getData());
            $entityManager->flush();

            $this->addFlash(
                'warning',
                'Zmodyfikowano wpis uprawnień do ścieżki. (ID: ' . $rolePrivilege->getId() . ')'
            );

            return $this->redirectToRoute('jasper_management');
        }

        return $this->render('@ParpJasperReports/add_edit_role_privilege.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Dodaje nową ścięzkę raportu.
     *
     * @Route("/path/add", name="add_path")
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addPath(Request $request): Response
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

            return $this->redirectToRoute('jasper_management');
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
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addRolePrivilege(Request $request): Response
    {
        $entityManager = $this
            ->getDoctrine()
            ->getManager()
        ;

        $form = $this->createForm(
            RolePrivilegeFormType::class,
            new RolePrivilege(),
            ['entity_manager' => $entityManager]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($form->getData());
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Dodano nowe ustawienie roli.'
            );

            return $this->redirectToRoute('jasper_management');
        }

        return $this->render('@ParpJasperReports/add_edit_role_privilege.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
