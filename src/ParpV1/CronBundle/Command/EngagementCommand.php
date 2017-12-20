<?php

namespace ParpV1\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ParpV1\MainBundle\Services\ParpMailerService;

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
            foreach ($grupaHelpdesk->getUsers() as $user) {
                $samaccountname = $user->getSamaccountname();
                if (!in_array($samaccountname, $adresyDo)) {
                    $adresyDo[] = $samaccountname;
                }
            }
        }

        $grupaKomp = $em->getRepository('ParpMainBundle:AclRole')->findOneByName('PARP_KOMP');
        if (!empty($grupaKomp->getUsers())) {
            foreach ($grupaKomp->getUsers() as $user) {
                $samaccountname = $user->getSamaccountname();
                if (!in_array($samaccountname, $adresyDo)) {
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

        if (!empty($userEngagements)) {
            $kontaUzyte = []; // aby unuknąc wysyłania wielu mailii dotyczącej zmian u jednej osoby
            foreach ($userEngagements as $userEngagement) {
                $konta = [];

                $date = new \DateTime();
                $date->setDate($userEngagement->getYear(), $userEngagement->getMonth(), 1);
                $date->setTime(0, 0, 0);

                $today = new \DateTime();

                if ($date <= $today) {
                    $user = $ldap->getUserFromAD($userEngagement->getSamaccountname())[0];
                    if (!in_array($user['samaccountname'], $kontaUzyte)) {
                        // jezeli zarząd dadaj powierników, jeżeli nie, to tylko przełożonego(dyrektora)
                        if ($this->czyZarzad($user)) {
                            $konta = array_merge($konta, $powiernicy);
                        } else {
                            $przelozony = $ldap->getPrzelozonyPracownika($user['samaccountname']);
                            $konta[] = $przelozony['samaccountname'];
                        }
                        // dodaj pozostale grupy
                        $konta = array_merge($konta, $adresyDo);
                        $data['odbiorcy'] = $konta;
                        $data['login'] = $user['samaccountname'];
                        $data['departament'] = $user['department'];
                        $data['data_zmiany'] = $today->format('Y-m-d');
                        $data['rok'] = $userEngagement->getYear();

                        $mailer->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKZMIANAZAANGAZOWANIA, $data);
                        $kontaUzyte[] = $user['samaccountname'];
                    }
                    $userEngagement->setCzyNowy(false);
                    $em->persist($userEngagement);
                }
            }
            $em->flush();
        }
    }

    /**
     * Funcja spradza czy uzytkownik nalezy do zarządu
     *
     * @param array $user
     *
     * @retrun boolean
     */
    protected function czyZarzad($user)
    {
        $stanowiska = ['zastępca dyrektora', 'p.o. dyrektora', 'dyrektor', 'prezes', 'zastępca prezesa'];
        if (in_array($user['title'], $stanowiska)) {
            return true;
        }

        return false;
    }

}
