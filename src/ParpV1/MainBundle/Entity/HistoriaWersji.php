<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

/**
 * Gedmo\Loggable\Entity\LogEntry
 *
 * @ORM\Table(
 *     name="historia_wersji",
 *  indexes={
 *      @ORM\Index(name="log_class_lookup_idx", columns={"object_class"}),
 *      @ORM\Index(name="log_date_lookup_idx", columns={"logged_at"}),
 *      @ORM\Index(name="log_user_lookup_idx", columns={"username"}),
 *      @ORM\Index(name="log_version_lookup_idx", columns={"object_id", "object_class", "version"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\HistoriaWersjiRepository")
 * @ORM\HasLifecycleCallbacks
 */
class HistoriaWersji extends AbstractLogEntry
{
    /**
     * All required columns are mapped through inherited superclass
     */

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="route", type="string", length=255, nullable=true)
     */
    private $route = 'ldap_service';

    /**
     * Set url
     *
     * @param string $url
     *
     * @return HistoriaWersji
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpdate()
    {
        global $kernel;
        if ('AppCache' == get_class($kernel)) {
            $kernel = $kernel->getKernel();
        }
        $request = $kernel->getContainer()->get('request_stack')->getCurrentRequest();
        $url = $request->getUri();
        //print_r($url);
        $routeName = $request->get('_route');
        //print_r($routeName); die();

        $this->setUrl($url);
        $this->setRoute($routeName);
    }

    /**
     * Set route
     *
     * @param string $route
     *
     * @return HistoriaWersji
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get route
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    public function __construct()
    {
        $this->setRoute('ldap_service');
    }
}
