<?php
namespace ParpV1\MainBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrineExtensionListener
{
    use ContainerAwareTrait;

    public function onLateKernelRequest(GetResponseEvent $event)
    {
        //$translatable = $this->container->get('gedmo.listener.translatable');
        //$translatable->setTranslatableLocale($event->getRequest()->getLocale());
    }
    public function preUpdate($event)
    {
        //die('b');
        $event->setNewValue('grupyHistoriaZmian', $event->getEntity()->getGrupyHistoriaZmian());
        $nv = $event->getEntity()->getGrupyHistoriaZmian();

        print_r($nv);
        die('   preUpdate '.get_class($event));
    }

    public function onConsoleCommand()
    {
        //$this->container->get('gedmo.listener.translatable')
            //->setTranslatableLocale($this->container->get('translator')->getLocale());
    }
    public function onKernelRequest(GetResponseEvent $event)
    {
        $tokenStorage = $this->container->get('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if (null !== $tokenStorage && null !== $tokenStorage->getToken() && $tokenStorage->getToken()->isAuthenticated()) {
            $loggable = $this->container->get('gedmo.listener.loggable');
            $loggable->setUsername($tokenStorage->getToken()->getUsername());
        }
    }
}
