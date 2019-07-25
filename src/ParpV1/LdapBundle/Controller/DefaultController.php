<?php

namespace ParpV1\LdapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use ParpV1\LdapBundle\Constants\GroupBy;
use ParpV1\LdapBundle\Form\PushChangesFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use ParpV1\MainBundle\Entity\Wniosek;
use ParpV1\LdapBundle\Voter\AdPublishVoter;

/**
 * Podstawowa klasa kontrolera.
 */
class DefaultController extends Controller
{
    /**
     * Wyświetla zmiany oczekujące (wpisy klasy Entry) i publikuje po akcji.
     *
     * Druga ścieżka udostepnia administratorom zasobów publikację uprawnień z wniosku.
     *
     * @Route("/zmiany/opublikuj", name="opublikuj_zmiany_ldap")
     * @Route("/zmiany/opublikuj/{application}", name="opublikuj_zmiany_ldap_wniosek")
     *
     *
     * @param Request $request
     * @param Wniosek $application
     *
     * @return Response
     */
    public function publishLdapChangesAction(Request $request, Wniosek $application = null): Response
    {
        $this->denyAccessUnlessGranted(AdPublishVoter::PUBLISH_CHANGES, $application);

        $updateByEntry = $this->get('ldap.update_from_entry');

        $isSimulation = false;
        $writeChanges = false;
        $form = $this->createForm(PushChangesFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            throw new AccessDeniedException();
        }

        if (!$request->isMethod('POST')) {
            $isSimulation = true;
        }

        if ($form->isSubmitted() && $form->isValid() && !$updateByEntry->hasError()) {
            $writeChanges = true;
        }

        $updateByEntry->publishAllPendingChanges($isSimulation, false, $application);

        if ($writeChanges && !$updateByEntry->hasError()) {
            $this
                ->getDoctrine()
                ->getManager()
                ->flush()
            ;
        }

        return $this->render('@ParpLdap/main/user_changes.html.twig', [
            'change_log' => $updateByEntry->getResponseMessages(GroupBy::LOGIN),
            'is_simulation' => $isSimulation,
            'form' => $form->createView(),
            'has_error' => $updateByEntry->hasError(),
            'application_id' => $application,
        ]);
    }
}
