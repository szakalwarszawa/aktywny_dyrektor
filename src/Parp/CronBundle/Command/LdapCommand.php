<?php

/**
 * Created by PhpStorm.
 * User: muchar
 * Date: 20.08.14
 * Time: 16:04
 */

namespace Parp\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LdapCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('parp:ldapsave')->setDescription('Pobiera niezapisane dane z bazy Aktywnego Dyrektora i wprowadza je do Active Directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $output->writeln('<comment>Wczytuję usługi ...                             </comment>', false);
            $doctrine = $this->getContainer()->get('doctrine');
            $output->writeln('<comment>Wczytano usługe doctrine ...                             </comment>', false);
            $ldap = $this->getContainer()->get('ldap_admin_service');
            $output->writeln('<comment>Wczytano usługe ldap_admin_service...                             </comment>', false);
            $uprawnienia = $this->getContainer()->get('uprawnienia_service');
            $output->writeln('<comment>Wczytano usługe uprawnienia_service ...                             </comment>', false);
            $em = $doctrine->getManager();
            $output->writeln('<info> [  OK  ]</info>');
    
            $output->writeln('<comment>Szukam zmian w Aktywnym Dyrektorze...          </comment>', false);
            $zmiany = $doctrine->getRepository('ParpMainBundle:Entry')->findByIsImplementedAndFromWhen();
            $output->writeln('<info> [  OK  ]</info>');
    
            if ($zmiany) {
                // Sprawdzamy po kolei co się zmieniło i zbieramy to cezamem do kupy
                foreach ($zmiany as $zmiana) {
    
                    $userNow = $ldap->getUserFromAD($zmiana->getSamaccountname());
                    
                    if ($userNow) {
    
                        $output->writeln('<info>Znalazłem następujące zmiany:</info>');
                        foreach ($zmiany as $zmiana) {
                            $userNow = $ldap->getUserFromAD($zmiana->getSamaccountname());
                            $output->writeln($userNow[0]['name'] . ':');
                        }
                        if ($zmiana->getAccountExpires()) {
                            // Wygasza się konto
                            $output->writeln('  - Wygaszenie konta: ' . $zmiana->getAccountExpires()->format('d-m-Y H:i:s'));
                        }
                        if ($zmiana->getDepartment()) {
                            if ($userNow[0]['department']) {
                                $output->writeln('  - Zmiana biura: ' . $userNow[0]['department'] . ' -> ' . $zmiana->getDepartment());
                            } else {
                                $output->writeln('  - Nadanie biura: ' . $zmiana->getDepartment());
                            }
                        }
                        if ($zmiana->getInfo()) {
                            if ($userNow[0]['info']) {
                                $output->writeln('  - Zmiana sekcji: ' . $userNow[0]['info'] . ' -> ' . $zmiana->getInfo());
                            } else {
                                $output->writeln('  - Nadanie sekcji: ' . $zmiana->getInfo());
                            }
                        }
                        if ($zmiana->getCn()) {
                            if ($userNow[0]['cn']) {
                                $output->writeln('  - Zmiana imienia i nazwiska : ' . $userNow[0]['cn'] . ' -> ' . $zmiana->getCn());
                            } else {
                                $output->writeln('  - Nadanie imienia i nazwiska: ' . $zmiana->getCn());
                            }
                        }
                        if ($zmiana->getManager()) {
                            if ($userNow[0]['manager']) {
                                $output->writeln('  - Zmiana przełożonego : ' . $userNow[0]['manager'] . ' -> ' . $zmiana->getManager());
                            } else {
                                $output->writeln('  - Nadanie przełożonego: ' . $zmiana->getManager());
                            }
                        }
                        if ($zmiana->getTitle()) {
                            if ($userNow[0]['title']) {
                                $output->writeln('  - Zmiana przełożonego : ' . $userNow[0]['title'] . ' -> ' . $zmiana->getTitle());
                            } else {
                                $output->writeln('  - Nadanie stanowiska: ' . $zmiana->getTitle());
                            }
                        }
                        if ($zmiana->getInitials()) {
                            if ($userNow[0]['initials']) {
                                $output->writeln('  - Zmiana imicjałów : ' . $userNow[0]['initials'] . ' -> ' . $zmiana->getInitials());
                            } else {
                                $output->writeln('  - Nadanie inicjałów: ' . $zmiana->getInitials());
                            }
                        }
    
                        if ($zmiana->getInitialrights()) {
                            // pobierzmy stare
                            $old = $em->getRepository('ParpMainBundle:UserGrupa')->findBy(array('samaccountname' => $zmiana->getSamaccountname()));
                            $oldg = array();
                            foreach($old as $o)
                                $oldg[] = $o->getGrupa();
    
                            // jezeli do tej pory nie miał żadnych
                            if ($old) {
                                $output->writeln('  - Zmiana uprawnień początkowych : ' . implode(",", $oldg) . ' -> ' . $zmiana->getInitialrights());
                            } else {
                                $output->writeln('  - Nadanie uprawnień początkowych : ' . $zmiana->getInitialrights());
                            }
                        }
                        
                        if ($zmiana->getMemberOf()) {
                            $znak = substr($zmiana->getMemberOf(), 0, 1);                 
                            $g = substr($zmiana->getMemberOf(), 1);
                            if ($znak == "+") {
                                $output->writeln('  - Dodanie do grupy: ' . $g);
                            } else {
                                $output->writeln('  - Usunięciez grupy: ' . $g);
                            }
                        }
                        // zmiana uprawnien początkowych nie powduje zadnch zmian w ldap-ie
                        //if (!$zmiana->getInitialrights() && count($zmiany) == 1) {
                            //print_r($zmiana);
                            
                            $ldap->saveEntity($zmiana->getDistinguishedName(), $zmiana);
                        //}
                        //print_r($zmiana); die();
                        $uprawnienia->zmianaUprawnien($zmiana);
                        
                        
                        $zmiana->setIsImplemented(1);
                        $em->persist($zmiana);
    
                        // nie znaleziono w ldap tzn ze mamy nowego usera do wstawienia
                    } else {
    
                        $output->writeln('  - Dodanie praownika: ' . $zmiana->getCn());
                        //print_r($zmiana); //die();
                        $ldap->createEntity($zmiana);
                        // nadaj uprawnieznia poczatkowe
                        $uprawnienia->ustawPoczatkowe($zmiana);
                        $zmiana->setIsImplemented(1);
                        $em->persist($zmiana);
                    }
                }
    
                $em->flush();
            }
        }catch(\Exception $e){
                
            $output->writeln('<comment>Błąd...                             </comment>', false);
            $output->writeln($e->getMessage(), false);
        }
    }

}
