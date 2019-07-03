<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Helper;

use DateTime;

/**
 * FileHeadersHelper
 *
 * Określa nagłówki odpowiednie do typu pliku.
 */
class FileHeadersHelper
{
    /**
     * Zwraca datę teraźniejszą w postaci stringa.
     *
     * @return string
     */
    public static function dateNow(): string
    {
        $dateNow = (new DateTime())->format('Y-m-d_H:i:s');

        return $dateNow;
    }

    /**
     * @see FileHeadersHelper
     */
    public static function pdf(): array
    {
        return [
            'Content-Type' => 'application/pdf'
        ];
    }

    /**
     * @see FileHeadersHelper
     */
    public static function xls(): array
    {
        return [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' =>  'attachment; filename=jasper_report_' . self::dateNow() . '.xls',
            'Cache-Control' =>  'max-age=0'
        ];
    }

    /**
     * @see FileHeadersHelper
     */
    public static function docx(): array
    {
        return [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' =>  'attachment; filename=jasper_report_' . self::dateNow() . '.docx',
        ];
    }

    /**
     * @see FileHeadersHelper
     */
    public static function pptx(): array
    {
        return [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'Content-Disposition' =>  'attachment; filename=jasper_report_' . self::dateNow() . '.pptx',
        ];
    }

    /**
     * @see FileHeadersHelper
     */
    public static function csv(): array
    {
        return [
            'Content-Type' => 'text/csv',
            'Content-Disposition' =>  'attachment; filename=jasper_report_' . self::dateNow() . '.csv',
        ];
    }

    /**
     * Wywołuje odpowiednią metodę na podstawe formatu.
     *
     * @param string $format
     *
     * @return mixed
     */
    public static function resolve(string $format)
    {
        return self::$format();
    }
}
