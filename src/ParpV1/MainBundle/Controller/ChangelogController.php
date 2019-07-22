<?php

namespace ParpV1\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * ChangelogController
 */
class ChangelogController extends Controller
{
    /**
     * @Route("/changelog")
     *
     * @return Response
     */
    public function showChangelogAction(): Response
    {
        return $this->render('ParpMainBundle:Changelog:show_changelog.html.twig', [
            'controller_name' => 'ChangelogController',
        ]);
    }
    /**
     * @Route("/changelog/{wersja}")
     *
     * @param string $wersja Nur wersji AkD, eg. 'v2.1.1'
     *
     * @return Response
     */
    public function showVersionAction(string $wersja): Response
    {
        return $this->render('ParpMainBundle:Changelog:show_version.html.twig', [
            'controller_name' => 'ChangelogController',
        ]);
    }
}
