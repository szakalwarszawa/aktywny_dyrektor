<?php declare(strict_types=1);

namespace ParpV1\CronBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use DateTime;
use Symfony\Component\Console\Style\SymfonyStyle;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use Symfony\Component\Console\Input\InputOption;
use ParpV1\CronBundle\Exception\UnknownFileTypeException;
use Symfony\Component\Console\Helper\ProgressBar;
use ParpV1\MainBundle\Services\UprawnieniaService;
use ParpV1\MainBundle\Entity\WniosekStatus;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use RecursiveIteratorIterator;
use RecursiveArrayIterator;

/**
 * Klasa komendy CzyszczenieStarychWnioskowCommand
 * Umożliwia czyszczenie starych wniosków. (Anulowanie ich)
 */
class CzyszczenieStarychWnioskowCommand extends Command
{
    /**
     * @var UprawnieniaService
     */
    private $uprawnieniaService;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var string|null
     */
    private $fileType = null;

    /**
     * @var bool
     */
    private $exportFile = false;

    /**
     * @var string
     */
    private $exportPath;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    const JSON_FILE = 'json';

    /**
     * @var string
     */
    const DEFAULT_REASON = 'Wniosek przedawniony';

    /**
     * Publiczny konstruktor
     *
     * @param UprawnieniaService $uprawnieniaService
     * @param EntityManager $entityManager
     * @param stirng $exportPath
     */
    public function __construct(
        UprawnieniaService $uprawnieniaService,
        EntityManager $entityManager,
        string $exportPath
    ) {
        $this->uprawnieniaService = $uprawnieniaService;
        $this->entityManager = $entityManager;
        $this->exportPath = $exportPath;

        parent::__construct();
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('parp:usunstarewnioski')
            ->setDescription(
                'Anuluje administracyjnie niezakończone wnioski starsze niż podana data lub podane z pliku.'
            )
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Data przed którą zostaną anulowane wnioski. <info>Domyślnie pierwszy dzień obecnego roku.</info>'
            )
            ->addOption(
                'sourceFile',
                'f',
                InputOption::VALUE_OPTIONAL,
                '<error>Ścieżka do pliku zawierającego id wniosków o nadanie/odebranie do anulowania.</error>'
            )
            ->addOption(
                'exportFile',
                'k',
                InputOption::VALUE_NONE,
                'Eksportuje przeprocesowane wnioski do pliku.'
            )
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        if ($input->getOption('exportFile')) {
            $this
                ->symfonyStyle
                ->warning('Anulowane wnioski zostaną wyeksportowane do pliku.')
            ;
            $this->exportFile = true;
        }
        if ($input->getOption('sourceFile')) {
            return $this->dataFromFileExecute($input, $output, $input->getOption('sourceFile'));
        }
    }

    /**
     * Rozpoczyna pracę z pobranymi z pliku danymi wniosków (ich identyfikatorami).
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $sourceFile
     *
     * @return null
     */
    private function dataFromFileExecute(InputInterface $input, OutputInterface $output, string $sourceFile)
    {
        if (!file_exists($sourceFile)) {
            $this
                ->symfonyStyle
                ->caution('Nie odnaleziono podanego pliku.')
            ;

            return null;
        }

        if (null !== $input->getArgument('date')) {
            $this
                ->symfonyStyle
                ->note(['Wprowadzona data zostanie zignorowana.', 'Lista wniosków podchodzi z pliku!'])
            ;
        }

        $this->setFileType($sourceFile);
        $arrayFileContent = $this->contentToArray($sourceFile);
        $this->initializeProgressBar($output, $arrayFileContent);

        return $this->cancelApplications($arrayFileContent);
    }

    /**
     * Uruchamia pasek postępu.
     *
     * @param OutputInterface
     * @param array $arrayFileContent
     *
     * @return void
     */
    private function initializeProgressBar(OutputInterface $output, array $arrayFileContent): void
    {
        ProgressBar::setFormatDefinition('customBar', ' %current%/%max% [%bar%] %percent%% -- %message%');
        $this
            ->progressBar = new ProgressBar($output, count($arrayFileContent))
        ;
        $this
            ->progressBar
            ->setFormat('customBar')
        ;
        $this
            ->progressBar
            ->setMessage('Uruchamianie...')
        ;
    }

    /**
     * Określenie przed jaką datą mają być anulowane wnioski.
     * Wymagane potwierdzenie przez użytkownika.
     *
     * @todo metoda przyszłościowa kiedy będzie się podawać tylko datę
     *      a skrypt sam wyszuka wnioski przed datą.
     *
     * @param InputInterface $input
     *
     * @return DateTime|null
     */
    private function specifyLimitDate(InputInterface $input)
    {
        $limitDate = new DateTime('1 January This Year');
        $argumentDate = $input->getArgument('date');
        if (null !== $argumentDate) {
            $limitDate = new DateTime($argumentDate);
        }

        $confirm = $this
            ->symfonyStyle
            ->confirm('Wnioski przed ' . $limitDate->format('Y-m-d') . ' zostaną anulowane administracyjnie')
        ;

        if (!$confirm) {
            return null;
        }

        return $limitDate;
    }

    /**
     * Przekazuje wnioski do anulowania końcowego.
     *
     * @param array $applicationsArray
     *
     * @return null
     */
    private function cancelApplications(array $applicationsArray)
    {
        $symfonyStyle = $this->symfonyStyle;
        $symfonyStyle
            ->section('Poniższe wnioski zostaną anulowane administracyjnie:')
        ;
        $symfonyStyle
            ->listing($applicationsArray)
        ;

        $confirm = $symfonyStyle
            ->confirm('Kontynuować?', false)
        ;

        if (!$confirm) {
            return null;
        }

        $this
            ->progressBar
            ->start()
        ;

        $uprawnieniaService = $this->uprawnieniaService;
        $uprawnieniaService
            ->switchFlush(false)
        ;

        $cancelledApplications = [];
        foreach ($applicationsArray as $application) {
            $wniosekNadanieOdebranieZasobow = $this
                ->entityManager
                ->getRepository(WniosekNadanieOdebranieZasobow::class)
                ->findOneById($application)
            ;

            $status = false;
            $wniosekNadanieOdebranieZasobowId = null;
            if (null !== $wniosekNadanieOdebranieZasobow
                && !$wniosekNadanieOdebranieZasobow->getWniosek()->getIsBlocked()) {
                $wniosekNadanieOdebranieZasobowId = $wniosekNadanieOdebranieZasobow->getId();
                $status = $uprawnieniaService
                    ->zablokujKoncowoWniosek(
                        $wniosekNadanieOdebranieZasobow,
                        WniosekStatus::ANULOWANO_ADMINISTRACYJNIE,
                        'Wniosek przedawniony.'
                    );
            }

            $tempArray = [
                'wniosek_id' => $wniosekNadanieOdebranieZasobowId,
                'status' => $status? 'success' : 'fail'
            ];

            if (null !== $wniosekNadanieOdebranieZasobowId) {
                $cancelledApplications[] = $tempArray;
                $this->addProgress('Procesowany wniosek ID: ' . $wniosekNadanieOdebranieZasobowId);
            }
        }

        $this
            ->progressBar
            ->finish()
        ;

        $symfonyStyle
            ->table(['Wniosek ID', 'Status'], $cancelledApplications)
        ;

        $this
            ->entityManager
            ->flush()
        ;

        if ($this->exportFile) {
            $this->exportApplicationsToFile($cancelledApplications);
        }

        $symfonyStyle
            ->success('Przeprocesowano wniosków: ' . count($cancelledApplications))
        ;

        return null;
    }

    private function exportApplicationsToFile(array $applicationsData)
    {
        $exportPath = $this->exportPath;
        $fileName = (new DateTime())->format('Y-m-d_h-i-s') . '_anulowanie_wnioskow.json';

        $filePathName = $exportPath . $fileName;
        $fileSystem = new Filesystem();
        $fileSystem
            ->dumpFile($filePathName, json_encode($applicationsData))
        ;
        $this
            ->symfonyStyle
            ->section('Przeprocesowane wnioski zostały zapisane w pliku <info>' . $filePathName . '</info>');
    }

    /**
     * Ustawia typ pliku.
     *
     * @todo aktualnie obsługiwany jest tylko json
     *
     * @param string $filePath
     *
     * @return void
     */
    private function setFileType(string $filePath): void
    {
        $fileExtension = explode('.', $filePath);
        switch (end($fileExtension)) {
            case 'json':
                $this->fileType = self::JSON_FILE;
                break;
        }

        if (null === $this->fileType) {
            throw new UnknownFileTypeException();
        }
    }

    /**
     * Konwertuje odczytany plik do tablicy.
     *
     * @todo aktualnie obsługiwany jest tylko json
     *
     * @param string $sourceFile
     *
     * @return array
     */
    private function contentToArray(string $sourceFile): array
    {
        $fileContent = file_get_contents($sourceFile);
        if (self::JSON_FILE === $this->fileType) {
            $arrayData = json_decode($fileContent, true);
            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($arrayData));
            $applicationsList = iterator_to_array($iterator, false);

            $this
                ->symfonyStyle
                ->note('Odczytano dane z pliku.')
            ;

            return $applicationsList;
        }

        return [];
    }

    /**
     * Dodaje postęp do paska.
     *
     * @param string|null $optionalMessage
     *
     * @return void
     */
    private function addProgress(string $optionalMessage = null): void
    {
        $this
            ->progressBar
            ->advance()
        ;

        if ($optionalMessage) {
            $this
                ->progressBar
                ->setMessage($optionalMessage)
            ;
        }
    }
}
