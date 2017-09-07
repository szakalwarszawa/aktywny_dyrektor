<?php

namespace ParpV1\AuthBundle\Handler;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Class SessionIdleHandler
 * @package Parp\AuthBundle\Handler
 */
class SessionIdleHandler
{

    protected $session;
    protected $securityContext;
    protected $router;
    protected $maxIdleTime;

    /**
     * SessionIdleHandler constructor.
     * @param SessionInterface $session
     * @param SecurityContextInterface $securityContext
     * @param RouterInterface $router
     * @param int $maxIdleTime
     */
    public function __construct(SessionInterface $session, SecurityContextInterface $securityContext, RouterInterface $router, $maxIdleTime = 0)
    {
        $this->session = $session;
        $this->securityContext = $securityContext;
        $this->router = $router;
        $this->maxIdleTime = $maxIdleTime;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        if ($this->maxIdleTime > 0) {
            $this->session->start();
            $lapse = time() - $this->session->getMetadataBag()->getLastUsed();

            if ($lapse > $this->maxIdleTime) {
                $this->securityContext->setToken(null);
                $this->session->getFlashBag()->set('info', 'You have been logged out due to inactivity.');

                // Change the route if you are not using FOSUserBundle.
                $event->setResponse(new RedirectResponse($this->router->generate('main_home')));
            }
        }
    }
}
