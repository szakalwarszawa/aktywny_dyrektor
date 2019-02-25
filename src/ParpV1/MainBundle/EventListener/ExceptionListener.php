<?php

namespace ParpV1\MainBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use ParpV1\AppBundle\Exception\LsiException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException as AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use ParpV1\MainBundle\Exception\SecurityTestException;

class ExceptionListener implements EventSubscriberInterface
{

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * @var Security
     */
    private $security;

    /**
     * @param TwigEngine $twinEngine
     */
    public function __construct(KernelInterface $kernel, TwigEngine $templating)
    {
        $this->kernel = $kernel;
        $this->srodowisko = $this->okreslSrodowiskoUruchomieniowe();
        $this->templating = $templating;
    }

    private function okreslSrodowiskoUruchomieniowe()
    {
        $srodowisko = 'prod';
        $kernel = $this->kernel;
        if ($kernel instanceof KernelInterface) {
            $srodowisko = $kernel->getEnvironment();
            $scislePorownanie = true;
            $srodowisko = (!in_array($srodowisko, array('dev', 'test'))) ? 'prod' : $srodowisko;
        }

        return $srodowisko;
    }

    /**
     * @see EventSubscriberInterface
     *
     * @return string[] Tablica nazw nasłuchiwanych zdarzeń.
     */
    public static function getSubscribedEvents()
    {
        $zdarzenia = array(
            KernelEvents::EXCEPTION => array('onKernelException', -127),
        );

        return $zdarzenia;
    }

    /**
     * Uwaga! Wywołanie getKernel() na obiekcie GetResponseForExceptionEvent zwraca
     * obiekt typu HttpKernelInterface, który nie  będzie posiadał informacji o
     * środowisku uruchomieniowym aplikacji. Z tego powodu określanie środowiska
     * zostało przeniesione do konstruktora.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $srodowisko = $this->srodowisko;
        if ($srodowisko === 'prod') {
            $userId = 0;
            $uprawnienia = array();
            $email = "kamil_jakacki@parp.gov.pl";
            $imie_nazwisko = "";
            $userId = 0;
            if ($this->kernel->getContainer()->get('security.token_storage')->getToken()) {
                $userData = $user = $this->kernel->getContainer()->get('security.token_storage')->getToken()->getUser();
                $userId = $userData->getUsername();
                $imie_nazwisko = $userData->getUsername();
//var_dump($userData);die();
/*
                $userId = $userData->getId();
                $email = $userData->getEmail();
                $imie_nazwisko = $userData->getImie().' '.$userData->getNazwisko();
*/

                $em = $this->kernel->getContainer()->get('doctrine')->getManager();
                $uprawnienia = $userData->getRoles();//$em->getRepository('ParpV1\UzytkownikBundle\Entity\Uzytkownik')->findOneById($userId)->getRoles();
            }

            $wyjatek = $event->getException();

            if ($this->kernel->getContainer()->getParameter('id_srodowiska')=='testowy') {
                var_dump($wyjatek->getMessage());
            }
            //print_r($wyjatek);die();
            // jezeli uzytkownik PARP-owy wyświetel mu kounikat o błędzie w inny sposób bez zapisywania w redmine
            // lub jesli to blad zwiazany z zabezpieczeniem testowym
            if (in_array('ROLE_USER', $uprawnienia) || $wyjatek instanceof SecurityTestException) {
                $odpowiedz = new Response($this->templating->render(':status:lsiParpException.html.twig', array(
                            'wyjatek' => $wyjatek,
                )));
            } else {
                if ($wyjatek instanceof NotFoundHttpException) {
                    $odpowiedz = new Response($this->templating->render(':status:httpNotFound.html.twig'), 404);
                } elseif ($wyjatek instanceof AccessDeniedHttpException) {
                    $odpowiedz = new Response($this->templating->render(':status:accessDenied.html.twig'), 404);
                } elseif (!$wyjatek instanceof LsiException) {
                    $komunikat_systemowy = $wyjatek->getCode() . ' ' . $wyjatek->getMessage();

                    $opis = $wyjatek->getMessage();
                    $temat = $opis;
                    $kategoria = 10; # Zgłoszone przez system

                    $redmine = $this->kernel->getContainer()->get('parp.redmine');
                    // putZgloszenieBeneficjenta($id_beneficjenta, $temat, $opis, $kategoria, $uri = null, $czy_prywatna = true,$komunikat_systemowy = null,$zgloszenie_id = null,$podmiot = null,$email = null, $telefon = null, $imie_nazwisko = null);
                    $json = $redmine->putZgloszenieBeneficjenta($userId, $temat, $wyjatek, $kategoria, null, true, $komunikat_systemowy, null, null, $email, null, $imie_nazwisko);

                    // wyciagnij numer zgłoszenia
                    $tablica = json_decode($json, true);
                    $nr_zgloszenia = $tablica['issue']['id'];

                    if ($nr_zgloszenia) {
                        $redmine->dodajNotatke($nr_zgloszenia);
                    }

                    $komunikat = 'Nieznany błąd!';
                    $odpowiedz = new Response($this->templating->render(':status:500.html.twig', array(
                                'status_text' => $komunikat,
                                'nr_zgloszenia' => $nr_zgloszenia,
                    )));
                } else {
                    $odpowiedz = new Response($this->templating->render(':status:lsiException.html.twig', array(
                                'status_text' => $wyjatek->getMessage(),
                    )));
                }
            }

            $event->setResponse($odpowiedz);
        }
    }
}
