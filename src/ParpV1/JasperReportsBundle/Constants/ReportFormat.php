<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Constants;

use ReflectionClass;

/**
 * Dostępne formaty raportu Jasper.
 * Stałe klasy to wyłącznie formaty w postaci rozszerzenia pliku.
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
    public static function getFormats(bool $associativeArray = false): array
    {
        if ($associativeArray) {
            return self::getClassConstants();
        }

        return array_values(self::getClassConstants());
    }

    /**
     * Zwraca stałe tej klasy.
     *
     * @return array
     */
    private static function getClassConstants(): array
    {
        $thisClass = new ReflectionClass(ReportFormat::class);

        return $thisClass->getConstants();
    }
}
