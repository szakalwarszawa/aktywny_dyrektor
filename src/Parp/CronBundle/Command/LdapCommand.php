<?php

/**
 * Created by PhpStorm.
 * User: muchar
 * Date: 20.08.14
 * Time: 16:04
 */

namespace Parp\CronBundle\Command;

use LogicException;
use Parp\AuthBundle\Security\ParpUser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class LdapCommand
 * @package Parp\CronBundle\Command
 */
class LdapCommand extends ContainerAwareCommand
{
    protected $debug = true;
    protected $showonly = true;
    protected $ids = "";
    protected $samaccountname = "console";
    protected $pushErrors = [];

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
        ->addOption(
            'samaccountname',
            null,
            InputOption::VALUE_NONE,
            'Entry samaccountname who is publishing'
        )
        ;
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if (!$this->getContainer()->get('security.context')->getToken()) {
                $user = new ParpUser("kamil_jakacki", "", "salt", ["PARP_ADMIN"]);
                // create the authentication token
                $token = new UsernamePasswordToken(
                    $user,
                    null,
                    'main',
                    $user->getRoles()
                );
                // give it to the security context
                $this->getContainer()->get('security.context')->setToken($token);
            }

            $t1 = microtime(true) ;
            if ($input->getOption('ids')) {
                $this->ids = $input->getOption('ids');
            }

            if ($input->getOption('samaccountname')) {
                $this->samaccountname = $input->getOption('samaccountname');
            }

            $this->showonly = $input->getArgument('showonly');
            $msg = $this->showonly ? "Tryb w którym zmiany nie będą wypychane do AD (tylko pokazuje zmiany czekające na publikację)": "Publikowanie zmian do AD";
            $output->writeln('<comment>'.$msg.'</comment>', false);

            $output->writeln('<comment>Wczytuję usługi ...                             </comment>', false);
            $doctrine = $this->getContainer()->get('doctrine');
            $output->writeln('<comment>Wczytano usługe doctrine ...                             </comment>', false);
            $ldap = $this->getContainer()->get('ldap_admin_service');
            $ldap_client = $this->getContainer()->get('ldap_service');
            $ldap->output = $output;

            $time = date("Y-m-d_H-i-s");
            if ($ldap->pushChanges) {
                $output->writeln('<error>                             PUBLIKUJE zmiany do AD...</error>', false);
                $logfile = __DIR__."/../../../../work/logs/"."publish_".$time.".html";
            } else {
                $output->writeln('<error>                             NIE publikuje zmiany do AD...</error>', false);
                $logfile = __DIR__."/../../../../work/logs/"."tylko_test_publish_".$time.".html";
            }


            $output->writeln('<comment>Wczytano usługe ldap_admin_service...                             </comment>', false);
            $uprawnienia = $this->getContainer()->get('uprawnienia_service');
            $output->writeln('<comment>Wczytano usługe uprawnienia_service ...                             </comment>', false);
            $em = $doctrine->getManager();
            $output->writeln('<info> [  OK  ]</info>');

            $output->writeln('<comment>Szukam zmian w Aktywnym Dyrektorze...          </comment>', false);
            $zmiany = $doctrine->getRepository('ParpMainBundle:Entry')->findByIsImplementedAndFromWhen($this->ids);
            $output->writeln('<info> [  OK  ]</info>');

            if ($zmiany) {
                // Sprawdzamy po kolei co się zmieniło i zbieramy to cezamem do kupy
                foreach ($zmiany as $zmiana) {
                    $userNowData = $ldap->getUserFromAllAD($zmiana->getSamaccountname());
                    $userNow = $userNowData['user'];
                    $ktorzy = $userNowData['ktorzy'];

                    if ($userNow) {
                        $liczbaZmian = 0;
                        if ($ktorzy == "aktywne") {
                            $output->writeln('<info>Znalazłem następujące zmiany dla użytkownika "'.$zmiana->getSamaccountname().'" (id: '.$zmiana->getId().'):</info>');
                        } elseif ($ktorzy == "zablokowane") {
                            $output->writeln('<info>Znalazłem następujące zmiany dla ZABLOKOWANEGO użytkownika "'.$zmiana->getSamaccountname().'" (id: '.$zmiana->getId().'):</info>');
                        } elseif ($ktorzy == "nieaktywne") {
                            $output->writeln('<info>Znalazłem następujące zmiany dla NIEAKTYWNEGO użytkownika "'.$zmiana->getSamaccountname().'" (id: '.$zmiana->getId().'):</info>');
                        }

                        if ($zmiana->getAccountExpires()) {
                            $liczbaZmian++;
                            // Wygasza się konto
                            $output->writeln('  - Wygaszenie konta: ' . $zmiana->getAccountExpires()->format('d-m-Y H:i:s'));
                        }
                        if ($zmiana->getDepartment()) {
                            $liczbaZmian++;
                            if ($userNow[0]['department']) {
                                $output->writeln('  - Zmiana biura: ' . $userNow[0]['department'] . ' -> ' . $zmiana->getDepartment());
                            } else {
                                $output->writeln('  - Nadanie biura: ' . $zmiana->getDepartment());
                            }
                        }
                        if ($zmiana->getInfo() != null) {
                            $liczbaZmian++;
                            if ($userNow[0]['info']) {
                                $output->writeln('  - Zmiana sekcji: ' . $userNow[0]['info'] . ' -> ' . $zmiana->getInfo());
                            } else {
                                $output->writeln('  - Nadanie sekcji: ' . ($zmiana->getInfo() == null ? "n/d": ""));
                            }
                        }
                        if ($zmiana->getDivision() != null) {
                            $liczbaZmian++;
                            if ($userNow[0]['division']) {
                                $output->writeln('  - Zmiana skrótu sekcji: ' . $userNow[0]['division'] . ' -> ' . $zmiana->getDivision());
                            } else {
                                $output->writeln('  - Nadanie skrótu sekcji: ' . ($zmiana->getDivision() == null ? "n/d": ""));
                            }
                        }
                        if ($zmiana->getCn()) {
                            $liczbaZmian++;
                            if ($userNow[0]['cn']) {
                                $output->writeln('  - Zmiana imienia i nazwiska: ' . $userNow[0]['cn'] . ' -> ' . $zmiana->getCn());
                            } else {
                                $output->writeln('  - Nadanie imienia i nazwiska: ' . $zmiana->getCn().$zmiana->getId());
                            }
                        }
                        if ($zmiana->getManager()) {
                            $liczbaZmian++;
                            if ($userNow[0]['manager']) {
                                $output->writeln('  - Zmiana przełożonego: ' . $userNow[0]['manager'] . ' -> ' . $zmiana->getManager());
                            } else {
                                $output->writeln('  - Nadanie przełożonego: ' . $zmiana->getManager());
                            }
                        }
                        if ($zmiana->getTitle()) {
                            $liczbaZmian++;
                            if ($userNow[0]['title']) {
                                $output->writeln('  - Zmiana stanowiska: ' . $userNow[0]['title'] . ' -> ' . $zmiana->getTitle());
                            } else {
                                $output->writeln('  - Nadanie stanowiska: ' . $zmiana->getTitle());
                            }
                        }
                        if ($zmiana->getInitials() && $zmiana->getInitials() != "puste") {
                            $liczbaZmian++;
                            if ($userNow[0]['initials']) {
                                $output->writeln('  - Zmiana inicjałów: ' . $userNow[0]['initials'] . ' -> ' . $zmiana->getInitials());
                            } else {
                                $output->writeln('  - Nadanie inicjałów: ' . $zmiana->getInitials());
                            }
                        }

                        if ($zmiana->getInitialrights()) {
                            $liczbaZmian++;
                            // pobierzmy stare
                            $old = $em->getRepository('ParpMainBundle:UserGrupa')->findBy(array('samaccountname' => $zmiana->getSamaccountname()));
                            $oldg = array();
                            foreach($old as $o)
                                $oldg[] = $o->getGrupa();

                            // jezeli do tej pory nie miał żadnych
                            if ($old) {
                                $output->writeln('  - Zmiana uprawnień początkowych: ' . implode(",", $oldg) . ' -> ' . $zmiana->getInitialrights());
                            } else {
                                $output->writeln('  - Nadanie uprawnień początkowych: ' . $zmiana->getInitialrights());
                            }
                        }
                        if ($userNow[0]['isDisabled'] != $zmiana->getIsDisabled()) {
                            $liczbaZmian++;

                            if ($zmiana->getIsDisabled()) {
                                $output->writeln('  - Wyłączenie konta w domenie');
                            } else {
                                $output->writeln('  - Włączenie konta w domenie');
                            }
                        } else {
                            $zmiana->setIsDisabled(null);
                        }

                        if ($zmiana->getMemberOf()) {
                            $liczbaZmian++;
                            $znak = substr($zmiana->getMemberOf(), 0, 1);
                            $g = substr($zmiana->getMemberOf(), 1);
                            if ($znak == "+") {
                                $output->writeln('  - Dodanie do grupy: ' . $g);
                            } else {
                                $output->writeln('  - Usunięciez grupy: ' . $g);
                            }
                        }

                        if ($liczbaZmian == 0) {
                            $output->writeln('<error>Nie ma nic do opublikowania</error>', false);
                            $ldapstatus = "Success";
                        } else {
                            $ldapstatus = "Success";
                            $ldapstatus = $this->tryToPushChanges($ldap, $zmiana, $output, false);
                        }

                        // zmiana uprawnien początkowych nie powduje zadnch zmian w ldap-ie
                        //if (!$zmiana->getInitialrights() && count($zmiany) == 1) {
                            //print_r($zmiana);
                        if ($ldapstatus == "Success") {
                            if (!$this->showonly) {
                                $uprawnienia->zmianaUprawnien($zmiana);
                                $zmiana->setIsImplemented(1);
                                $zmiana->setLogfile($logfile);
                                $zmiana->setPublishedBy($this->samaccountname);
                                $zmiana->setPublishedAt(new \Datetime());
                                $em->persist($zmiana);
                                //if($liczbaZmian == 0) echo ("zero zian ");
                            }
                        } else {
                            $output->writeln('<error>Błąd...Nie udało się wprowadzić zmian dla '.$zmiana->getSamaccountname().':</error>', false);
                            $output->writeln('<error>'.$ldapstatus.'</error>', false);
                        }

                        // nie znaleziono w ldap tzn ze mamy nowego usera do wstawienia
                    } else {
                        $output->writeln('<info>Znalazłem następujące zmiany (id: '.$zmiana->getId().'):   - Dodanie pracownika: ' . $zmiana->getCn()." ".$zmiana->getSamaccountname()."</info>");

                        $ldapstatus = $this->tryToPushChanges($ldap, $zmiana, $output, true);
                        if ($ldapstatus == "Success") {
                            // nadaj uprawnieznia poczatkowe
                            if (!$this->showonly) {
                                $uprawnienia->ustawPoczatkowe($zmiana);
                                $zmiana->setIsImplemented(1);
                                $zmiana->setLogfile($logfile);
                                $zmiana->setPublishedBy($this->samaccountname);
                                $zmiana->setPublishedAt(new \Datetime());
                                $em->persist($zmiana);
                            }
                        } else {
                            $output->writeln('<error>Błąd...Nie udało się wprowadzić zmian (utworzyć użytkownika) '.$zmiana->getCn().':</error>', false);
                            $output->writeln('<error>'.$ldapstatus.'</error>', false);
                        }
                    }
                }
                if (!$this->showonly && $this->getContainer()->getParameter('pusz_to_ad')) {
                    $this->proccessErrors($logfile);
                    $em->flush();
                }
            }

            $t2 = microtime(true) ;
            $td = ($t2 - $t1);
                $output->writeln('<comment>Czas operacji: '.$td.' sekund.</comment>', false);


            if (!$this->showonly && count($zmiany) > 0) {
                //zapis loga
//                $output2 = clone $output;
                $output2 = new BufferedOutput(
                    OutputInterface::VERBOSITY_NORMAL,
                    true // true for decorated
                );
                $converter = new AnsiToHtmlConverter();
                $msg = '<link rel="stylesheet" href="https://aktywnydyrektor.parp.gov.pl/css/main.css"><div class="publishOutput">'.
                    $converter->convert($output2->fetch()).
                    "</div>"; //"sdadsadsa";

                $fs = new Filesystem();
                $fs->dumpFile($logfile, $msg);
            } elseif (count($zmiany) == 0) {
                $output->writeln('<info>Nie ma nic do opublikowania!</info>', false);
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Błąd...                             </error>', false);
            $output->writeln('<error>'.$e->getMessage()."</error>", false);
            $output->writeln('<error>'.$e->getTraceAsString()."</error>", false);
        }
    }
    protected function proccessErrors($logfile)
    {
        $przetwarzajOK = [
            'ldapModAdd' => [68 /*Already exists*/],
            'ldapModify' => [],
            'ldapRename' => [],
            'ldapModDel' => [],
            'ldap_add' => [68 /*Already exists*/],
            'ldapDelete' => [],
            'ldapDelete' => [],
            'addRemoveMemberOf' => []
        ];

        foreach ($this->pushErrors as $es) {
            foreach ($es as $e) {
                if ($e) {
                    var_dump($e);
                    if (in_array($e['errorno'], $przetwarzajOK[$e['function']])) {
                        //znaczy ze ok i logujemy i zamykamy zgloszenie
                        $e['lastEntry']->setIsImplemented(1);
                        //echo "...ustawiam isImplemented ".$e['lastEntry']->getId();
                        unset($e['lastEntry']);
                        $logfileThis = str_replace(".html", "-".$e['function'].".log", $logfile);



                        //error_log(print_r($e, true), 3, $logfileThis);
                        file_put_contents($logfileThis, print_r($e, true), FILE_APPEND | LOCK_EX);
                    }
                }
            }
        }
        //echo "<pre>"; print_r($this->pushErrors);
/*
        $grouped = [];
        foreach($this->pushErrors as $es){
            foreach($es as $e){
                $grouped[$e['function']][$e['error']][] = $e;
            }
        }
        echo "<pre>"; print_r($grouped); echo "</pre>";
        echo "<pre>"; print_r($grouped);
*/
    }
    protected function tryToPushChanges($ldap, $zmiana, $output, $isCreating)
    {
        $maxConnections = $this->getContainer()->getParameter('maximum_ldap_reconnects');
        $ldapstatus = "";
        $i = 0;
        do {
            if ($this->showonly) {
                $ldapstatus = "Success";
            } else {
                if ($isCreating) {
                    $ldapstatus = $ldap->createEntity($zmiana);
                } else {
                    $ldapstatus = $ldap->saveEntity($zmiana->getDistinguishedName(), $zmiana);
                }
            }
            $i++;
            if ($ldapstatus != "Success") {
                $ldap->switchServer($ldapstatus);
            }
        } while ($ldapstatus != "Success" && $i < $maxConnections);

        if (!empty($ldap->lastConnectionErrors)) {
            $this->pushErrors[] = $ldap->lastConnectionErrors;
        }

        return $ldapstatus;
    }
}
