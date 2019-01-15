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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;

class AccessCheckerService
{

    protected $entityManager;
    protected $container;

    public function __construct(EntityManager $OrmEntity, Container $container = null)
    {
        $this->entityManager = $OrmEntity;
        $this->container = $container;
        if (PHP_SAPI == 'cli') {
            $this->container->set('request', new Request(), 'request');
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
            throw new AccessDeniedException("Brak uprawnień.");
        }
    }

    /**
     * Sprawdza czy można przeprowadzić operację
     * na danym wniosku lub powiązanym elemencie.
     *
     * @param mixed $object
     *
     * @return bool
     */
    public function checkWniosekIsBlocked($object, $id = null, $throwException = false)
    {
        $isBlocked = false;
        if (WniosekNadanieOdebranieZasobow::class === $object) {
            $wniosek = $this
                ->entityManager
                ->getRepository(WniosekNadanieOdebranieZasobow::class)
                ->findOneById($id)
            ;
        }

        if ($object instanceof WniosekNadanieOdebranieZasobow) {
            $wniosek = $object;
        }

        if (null !== $wniosek) {
            $isBlocked = $wniosek->getWniosek()->getIsBlocked();
        }

        if ($isBlocked && $throwException) {
            throw new AccessDeniedException('Wniosek jest ostatecznie zablokowany.');
        }

        return $isBlocked;
    }
}
