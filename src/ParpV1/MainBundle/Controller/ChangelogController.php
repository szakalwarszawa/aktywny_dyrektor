<?php

namespace ParpV1\MainBundle\Controller;

use ParpV1\MainBundle\Entity\Changelog;
use ParpV1\MainBundle\Form\ChangelogType;
use ParpV1\MainBundle\Repository\ChangelogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/changelog")
 */
class ChangelogController extends Controller
{
    /**
     * Lists all Changelog entities in public view.
     *
     * @Route("/", name="changelog_index", methods={"GET"})
     */
    public function index(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository('ParpMainBundle:Changelog')
            ->findBy(['opublikowany' => true], ['id' => 'DESC']);


        return $this->render('ParpMainBundle:Changelog:index.html.twig', [
            'changelogs' => $entity,
        ]);
    }

    /**
     * Lists all Changelog entities in admin view.
     *
     * @Route("/admin/", name="changelog_admin_index", methods={"GET"})
     *
     * @Security("has_role('PARP_ADMIN')")
     */
    public function indexAdmin(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository('ParpMainBundle:Changelog')->findAll();

        return $this->render('ParpMainBundle:Changelog:index-admin.html.twig', [
            'changelogs' => $entity,
        ]);
    }

    /**
     * Creates a new Changelog entity.
     *
     * @Route("/admin/new", name="changelog_new", methods={"GET","POST"})
     *
     * @Security("has_role('PARP_ADMIN')")
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
     * Finds and displays a Changelog entity in admin view.
     *
     * @Route("/admin/{id}", name="changelog_show", methods={"GET"})
     *
     * @Security("has_role('PARP_ADMIN')")
     */
    public function show(Changelog $changelog): Response
    {
        return $this->render('ParpMainBundle:Changelog:show.html.twig', [
            'changelog' => $changelog,
        ]);
    }

    /**
     * Displays a form to edit an existing Changelog entity.
     *
     * @Route("/admin/{id}/edit", name="changelog_edit", methods={"GET","POST"})
     *
     * @Security("has_role('PARP_ADMIN')")
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
     * Deletes a Changelog entity
     *
     * @Route("/admin/{id}", name="changelog_delete", methods={"DELETE"})
     *
     * @Security("has_role('PARP_ADMIN')")
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
