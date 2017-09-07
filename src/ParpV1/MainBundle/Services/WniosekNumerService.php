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

class WniosekNumerService
{
    protected $doctrine;
    protected $container;

    public function __construct(EntityManager $OrmEntity, Container $container)
    {
        $this->doctrine = $OrmEntity;
        $this->container = $container;
    }
    public function nadajNumer($wniosek, $typ)
    {
        $rok = date('Y');
        $prefix = $typ == "wniosekONadanieUprawnien" ? "WU" : "WZ";
        $numer = $this->doctrine->getRepository('ParpMainBundle:WniosekNumer')->findNextNumer($typ, $rok);
        $wniosek->getWniosek()->setNumer($prefix."-".$numer->getNumer()."/".$rok);
        $wniosek->getWniosek()->setWniosekNumer($numer);
        $this->doctrine->persist($numer);
        //die('nadajNumer');
    }
    public function nadajPodNumer($wn, $wniosek, $numer)
    {
        $wniosekNumer = "";
        $prefix = $wniosek->getWniosek()->getWniosekNadanieOdebranieZasobow() ? "WU" : "WZ";
        //$prefix = $typ == "wniosekONadanieUprawnien" ? "WU" : "WZ";
        if ($wniosek->getWniosek()->getParent()) {
            //znaczy ze drugi stopien dzielenie wniosku
            $numerParenta = str_replace($prefix."-", "", str_replace("/".$wniosek->getWniosek()->getParent()->getWniosekNumer()->getRok(), "", $wniosek->getWniosek()->getNumer()));
            $wniosekNumer = $prefix."-".$numerParenta.".".$numer."/".$wniosek->getWniosek()->getParent()->getWniosekNumer()->getRok();
            //die($wniosekNumer);
        } else {
            //znaczy ze pierwszy stopien dzielenie wniosku
            $wniosekNumer = $prefix."-".$wniosek->getWniosek()->getWniosekNumer()->getNumer().".".$numer."/".$wniosek->getWniosek()->getWniosekNumer()->getRok();
        }
        $wn->getWniosek()->setNumer($wniosekNumer);
    }
}
