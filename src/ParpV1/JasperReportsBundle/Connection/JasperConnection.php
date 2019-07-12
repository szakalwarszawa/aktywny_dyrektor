<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Connection;

use Jaspersoft\Client\Client;
use Jaspersoft\Service\JobService;
use Jaspersoft\Exception\RESTRequestException;
use ParpV1\RaportBundle\Exception\ConnectException;
use Jaspersoft\Service\ReportService;
use Jaspersoft\Service\OptionsService;
use Jaspersoft\Service\RepositoryService;

/**
 * Klasa połączenia z Jasper.
 */
class JasperConnection
{
    /**
     * @var string
     */
    private $serverUrl;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var int
     */
    private $requestTimeout;

    /**
     * @var Client|null
     */
    private $jasperClient = null;

    /**
     * @var bool
     */
    public $jasperActive;

    /**
     * Konstruktor.
     *
     * @param string $serverUrl
     * @param string $username
     * @param string $password
     * @param int $requestTimeout
     */
    public function __construct(bool $jasperActive, string $serverUrl, string $username, string $password, int $requestTimeout = 30)
    {
        $this->serverUrl = $serverUrl;
        $this->username = $username;
        $this->password = $password;
        $this->requestTimeout = $requestTimeout;
        $this->jasperActive = $jasperActive;
        if ($jasperActive) {
            $this->prepareClient();
        }
    }

    /**
     * Połączenie z Jasper.
     *
     * @return void
     */
    private function prepareClient(): void
    {
        $jasperClient = new Client(
            $this->serverUrl,
            $this->username,
            $this->password
        );

        $jasperClient->setRequestTimeout($this->requestTimeout);
        try {
            $jasperClient->serverInfo();
        } catch (RESTRequestException $exception) {
            throw new ConnectException('Niepoprawny adres serwera lub dane użytkownika Jasper.');
        }

        $this->jasperClient = $jasperClient;
        $this->password = '';
    }

    /**
     * Zwraca JobService do zapytań klienta Jasper.
     *
     * @return JobService
     */
    public function getJobService(): JobService
    {
        if (null === $this->jasperClient) {
            throw new ConnectException('Połączenie z Jasper nie powiodło się.');
        }

        return $this
            ->jasperClient
            ->jobService()
        ;
    }

    /**
     * Zwraca ReportService klienta Jasper.
     *
     * @return ReportService
     */
    public function getReportService(): ReportService
    {
        if (null === $this->jasperClient) {
            throw new ConnectException('Połączenie z Jasper nie powiodło się.');
        }

        return $this
            ->jasperClient
            ->reportService()
        ;
    }

    /**
     * Zwraca OptionsService klienta Jasper.
     *
     * @return OptionsService
     */
    public function getOptionsService(): OptionsService
    {
        if (null === $this->jasperClient) {
            throw new ConnectException('Połączenie z Jasper nie powiodło się.');
        }

        return $this
            ->jasperClient
            ->optionsService()
        ;
    }

    /**
     * Zwraca RepositoryService klienta Jasper.
     *
     * @return RepositoryService
     */
    public function getRepositoryService(): RepositoryService
    {
        if (null === $this->jasperClient) {
            throw new ConnectException('Połączenie z Jasper nie powiodło się.');
        }

        return $this
            ->jasperClient
            ->repositoryService()
        ;
    }
}
