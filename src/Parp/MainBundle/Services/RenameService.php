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

class RenameService
{
    protected $doctrine;
    protected $container;

    public function __construct(EntityManager $OrmEntity, Container $container)
    {
        $this->doctrine = $OrmEntity;
        $this->container = $container;
        
    }
    public function fixImieNazwisko($imienazwisko)
    {
        $p = explode(" ", $imienazwisko);
        return $p[1]." ".$p[0];
    }
    public function objectTitles($var)
    {
        $titles = array(
            'UserZasoby' => 'Uprawnienia użytkownika do zasobu',
            'WniosekNadanieOdebranieZasobowEditor' => 'Możliwość edycji',
            'WniosekNadanieOdebranieZasobowViewer' => 'Możliwość podglądu',
            'WniosekNadanieOdebranieZasobow' => 'Wniosek o Nadanie uprawnień',
        );
        //die($var);
        return (isset($titles[$var]) ? $titles[$var] : $var);
    }
    
    
    public function actionTitles($var)
    {
        $titles = array(
            'create' => 'Utworzenie',
            'update' => 'Edycja',
            'remove' => 'Usunięcie',
        );
        return (isset($titles[$var]) ? $titles[$var] : $var);
    }
    
    public function zasobNazwa($zid){
        //echo ".".$zid.".";
        $z = null;
        try{
            $this->doctrine->getFilters()->disable('softdeleteable');
            $z = $this->doctrine->getRepository('ParpMainBundle:Zasoby')->find($zid);
            
            $this->doctrine->getFilters()->enable('softdeleteable');
        }catch(\Exception $e){
            echo $e->getMessage();
        }
        
        return $z ? $z->getNazwa() : $zid;    
    }
}