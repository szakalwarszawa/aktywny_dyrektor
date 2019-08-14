<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Controller;

use ParpV1\MainBundle\Entity\Changelog;
use ParpV1\MainBundle\Form\ChangelogType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @Route("/changelog")
 */
class ChangelogController extends Controller
{
    /**
     * Stronnicowanie - domyślna liczba wpisów na stronę
     *
     * @var int
     */
    const ILE_WPISOW_NA_STRONE = 2;

    /**
     * Lista wszystkich opublikowanych wpisów Changeloga.
     *
     * @Route("/", name="changelog_index", methods={"GET"})
     *
     * @param Request $request
     * @param int     $wynikowNaStrone
     *
     * @return Response
     */
    public function index(Request $request, $wynikowNaStrone = self::ILE_WPISOW_NA_STRONE): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository(Changelog::class)
            ->findBy(['opublikowany' => true], ['id' => 'DESC']);

        $adapter = new ArrayAdapter($entity);
        $page = (int)($request->query->get('page') == null ?  1 : $request->query->get('page'));
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($wynikowNaStrone);
        $pagerfanta->setCurrentPage($page);

        return $this->render('ParpMainBundle:Changelog:index.html.twig', [
            'changelogs' => $pagerfanta,
        ]);
    }

    /**
     * Administracyjna lista wszystkich wpisów Changelogaew.
     *
     * @Route("/admin/", name="changelog_admin_index", methods={"GET"})
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @return Response
     */
    public function indexAdmin(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository(Changelog::class)->findAll();

        return $this->render('ParpMainBundle:Changelog:index-admin.html.twig', [
            'changelogs' => $entity,
        ]);
    }

    /**
     * Tworzy nowy wpis changeloga.
     *
     * @Route("/admin/new", name="changelog_new", methods={"GET","POST"})
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function new(Request $request): Response
    {
        $changelog = (new Changelog())
            ->setSamaccountname($this->getUser()->getUsername());

        $form = $this->createForm(ChangelogType::class, $changelog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($changelog);
            $entityManager->flush();

            return $this->redirectToRoute('changelog_admin_index');
        }

        return $this->render('ParpMainBundle:Changelog:new.html.twig', [
            'changelog' => $changelog,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Wyświetla wpis changeloga w trybie podglądu.
     *
     * @Route("/admin/{id}", name="changelog_show", methods={"GET"})
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param Changelog $changelog
     *
     * @return Response
     */
    public function show(Changelog $changelog): Response
    {
        return $this->render('ParpMainBundle:Changelog:show.html.twig', [
            'changelog' => $changelog,
        ]);
    }

    /**
     * Wyświetla formularz edycji wpisu changeloga.
     *
     * @Route("/admin/{id}/edit", name="changelog_edit", methods={"GET","POST"})
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param Request $request
     * @param Changelog $changelog
     *
     * @return Response
     */
    public function edit(Request $request, Changelog $changelog): Response
    {
        $form = $this->createForm(ChangelogType::class, $changelog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('changelog_admin_index');
        }

        return $this->render('ParpMainBundle:Changelog:edit.html.twig', [
            'changelog' => $changelog,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Usuwa wpis changeloga.
     *
     * @Route("/admin/{id}", name="changelog_delete", methods={"DELETE"})
     *
     * @Security("has_role('PARP_ADMIN')")
     *
     * @param Request $request
     * @param Changelog $changelog
     *
     * @return Response
     */
    public function delete(Request $request, Changelog $changelog): Response
    {
        if ($this->isCsrfTokenValid('delete'.$changelog->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($changelog);
            $entityManager->flush();
        }

        return $this->redirectToRoute('changelog_admin_index');
    }
}
