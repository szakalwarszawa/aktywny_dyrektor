<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Controller;

use ParpV1\MainBundle\Entity\PositionGroups;
use ParpV1\MainBundle\Form\PositionGroupsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/position/groups")
 */
class PositionGroupsController extends Controller
{
    /**
     * Wyświetla listę grup stanowisk
     *
     *  @Route("/", name="position_groups_index", methods={"GET"})
     *
     * @return Response
     */
    public function index(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $positionGroups = $entityManager->getRepository(PositionGroups::class);

        return $this->render('ParpMainBundle:PositionGroups:index.html.twig', [
            'position_groups' => $positionGroups->findAll(),
        ]);
    }

    /**
     * Tworzy nową grupę stanowisk
     *
     * @Route("/new", name="position_groups_new", methods={"GET","POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function new(Request $request): Response
    {
        $positionGroup = new PositionGroups();
        $form = $this->createForm(PositionGroupsType::class, $positionGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($positionGroup);
            $entityManager->flush();

            return $this->redirectToRoute('position_groups_index');
        }

        return $this->render('ParpMainBundle:PositionGroups:new.html.twig', [
            'position_group' => $positionGroup,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Wyświetla wybraną grupę stanowisk
     *
     * @Route("/{id}", name="position_groups_show", methods={"GET"})
     *
     * @param PositionGroups $positionGroup
     *
     * @return Response
     */
    public function show(PositionGroups $positionGroup): Response
    {
        return $this->render('ParpMainBundle:PositionGroups:show.html.twig', [
            'position_group' => $positionGroup,
            'positions' => $positionGroup->getPositions(),
        ]);
    }

    /**
     * Edycja wybranej grupy stanowisk
     *
     * @Route("/{id}/edit", name="position_groups_edit", methods={"GET","POST"})
     *
     * @param Request $request
     * @param PositionGroups $positionGroup
     *
     * @return Response
     */
    public function edit(Request $request, PositionGroups $positionGroup): Response
    {
        $form = $this->createForm(PositionGroupsType::class, $positionGroup);
        $form->add(
            'submit',
            SubmitType::class,
            array(
                'label' => 'Zapisz zmiany',
                'attr' => array(
                    'class' => 'btn btn-success'
                )
            )
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('position_groups_index', [
                'id' => $positionGroup->getId(),
            ]);
        }

        return $this->render('ParpMainBundle:PositionGroups:edit.html.twig', [
            'position_group' => $positionGroup,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Usunięcie wybranej grupy stanowisk
     *
     * @Route("/{id}", name="position_groups_delete", methods={"DELETE"})
     *
     * @param Request $request
     * @param PositionGroups $positionGroup
     *
     * @return Response
     */
    public function delete(Request $request, PositionGroups $positionGroup): Response
    {
        if ($this->isCsrfTokenValid('delete' . $positionGroup->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($positionGroup);
            $entityManager->flush();
        }

        return $this->redirectToRoute('position_groups_index');
    }
}
