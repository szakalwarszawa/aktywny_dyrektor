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

        $adresyDo = [];

        // dodaj pracowników grup HelpDesk BI, KOMP
        $grupaHelpdesk = $em->getRepository('ParpMainBundle:AclRole')->findOneByName('PARP_HELPDESK_BI');
        if (!empty($grupaHelpdesk->getUsers())) {
            foreach($grupaHelpdesk->getUsers() as $user){
                $samaccountname= $user->getSamaccountname();
                if(!in_array($samaccountname, $adresyDo)){
                    $adresyDo[] = $samaccountname;
                }
            }
        }

        $grupaKomp = $em->getRepository('ParpMainBundle:AclRole')->findOneByName('PARP_KOMP');
        if (!empty($grupaKomp->getUsers())) {
            foreach($grupaKomp->getUsers() as $user){
                $samaccountname= $user->getSamaccountname();
                if(!in_array($samaccountname, $adresyDo)){
                    $adresyDo[] = $samaccountname;
                }
            }
        }

        $powiernicy = [];
        $grupaPowiernikZarzadu = $em->getRepository('ParpMainBundle:AclRole')->findOneByName('PARP_KOMP');
        if (!empty($grupaPowiernikZarzadu->getUsers())) {
            foreach ($grupaPowiernikZarzadu->getUsers() as $user) {
                $samaccountname = $user->getSamaccountname();
                if (!in_array($samaccountname, $adresyDo)) {
                    $powiernicy[] = $samaccountname;
                }
            }
        }

        var_dump($powiernicy);
die();
       ///var_dump(empty($zastepstwo));
        // jezeli nie dyrektor to dodaj adres Pani(Przerobic na grupę) - POWIERNIK_ZARZADU

        // dodaj pracowników grup HelpDesk BI, KOMP

        // wyslij maila - nowa metoda w usłudze parp.mailer chyba

        //var_dump($xx['samaccountname']);
        //
        //
        //$xx = $ldap->getPrzelozonyPracownika('tomasz_bonczak');
        //$xx = $ldap->getPrzelozonyPracownika('tomasz_bonczak');
        // sprawdz czy ma zastepstwo
        //$zastepstwo = $em->getRepository('ParpMainBundle:Zastepstwo')->znajdzZastepstwa($xx['samaccountname']);

        if (!empty($userEngagements)) {
            foreach ($userEngagements as $userEngagement) {

                $date = new \DateTime();
                $date->setDate($userEngagement->getYear(), $userEngagement->getMonth(), 1);
                $date->setTime(0, 0, 0);

                $today = new \DateTime();

                if ($date <= $today) {
                    $user = $ldap->getUserFromAD($userEngagement->getSamaccountname());
                    //$user = $users[$userEngagement->getSamaccountname()];
                    var_dump($user);
                }
            }
        }
    }

}
