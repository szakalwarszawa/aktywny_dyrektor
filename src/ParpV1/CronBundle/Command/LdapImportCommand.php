<?php

/**
 * Created by PhpStorm.
 * User: muchar
 * Date: 20.08.14
 * Time: 16:04
 */

namespace ParpV1\CronBundle\Command;

use ParpV1\SoapBundle\Controller\ImportController;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LdapImportCommand extends ContainerAwareCommand
{
    protected $debug = true;
    
    protected function configure()
    {
        $this
            ->setName('parp:ldapimport')
            ->setDescription('Pobiera dane z bazy Active Directory (users, ous i groups) i wprowadza je do Aktywnego Dyrektora')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('<comment>' . date("Y-m-d-H-I-s") . ' - Zaczynam import ous ...                             </comment>', false);
            
            $this->getContainer()->get('ldap_import')->importOUsAction();
            
            
            $output->writeln('<comment>' . date("Y-m-d-H-I-s") . ' - Skonczylem import ous ...                             </comment>', false);
            $output->writeln('<comment>' . date("Y-m-d-H-I-s") . ' - Zaczynam import groups ...                             </comment>', false);
            
            $this->getContainer()->get('ldap_import')->importGroupsAction();
            
            
            $output->writeln('<comment>' . date("Y-m-d-H-I-s") . ' - Skonczylem import groups ...                             </comment>', false);
            $output->writeln('<comment>' . date("Y-m-d-H-I-s") . ' - Zaczynam import users ...                             </comment>', false);
            
                
            $letters = "abcdefghijklmnopqrstuvwxyz";
            $letters_array = str_split($letters);
            
            foreach ($letters_array as $l) {
                $output->writeln('<comment>' . date("Y-m-d-H-I-s") . ' - Import users na litere "' . $l . '" ...                             </comment>', false);
                $this->getContainer()->get('ldap_import')->importUsersAction($l);
            }
            
            
            $output->writeln('<comment>' . date("Y-m-d-H-I-s") . ' - Skonczylem import users ...                             </comment>', false);
        } catch (\Exception $e) {
            $output->writeln('<error>Błąd...                             </error>', false);
            $output->writeln('<error>' . $e->getMessage() . "</error>", false);
        }
    }
}
