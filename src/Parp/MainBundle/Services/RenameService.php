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
    public function __construct(){
        
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
}