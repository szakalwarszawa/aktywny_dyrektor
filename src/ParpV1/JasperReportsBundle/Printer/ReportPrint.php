<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Printer;

use ParpV1\JasperReportsBundle\Constants\ReportFormat;
use ParpV1\JasperReportsBundle\Connection\JasperConnection;
use UnexpectedValueException;
use Doctrine\ORM\EntityManager;
use ParpV1\JasperReportsBundle\Entity\RolePrivilege;
use ParpV1\JasperReportsBundle\Fetch\JasperReportFetch;
use ParpV1\AuthBundle\Security\ParpUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JasperReportFetch
     */
    private $jasperFetch;

    /**
     * Konstruktor
     *
     * @param JasperConnection $jasperConnection
     * @param EntityManager $entityManager
     */
    public function __construct(
        JasperConnection $jasperConnection,
        EntityManager $entityManager,
        JasperReportFetch $jasperFetch
    ) {
        $this->jasperConnection = $jasperConnection;
        $this->entityManager = $entityManager;
        $this->jasperFetch = $jasperFetch;
    }

    /**
     * Zwraca raport w formie binarnej.
     *
     * @param mixed $reportUri
     *
     * @return string
     */
    public function printReport(ParpUser $parpUser, $reportUri, string $format = ReportFormat::PDF): string
    {
        if (!in_array($format, ReportFormat::getFormats())) {
            throw new UnexpectedValueException('Niewspierany format raportu');
        }

        $entityManager = $this->entityManager;
        $allowedPaths = $entityManager
            ->getRepository(RolePrivilege::class)
            ->findPathsByRoles($parpUser->getRoles(), $this->jasperFetch);

        $hasAccess = false;

        foreach ($allowedPaths as $path) {
            if ($reportUri === $path['url']) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            throw new AccessDeniedHttpException('Nie posiadasz uprawnień do tego raportu.');
        }

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
