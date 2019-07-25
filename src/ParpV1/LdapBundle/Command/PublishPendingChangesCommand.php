<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ParpV1\MainBundle\Services\DictionaryService;
use Symfony\Component\Console\Input\InputOption;
use ParpV1\LdapBundle\Service\AdUser\Update\UpdateFromEntry;
use ParpV1\LdapBundle\Constants\GroupBy;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Komenda wypychająca oczekujące zmiany do AD.
 */
class PublishPendingChangesCommand extends Command
{
    /**
     * @var UpdateFromEntry
     */
    private $updateFromEntry;

    /**
     * Publiczny konstruktor
     *
     * @param UpdateFromEntry $updateFromEntry
     */
    public function __construct(UpdateFromEntry $updateFromEntry)
    {
        $updateFromEntry->throwExceptions();
        $this->updateFromEntry = $updateFromEntry;

        parent::__construct();
    }
    /**
     * @see Command
     */
    protected function configure()
    {
        $dictionary = new DictionaryService(__DIR__ . '//Dictionary//');
        $this
            ->setName('parp:ldap:publishPendingChanges')
            ->setDescription($dictionary::get('description'))
            ->setHelp($dictionary::get('help'))
            ->addOption('simulate', 's', InputOption::VALUE_NONE, $dictionary::get('simulate'))
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updateResult = $this
            ->updateFromEntry
            ->publishAllPendingChanges($input->getOption('simulate'), true)
        ;

        $symfonyStyle = new SymfonyStyle($input, $output);

        if ($input->getOption('simulate')) {
            $symfonyStyle->warning('SYMULACJA ZMIAN');
        }

        $responseMessages = $updateResult->getResponseMessages(GroupBy::LOGIN);

        if ($responseMessages instanceof ArrayCollection) {
            if (empty($responseMessages->toArray())) {
                $symfonyStyle->note('Brak wprowadzonych zmian.');
            }
        }

        foreach ($responseMessages as $key => $messages) {
            $messagesText = [];
            foreach ($messages as $message) {
                $messagesText[] = [
                    'message' => $message->getMessage()
                ];
            }

            $symfonyStyle
                ->table([$key], $messagesText)
            ;
        }
    }
}
