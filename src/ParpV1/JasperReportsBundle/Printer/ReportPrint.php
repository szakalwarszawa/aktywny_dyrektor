<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Printer;

use ParpV1\JasperReportsBundle\Constants\ReportFormat;
use ParpV1\JasperReportsBundle\Connection\JasperConnection;
use UnexpectedValueException;
use Jaspersoft\Exception\RESTRequestException;
use ParpV1\JasperReportsBundle\Constants\RaportInputParameters;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Drukarka raportów jasper.
 */
class ReportPrint
{
    /**
     * @var JasperConnection
     */
    private $jasperConnection;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * Konstruktor
     *
     * @param JasperConnection $jasperConnection
     * @param TokenStorage     $tokenStorage
     */
    public function __construct(JasperConnection $jasperConnection, TokenStorage $tokenStorage)
    {
        $this->jasperConnection = $jasperConnection;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Zwraca raport w formie binarnej.
     *
     * @param JasperPath|string $reportUri
     * @param string $format
     * @param array|null $inputControls
     *
     * @return string|null
     */
    public function printReport($reportUri, string $format = ReportFormat::PDF, ?array $inputControls = null): ?string
    {

        if (!in_array($format, ReportFormat::getFormats())) {
            throw new UnexpectedValueException('Niewspierany format raportu');
        }

        if ($reportUri instanceof JasperPath) {
            $reportUri = $reportUri->getUrl();
        }

        $reportService = $this
            ->jasperConnection
            ->getReportService()
        ;

        $inputControls[RaportInputParameters::LOGIN_PARAMETER] = $this->tokenStorage->getToken()->getUsername();

        try {
            return $reportService->runReport($reportUri, $format, null, null, $inputControls);
        } catch (RESTRequestException $exception) {
            return null;
        }

        return null;
    }
}
