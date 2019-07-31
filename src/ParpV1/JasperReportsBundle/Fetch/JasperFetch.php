<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Fetch;

use ParpV1\JasperReportsBundle\Connection\JasperConnection;
use Jaspersoft\Service\JobService;
use Jaspersoft\Service\ReportService;
use Jaspersoft\Exception\RESTRequestException;
use Jaspersoft\Dto\Resource\ReportUnit;
use Jaspersoft\Dto\Resource\Folder;
use Jaspersoft\Service\Criteria\RepositorySearchCriteria;
use Doctrine\Common\Collections\ArrayCollection;
use ParpV1\JasperReportsBundle\Exception\ResourceNotFoundException;
use ParpV1\JasperReportsBundle\Utils\SpecialCharactersUrlEncoder;

/**
 * JasperFetch
 *
 * Pobieranie repozytoriów z jaspera.
 */
class JasperFetch
{
    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var ReportService
     */
    private $reportService;

    /**
     * @var OptionsService
     */
    private $optionsService;

    /**
     * @var RepositoryService
     */
    private $repositoryService;

    /**
     * Konstruktor
     *
     * @param JasperConnection $jasperConnection
     */
    public function __construct(JasperConnection $jasperConnection)
    {
        if ($jasperConnection->jasperActive) {
            $this->jobService = $jasperConnection->getJobService();
            $this->reportService = $jasperConnection->getReportService();
            $this->optionsService = $jasperConnection->getOptionsService();
            $this->repositoryService = $jasperConnection->getRepositoryService();
        }
    }

    /**
     * Sprawdza czy zasób istnieje.
     *
     * @param string $url
     *
     * @return bool
     */
    public function isResourceExist(string $url): bool
    {
        return null !== $this->getResourceInfo($url);
    }

    /**
     * Zwraca informacje o zasobie.
     *
     * @param string $url
     *
     * @return ReportUnit|Folder|null
     */
    public function getResourceInfo(string $url)
    {
        $url = SpecialCharactersUrlEncoder::encode($url);

        $repositoryService = $this->repositoryService;

        try {
            $resource = $repositoryService->getResource($url);
        } catch (RESTRequestException $exception) {
            return null;
        }

        return $resource;
    }

    /**
     * Zwraca opcje wejściowe raportu.
     * Wyłapuje RESTRequestException który jest rzucany przez \Jaspersoft
     * kiedy raport nie posiada zdefiniowanych opcji wejściowych.
     *
     * @param string $reportUri
     *
     * @throws ResourceNotFoundException gdy zasób nie istnieje
     *
     * @return array|null
     */
    public function getReportOptions(string $reportUri): ?array
    {
        if (!$this->isResourceExist($reportUri)) {
            throw new ResourceNotFoundException();
        }

        $reportService = $this->reportService;
        $inputControls = null;
        try {
            $inputControls = $reportService
                ->getReportInputControls(SpecialCharactersUrlEncoder::encode($reportUri))
            ;
        } catch (RESTRequestException $exception) {
            return $inputControls;
        }

        return $inputControls;
    }

    /**
     * Zwraca raporty z folderu z podanego url.
     *
     * @param string $folder
     * @param bool $asObject
     *
     * @return ArrayCollection
     */
    public function findAllFromFolderUrl(string $folderUrl, bool $asObject = false)
    {
        $repositoryService = $this->repositoryService;
        $criteria = new RepositorySearchCriteria();
        $criteria->folderUri = $folderUrl;
        $folderChildrenResources = new ArrayCollection();
        try {
            $resources = $repositoryService->searchResources($criteria);
        } catch (RESTRequestException $exception) {
            return $folderChildrenResources;
        }


        foreach ($resources->items as $resource) {
            if ($asObject) {
                $folderChildrenResources->add($resource);

                continue;
            }

            $folderChildrenResources->add([
                'id' => null,
                'title' => $resource->label,
                'url' => $resource->uri
            ]);
        }

        return $folderChildrenResources;
    }
}
