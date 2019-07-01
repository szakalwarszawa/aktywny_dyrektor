<?php

namespace ParpV1\JasperReportsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ParpV1\JasperReportsBundle\Validator\Constraints as JasperAssert;
use Symfony\Component\Validator\Constraints as Assert;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * Path
 *
 * @ORM\Table(name="path")
 * @ORM\Entity(repositoryClass="ParpV1\JasperReportsBundle\Repository\PathRepository")
 */
class Path
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(
     *      min = 5,
     *      max = 250,
     *      minMessage = "Ścieżka raportu musi mieć minimum {{ limit }} znaków",
     *      maxMessage = "Ścieżka raportu musi mieć maksymalnie {{ limit }} znaków"
     * )
     * @JasperAssert\JasperPath
     */
    protected $url;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_repository", type="boolean")
     */
    protected $isRepository = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     * @Assert\Length(
     *      min = 0,
     *      max = 250,
     *      minMessage = "Tytuł raportu musi mieć minimum {{ limit }} znaków",
     *      maxMessage = "Tytuł raportu musi mieć maksymalnie {{ limit }} znaków"
     * )
     */
    protected $title;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return Path
     */
    public function setUrl($url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set isRepository.
     *
     * @param bool $isRepository
     *
     * @return Path
     */
    public function setIsRepository($isRepository): self
    {
        $this->isRepository = $isRepository;

        return $this;
    }

    /**
     *  is Repository.
     *
     * @return bool
     */
    public function isRepository(): bool
    {
        return $this->isRepository;
    }

    /**
     * Set title.
     *
     * @param string|null $title
     *
     * @return Path
     */
    public function setTitle($title = null): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }
}
