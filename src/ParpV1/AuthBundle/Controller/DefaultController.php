<?php

namespace ParpV1\AuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/login")
     * @Template()
     */
    public function loginAction()
    {
        $request = $this->getRequest();
        $session = $request->getSession();

//         get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $this->render('ParpAuthBundle:Default:login.html.twig', array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
            'roles'         => $this->getAkdRolesNames(),
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
        $roles = $entityManager->getRepository('ParpMainBundle:AclRole')->findAll();
        foreach ($roles as $role) {
            $roleDostepne[] = $role->getName();
        }

        return ($roleDostepne);
    }
}
