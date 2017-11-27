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

        $xx = $ldap->getPrzelozonyPracownika('tomasz_bonczak');

        // sprawdz czy ma zastepstwo
        $zastepstwo = $em->getRepository('ParpMainBundle:Zastepstwo')->znajdzZastepstwa($xx['samaccountname']);

        var_dump(empty($zastepstwo));
        // jezeli nie dyrektor to dodaj adres Pani(Przerobic na grupę) - POWIERNIK_ZARZADU

        // dodaj pracowników grup HelpDesk BI, KOMP

        // wyslij maila - nowa metoda w usłudze parp.mailer chyba

        var_dump($xx['samaccountname']);

        if (!empty($userEngagements)) {
            foreach ($userEngagements as $userEngagement) {

                $date = new \DateTime();
                $date->setDate($userEngagement->getYear(), $userEngagement->getMonth(), 1);
                $date->setTime(0, 0, 0);

                $today = new \DateTime();

                if($date <= $today){
                       $user = $ldap->getUserFromAD($userEngagement->getSamaccountname());
                       //$user = $users[$userEngagement->getSamaccountname()];
                       var_dump($user);
                }

            }
        }
    }

}
