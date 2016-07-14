<?php

/**
 * Description of RightsServices
 *
 * @author tomasz_bonczak
 */

namespace Parp\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Parp\MainBundle\Entity\UserUprawnienia;
use Parp\MainBundle\Entity\UserGrupa;
use Parp\MainBundle\Services\RedmineConnectService;

class WniosekNumerService
{
    protected $doctrine;
    protected $container;

    public function __construct(EntityManager $OrmEntity, Container $container)
    {
        $this->doctrine = $OrmEntity;
        $this->container = $container;
        
    }
    public function nadajNumer($wniosek, $typ){
        $rok = date('Y');
        $numer = $this->doctrine->getRepository('ParpMainBundle:WniosekNumer')->findNextNumer($typ, $rok);
        $wniosek->getWniosek()->setWniosekNumer($numer);
        $this->doctrine->persist($numer);
        //die('nadajNumer');
    }
}