<?php

/**
 * Description of RightsServices
 *
 * @author tomasz_bonczak
 */

namespace ParpV1\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use ParpV1\MainBundle\Entity\UserUprawnienia;
use ParpV1\MainBundle\Entity\UserGrupa;
use ParpV1\MainBundle\Services\RedmineConnectService;

class AccessCheckerService
{

    protected $doctrine;
    protected $container;

    public function __construct(EntityManager $OrmEntity, Container $container)
    {
        $this->doctrine = $OrmEntity;
        $this->container = $container;
        if (PHP_SAPI == 'cli') {
            $this->container->set('request', new \Symfony\Component\HttpFoundation\Request(), 'request');
        }
    }

    public function checkAccess($actionName)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $action = $this->container->get('doctrine')->getRepository('ParpMainBundle:AclAction')->findOneBySkrot($actionName);
        $ret = true;
        if ($action) {
            $ret = false;
            foreach ($action->getRoles() as $r) {
                $ret = $ret || in_array($r->getName(), $user->getRoles());
            }
        }
        if (!$ret) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException("Brak uprawnień.");
        }
    }
}
