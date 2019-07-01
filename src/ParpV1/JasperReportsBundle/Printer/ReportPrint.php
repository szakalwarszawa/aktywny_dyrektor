<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Printer;

use ParpV1\JasperReportsBundle\Fetch\JasperReportFetch;

/**
 * Drukarka raportów jasper.
 */
class ReportPrint
{
    /**
     * @var JasperReportFetch
     */
    private $jasperReportFetch;

    /**
     * Konstruktor
     *
     * @param JasperReportFetch
     */
    public function __construct(JasperReportFetch $jasperReportFetch)
    {
        $this->jasperReportFetch = $jasperReportFetch;
    }

    /**
     * Zwraca raport w formie binarnej.
     *
     * @param string $raportUri
     *
     * @return string
     */
    public function printReport(string $raportUri): string
    {
        return $this
            ->jasperReportFetch
            ->getReport($raportUri)
        ;
    }
}
