<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Constants;

/**
 * Dostępne formaty raportu Jasper.
 */
class ReportFormat
{
    /**
     * @var string
     */
    const PDF = 'pdf';

    /**
     * @var string
     */
    const XLS = 'xls';

    /**
     * @var string
     */
    const DOCX = 'docx';

    /**
     * @var string
     */
    const PPTX = 'pptx';

    /**
     * @var string
     */
    const CSV = 'csv';

    /**
     * Zwraca dostępne formaty
     *
     * @return array
     */
    public static function getFormats(): array
    {
        return [
            self::PDF,
            self::XLS,
            self::DOCX,
            self::PPTX,
            self::CSV,
        ];
    }
}
