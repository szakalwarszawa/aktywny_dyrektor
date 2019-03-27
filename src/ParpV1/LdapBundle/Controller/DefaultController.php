<?php

namespace ParpV1\LdapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use ParpV1\MainBundle\Entity\Entry;
use DateTime;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\LdapBundle\MessageCollector\Constants\Types;
use ParpV1\LdapBundle\MessageCollector\Collector;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/ldaptest/{name}")
     */
    public function indexAction($name)
    {
        return new Response();
    }
}
