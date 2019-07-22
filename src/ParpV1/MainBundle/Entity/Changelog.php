<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Changelog
 *
 * @ORM\Table(name="changelog")
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Repository\ChangelogRepository")
 */
class Changelog
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="samaccountname", type="string", length=255, nullable=true)
     */
    private $samaccountname;

    /**
     * @var string
     *
     * @ORM\Column(name="wersja", type="string", length=20)
     */
    private $wersja;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dodatkowy_tytul", type="string", length=255, nullable=true)
     */
    private $dodatkowyTytul;

    /**
     * @var string|null
     *
     * @ORM\Column(name="opis", type="text", nullable=true)
     */
    private $opis;

    /**
     * @var bool
     *
     * @ORM\Column(name="opublikowany", type="boolean")
     */
    private $opublikowany;


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
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Changelog
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set samaccountname.
     *
     * @param string|null $samaccountname
     *
     * @return Changelog
     */
    public function setSamaccountname($samaccountname = null)
    {
        $this->samaccountname = $samaccountname;

        return $this;
    }

    /**
     * Get samaccountname.
     *
     * @return string|null
     */
    public function getSamaccountname()
    {
        return $this->samaccountname;
    }

    /**
     * Set wersja.
     *
     * @param string $wersja
     *
     * @return Changelog
     */
    public function setWersja($wersja)
    {
        $this->wersja = $wersja;

        return $this;
    }

    /**
     * Get wersja.
     *
     * @return string
     */
    public function getWersja()
    {
        return $this->wersja;
    }

    /**
     * Set dodatkowyTytul.
     *
     * @param string|null $dodatkowyTytul
     *
     * @return Changelog
     */
    public function setDodatkowyTytul($dodatkowyTytul = null)
    {
        $this->dodatkowyTytul = $dodatkowyTytul;

        return $this;
    }

    /**
     * Get dodatkowyTytul.
     *
     * @return string|null
     */
    public function getDodatkowyTytul()
    {
        return $this->dodatkowyTytul;
    }

    /**
     * Set opis.
     *
     * @param string|null $opis
     *
     * @return Changelog
     */
    public function setOpis($opis = null)
    {
        $this->opis = $opis;

        return $this;
    }

    /**
     * Get opis.
     *
     * @return string|null
     */
    public function getOpis()
    {
        return $this->opis;
    }

    /**
     * Set opublikowany.
     *
     * @param bool $opublikowany
     *
     * @return Changelog
     */
    public function setOpublikowany($opublikowany)
    {
        $this->opublikowany = $opublikowany;

        return $this;
    }

    /**
     * Get opublikowany.
     *
     * @return bool
     */
    public function getOpublikowany()
    {
        return $this->opublikowany;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return Changelog
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
