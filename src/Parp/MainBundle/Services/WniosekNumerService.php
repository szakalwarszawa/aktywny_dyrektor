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
        $wniosek->getWniosek()->setNumer($numer->getNumer()."/".$rok);
        $wniosek->getWniosek()->setWniosekNumer($numer);
        $this->doctrine->persist($numer);
        //die('nadajNumer');
    }
    public function nadajPodNumer($wn,$wniosek, $numer){
        $wniosekNumer = "";
        if($wniosek->getWniosek()->getParent()){
            //znaczy ze drugi stopien dzielenie wniosku
            $numerParenta = str_replace("/".$wniosek->getWniosek()->getParent()->getWniosekNumer()->getRok(), "", $wniosek->getWniosek()->getNumer());
            $wniosekNumer = $numerParenta.".".$numer."/".$wniosek->getWniosek()->getParent()->getWniosekNumer()->getRok();
            //die($wniosekNumer);
        }else{
            //znaczy ze pierwszy stopien dzielenie wniosku
            $wniosekNumer = $wniosek->getWniosek()->getWniosekNumer()->getNumer().".".$numer."/".$wniosek->getWniosek()->getWniosekNumer()->getRok();
        }
        $wn->getWniosek()->setNumer($wniosekNumer);
    }
}