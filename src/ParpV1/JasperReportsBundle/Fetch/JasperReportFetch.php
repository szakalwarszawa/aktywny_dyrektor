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

/**
 * JasperReportFetch
 *
 * Pobieranie repozytoriów z jaspera.
 */
class JasperReportFetch
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
        $this->jobService = $jasperConnection->getJobService();
        $this->reportService = $jasperConnection->getReportService();
        $this->optionsService = $jasperConnection->getOptionsService();
        $this->repositoryService = $jasperConnection->getRepositoryService();
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
        $repositoryService = $this->repositoryService;

        try {
            $resource = $repositoryService->getResource($url);
        } catch (RESTRequestException $exception) {
            return null;
        }

        return $resource;
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
        $resources = $repositoryService->searchResources($criteria);

        $folderChildrenResources = new ArrayCollection();
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
