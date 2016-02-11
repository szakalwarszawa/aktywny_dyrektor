<?php

namespace Parp\CronBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/cron/ldap")
     * @Template()
     */
    public function ldapmodifyAction()
    {
        return array();
    }
}
