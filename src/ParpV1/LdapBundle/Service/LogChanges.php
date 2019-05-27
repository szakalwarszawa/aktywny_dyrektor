<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service;

use DateTime;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Logowanie zmian na użytkownikach (zapis zmian w AD).
 */
class LogChanges
{
    /**
     * @var string
     */
    const LOG_FILE_NAME_PATTERN = 'ad_push_%s.html';

    /**
     * @var string
     */
    private $logsDirectory;

    /**
     * @var TwigEngine
     */
    private $twigTemplating;

    /**
     * @var string|null
     */
    private $logFilename = null;

    /**
     * Konstruktor
     *
     * @param string $logsDirectory
     * @param TwigEngine $twigTemplating
     */
    public function __construct(
        string $logsDirectory,
        TwigEngine $twigTemplating
    ) {
        $this->twigTemplating = $twigTemplating;
        $this->logsDirectory = $logsDirectory;

        $this->logFilename = sprintf(self::LOG_FILE_NAME_PATTERN, (new DateTime())->format('Y-m-d_H:i:s'));
    }

    /**
     * Zapisuje zmiany w pliku
     *
     * @param ArrayCollection|array $changes
     *
     * @return void
     */
    public function logToFile($changes): void
    {
        $twigTemplating = $this->twigTemplating;
        $logFilePathName = '..' . DIRECTORY_SEPARATOR . $this->logsDirectory  . $this->logFilename;

        if ('cli' === PHP_SAPI) {
            $logFilePathName = $this->logsDirectory  . $this->logFilename;
        }

        $fileSystem = new Filesystem();

        $view = $twigTemplating->render('@ParpLdap/main/changes_iterator.html.twig', [
                'change_log' => $changes
        ]);

        $fileSystem->dumpFile($logFilePathName, $view);
    }

    /**
     * Zwraca nazwę utworzonego pliku.
     *
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->logFilename;
    }
}
