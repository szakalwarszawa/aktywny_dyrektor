<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Connection;

use Jaspersoft\Client\Client;
use Jaspersoft\Service\JobService;
use Jaspersoft\Exception\RESTRequestException;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\RaportBundle\Exception\ConnectException;
use Jaspersoft\Service\ReportService;
use Jaspersoft\Service\OptionsService;
use Jaspersoft\Service\RepositoryService;
use Jaspersoft\Service\Result\SearchResourcesResult;

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
     * Konstruktor.
     *
     * @param string $serverUrl
     * @param string $username
     * @param string $password
     * @param int $requestTimeout
     */
    public function __construct(string $serverUrl, string $username, string $password, int $requestTimeout = 30)
    {
        $this->serverUrl = $serverUrl;
        $this->username = $username;
        $this->password = $password;
        $this->requestTimeout = $requestTimeout;

        $this->prepareClient();
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
        $jasperClient->serverInfo();
        try {
            $jasperClient->serverInfo();
        } catch (RESTRequestException $exception) {
            throw new ConnectException('Niepoprawny adres serwera lub dane użytkownika Jasper.');
        }

        $this->jasperClient = $jasperClient;
        $this->password = '';
    }

    /**
     * Zwraca jobService do zapytań klienta Jasper.
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
