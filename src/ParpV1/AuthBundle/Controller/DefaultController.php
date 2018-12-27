<?php

namespace ParpV1\AuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use ParpV1\MainBundle\Entity\AclRole;

class DefaultController extends Controller
{
    /**
     * @Route("/login")
     * @Template()
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');
        $userLoginService = $this->get('parp.user_login_service');

        $availableRoles = $userLoginService->getAkdRolesNames();

        return $this->render('ParpAuthBundle:Default:login.html.twig', array(
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
            'roles'         => $availableRoles,
        ));
    }

    /**
     * @Route("/login_check", name="login_check");
     */
    public function logincheck()
    {
        return true;
    }

    /**
     * @Route("/logout", name="logout");
     */
    public function logout()
    {
        return true;
    }

    /**
     * Zwraca nazwy wszystkich dostępnych ról
     *
     * @return array
     */
    private function getAkdRolesNames()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $roles = $entityManager->getRepository(AclRole::class)->findAll();
        foreach ($roles as $role) {
            $roleDostepne[] = $role->getName();
        }

        return ($roleDostepne);
    }
}
