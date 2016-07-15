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
use Symfony\Component\Console\Input\InputOption;

class LdapCommand extends ContainerAwareCommand
{
    protected $debug = true;
    protected $showonly = true;
    protected $ids = "";
    
    protected function configure()
    {
        $this->setName('parp:ldapsave')->setDescription('Pobiera niezapisane dane z bazy Aktywnego Dyrektora i wprowadza je do Active Directory')
        ->addArgument(
                'showonly',
                InputArgument::OPTIONAL,
                'Tylko pokazuje jakie zmiany by poszly do AD?'
            )
        ->addOption(
                'ids',
                null,
                InputOption::VALUE_NONE,
                'Entry ids to proccess'
            )
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            if($input->getOption('ids')){
                $this->ids = $input->getOption('ids');
            }            
            $this->showonly = $input->getArgument('showonly');
            //die(".".$this->showonly);
            $msg = $this->showonly ? "Tryb w którym zmiany nie będą wypychane do AD (tylko pokazuje zmiany czekające na publikację)" : "Publikowanie zmian do AD";
            $output->writeln('<comment>'.$msg.'</comment>', false);
            //die(".".$showonly);
            
            $output->writeln('<comment>Wczytuję usługi ...                             </comment>', false);
            $doctrine = $this->getContainer()->get('doctrine');
            $output->writeln('<comment>Wczytano usługe doctrine ...                             </comment>', false);
            $ldap = $this->getContainer()->get('ldap_admin_service');
            $ldap->output = $output;
            $output->writeln('<comment>Wczytano usługe ldap_admin_service...                             </comment>', false);
            $uprawnienia = $this->getContainer()->get('uprawnienia_service');
            $output->writeln('<comment>Wczytano usługe uprawnienia_service ...                             </comment>', false);
            $em = $doctrine->getManager();
            $output->writeln('<info> [  OK  ]</info>');
    
            $output->writeln('<comment>Szukam zmian w Aktywnym Dyrektorze...          </comment>', false);
            $zmiany = $doctrine->getRepository('ParpMainBundle:Entry')->findByIsImplementedAndFromWhen($this->ids);
            $output->writeln('<info> [  OK  ]</info>');
    
            if ($zmiany) {
                //print_r($zmiany); 
                // Sprawdzamy po kolei co się zmieniło i zbieramy to cezamem do kupy
                foreach ($zmiany as $zmiana) {
    
                    $userNow = $ldap->getUserFromAD($zmiana->getSamaccountname());
                    //print_r($zmiana->getSamaccountname()); var_dump($userNow); die();
                    if ($userNow) {
    
                        $output->writeln('<info>Znalazłem następujące zmiany dla użytkownika "'.$zmiana->getSamaccountname().'" (id: '.$zmiana->getId().'):</info>');
/*
                        foreach ($zmiany as $zmiana) {
                            $userNow = $ldap->getUserFromAD($zmiana->getSamaccountname());
                            $output->writeln($userNow[0]['name'] . ':');
                        }
*/
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
                                $output->writeln('  - Nadanie imienia i nazwiska: ' . $zmiana->getCn().$zmiana->getId());
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
                        if ($zmiana->getInitials() && $zmiana->getInitials() != "puste") {
                            if ($userNow[0]['initials']) {
                                $output->writeln('  - Zmiana inicjałów : ' . $userNow[0]['initials'] . ' -> ' . $zmiana->getInitials());
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
                        if ($userNow[0]['isDisabled'] != $zmiana->getIsDisabled()) {
                            //print_r($userNow[0]);
                            if ($zmiana->getIsDisabled()) {
                                $output->writeln('  - Wyłączenie konta w domenie');
                            }else{
                                $output->writeln('  - Włączenie konta w domenie');
                            }
                            
                        }else{
                            $zmiana->setIsDisabled(null);
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
                        $ldapstatus = $this->tryToPushChanges($ldap, $zmiana, $output, false);
                        if($ldapstatus == "Success"){
                            //$ldapstatus = $ldap->saveEntity($zmiana->getDistinguishedName(), $zmiana);
                            //}
                            //print_r($zmiana); die();
                            if(!$this->showonly){
                                $uprawnienia->zmianaUprawnien($zmiana);
                                $zmiana->setIsImplemented(1);
                                $em->persist($zmiana);
                            }
                        }else{
                            $output->writeln('<error>Błąd...Nie udało się wprowadzić zmian dla '.$zmiana->getCn().':</error>', false);
                            $output->writeln('<error>'.$ldapstatus.'</error>', false);
                        }
    
                        // nie znaleziono w ldap tzn ze mamy nowego usera do wstawienia
                    } else {
    
                        $output->writeln('<info>Znalazłem następujące zmiany (id: '.$zmiana->getId().'):   - Dodanie pracownika: ' . $zmiana->getCn()."</info>");
                        //print_r($zmiana); //die();
                        $ldapstatus = $this->tryToPushChanges($ldap, $zmiana, $output, true);
                        if($ldapstatus == "Success"){
                            //$ldapstatus = $ldap->createEntity($zmiana);
                            // nadaj uprawnieznia poczatkowe
                            if(!$this->showonly){
                                $uprawnienia->ustawPoczatkowe($zmiana);
                                $zmiana->setIsImplemented(1);
                                $em->persist($zmiana);
                            }
                        }else{
                            $output->writeln('<error>Błąd...Nie udało się wprowadzić zmian (utworzyć użytkownika) '.$zmiana->getCn().':</error>', false);
                            $output->writeln('<error>'.$ldapstatus.'</error>', false);
                            die();
                        }
                    }
                }
                //die("\n $ldapstatus \n");
                if(!$this->showonly)
                    $em->flush();
            }
        }catch(\Exception $e){
                
            $output->writeln('<error>Błąd...                             </error>', false);
            $output->writeln('<error>'.$e->getMessage()."</error>", false);
        }
    }
    
    protected function tryToPushChanges($ldap, $zmiana, $output, $isCreating){
        $maxConnections = $this->getContainer()->getParameter('maximum_ldap_reconnects');
        $ldapstatus = "";
        $i = 0;
        do{
            if($this->showonly){
                $ldapstatus = "Success";                
            }else{
                if($isCreating){
                    $ldapstatus = $ldap->createEntity($zmiana);
                }else{
                    $ldapstatus = $ldap->saveEntity($zmiana->getDistinguishedName(), $zmiana);
                }
            }
            $i++;
            //print_r("\n $ldapstatus $i \n");
            if($ldapstatus != "Success"){
                $ldap->switchServer($ldapstatus);
            }
        }while($ldapstatus != "Success" && $i < $maxConnections);
        return $ldapstatus;
    }

}
