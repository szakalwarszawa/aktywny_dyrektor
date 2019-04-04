<?php

namespace ParpV1\LdapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use ParpV1\MainBundle\Entity\Entry;
use DateTime;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\LdapBundle\MessageCollector\Constants\Types;
use ParpV1\LdapBundle\MessageCollector\Collector;
use Symfony\Component\HttpFoundation\Response;
use ParpV1\LdapBundle\Constants\GroupBy;
use ParpV1\LdapBundle\Form\PushChangesFormType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Podstawowa klasa kontrolera.
 *
 * @todo zmiana nazwy
 */
class DefaultController extends Controller
{
    /**
     * Wyświetla zmiany oczekujące (wpisy klasy Entry) i publikuje po akcji.
     *
     * @Route("/zmiany/opublikuj", name="opublikuj_zmiany_ldap")
     *
     * @Security("has_role('PARP_ADMIN_REJESTRU_ZASOBOW')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function publishLdapChangesAction(Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $entries = $entityManager
            ->getRepository(Entry::class)
            ->findChangesToImplement()
        ;

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
            $updateByEntry->doSimulateProcess();
        }

        if ($form->isSubmitted() && $form->isValid() && !$updateByEntry->hasError()) {
            $writeChanges = true;
        }

        foreach ($entries as $entry) {
            $updateByEntry->update($entry, true);
        }

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
        ]);
    }
}
