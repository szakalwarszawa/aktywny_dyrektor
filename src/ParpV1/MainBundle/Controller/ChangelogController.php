<?php

namespace ParpV1\MainBundle\Controller;

use ParpV1\MainBundle\Entity\Changelog;
use ParpV1\MainBundle\Form\Changelog1Type;
use ParpV1\MainBundle\Repository\ChangelogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/changelog")
 */
class ChangelogController extends Controller
{
    /**
     * @Route("/admin/", name="changelog_index", methods={"GET"})
     */
    public function index(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository('ParpMainBundle:Changelog')->findAll();

        return $this->render('ParpMainBundle:Changelog:index.html.twig', [
            'changelogs' => $entity,
        ]);
    }

    /**
     * @Route("/admin/new", name="changelog_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $changelog = new Changelog();
        $form = $this->createForm(Changelog1Type::class, $changelog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($changelog);
            $entityManager->flush();

            return $this->redirectToRoute('changelog_index');
        }

        return $this->render('ParpMainBundle:Changelog:new.html.twig', [
            'changelog' => $changelog,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/{id}", name="changelog_show", methods={"GET"})
     */
    public function show(Changelog $changelog): Response
    {
        return $this->render('ParpMainBundle:Changelog:show.html.twig', [
            'changelog' => $changelog,
        ]);
    }

    /**
     * @Route("/admin/{id}/edit", name="changelog_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Changelog $changelog): Response
    {
        $form = $this->createForm(Changelog1Type::class, $changelog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('changelog_index');
        }

        return $this->render('ParpMainBundle:Changelog:edit.html.twig', [
            'changelog' => $changelog,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/{id}", name="changelog_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Changelog $changelog): Response
    {
        if ($this->isCsrfTokenValid('delete'.$changelog->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($changelog);
            $entityManager->flush();
        }

        return $this->redirectToRoute('changelog_index');
    }
}
