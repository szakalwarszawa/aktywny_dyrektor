<?php

namespace ParpV1\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EngagementCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
                ->setName('parp:engagement')
                ->setDescription('Wysyła maile o zmianie zaangażowań pracowników do opowiednich osób')
                ->setHelp('Wysyła maile o zmianie zaangażowań pracowników do opowiednich osób');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $mailer = $container->get('parp.mailer');
        $ldap = $container->get('ldap_service');

        $userEngagements = $em->getRepository('ParpMainBundle:UserEngagement')->findByCzyNowy(true);

        if (!empty($userEngagements)) {
 echo count($userEngagements);
        }
    }

}
