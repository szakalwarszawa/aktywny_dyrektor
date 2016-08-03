<?php

/**
 * Klasa ParpUserServiceService.
 *
 * @author Kamil Jakacki kamil_jakacki@parp.gov.pl
 *
 * @version 1.0.0
 */

namespace Parp\MainBundle\Services;

use Symfony\Component\DependencyInjection\Container;

/**
 * Usługa dostępna systemowo, której zadaniem jest sprawdzanie pewnych atrybutow useros (zaczynam od czyDyrektor).
 *
 * Class ParpUserServiceService
 */
class ParpUserServiceService
{
    public function kogoBracJakoManageraDlaUseraDoWniosku($user){
        $ret = 'dyrektor';
        switch(mb_strtolower($user['title'])){
            case "dyrektor":
            case "p.o. dyrektora":
            case "dyrektor (p.o.)":
            case "zastępca prezesa":
            case "zastępca prezesa (p.o.)":
                $ret = 'manager';
                break;
            case "prezes":
                $ret = 'prezes';
                break;
        }
        return $ret;
    }    
    
}