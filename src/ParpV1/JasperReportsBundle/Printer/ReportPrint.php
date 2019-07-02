<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Printer;

use ParpV1\JasperReportsBundle\Constants\ReportFormat;
use ParpV1\JasperReportsBundle\Connection\JasperConnection;

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
     * Konstruktor
     *
     * @param JasperConnection $jasperConnection
     */
    public function __construct(JasperConnection $jasperConnection)
    {
        $this->jasperConnection = $jasperConnection;
    }

    /**
     * Zwraca raport w formie binarnej.
     *
     * @param mixed $reportUri
     *
     * @return string
     */
    public function printReport($reportUri, string $format = ReportFormat::PDF): string
    {
        if ($reportUri instanceof JasperPath) {
            $reportUri = $reportUri->getUrl();
        }

        $reportService = $this
            ->jasperConnection
            ->getReportService()
        ;

        $report = $reportService->runReport($reportUri, $format);

        return $report;
    }
}
