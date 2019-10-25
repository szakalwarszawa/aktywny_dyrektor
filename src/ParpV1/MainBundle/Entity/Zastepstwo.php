<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ParpV1\MainBundle\Validator as ZastepstwoAssert;
use ParpV1\MainBundle\Entity\Wniosek;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Zastepstwo
 *
 * @ORM\Table(name="zastepstwo")
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\ZastepstwoRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, opis, ktoZastepuje, kogoZastepuje, dataOd, dataDo")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 * @ZastepstwoAssert\Zastepstwa
 */
class Zastepstwo
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $deletedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=5000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @Assert\Length(min=10)
     */
    private $opis;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     * @Assert\NotBlank()
     */
    private $ktoZastepuje;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     * @Assert\NotBlank()
     */
    private $kogoZastepuje;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=true, type="datetime")
     * @Gedmo\Mapping\Annotation\Versioned
     * @Assert\Type("DateTime")
     * @Assert\Expression(
     *      "this.getDataOd() <=  this.getDataDo()",
     *      message="Data rozpoczęcia nie może być późniejsza od daty końca zastępstwa!"
     * )
    */
    private $dataOd;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=true, type="datetime")
     * @Gedmo\Mapping\Annotation\Versioned
     * @Assert\Type("DateTime")
     * @Assert\Expression(
     *      "this.getDataDo() >=  this.getDataOd()",
     *      message="Data końcowa nie może być wczesniejsza od daty rozpoczęcia zastępstwa!"
     * )
     * @Assert\Expression(
     *      "this.getDataDo() > this.getCurrentDate()",
     *      message="Data końcowa nie może być datą przeszłą!"
     * )
    */
    private $dataDo;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="Wniosek", mappedBy="zastepstwo")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $wniosekHistoriaStatusu;

     /**
     * @var string|null
     *
     * @ORM\Column(name="lastModifiedBy", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $lastModifiedBy;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set deletedAt
     *
     * @param DateTime|null $deletedAt
     *
     * @return Zastepstwo
     */
    public function setDeletedAt(?DateTime $deletedAt): Zastepstwo
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * Set opis
     *
     * @param string $opis
     *
     * @return Zastepstwo
     */
    public function setOpis(string $opis): Zastepstwo
    {
        $this->opis = $opis;

        return $this;
    }

    /**
     * Get opis
     *
     * @return string|null
     */
    public function getOpis(): ?string
    {
        return $this->opis;
    }

    /**
     * Set ktoZastepuje
     *
     * @param string $ktoZastepuje
     *
     * @return Zastepstwo
     */
    public function setKtoZastepuje(string $ktoZastepuje): Zastepstwo
    {
        $this->ktoZastepuje = $ktoZastepuje;

        return $this;
    }

    /**
     * Get ktoZastepuje
     *
     * @return string|null
     */
    public function getKtoZastepuje(): ?string
    {
        return $this->ktoZastepuje;
    }

    /**
     * Set kogoZastepuje
     *
     * @param string $kogoZastepuje
     *
     * @return Zastepstwo
     */
    public function setKogoZastepuje(string $kogoZastepuje): Zastepstwo
    {
        $this->kogoZastepuje = $kogoZastepuje;

        return $this;
    }

    /**
     * Get kogoZastepuje
     *
     * @return string|null
     */
    public function getKogoZastepuje(): ?string
    {
        return $this->kogoZastepuje;
    }

    /**
     * Set wniosekHistoriaStatusu
     *
     * @param Wniosek|null $wniosekHistoriaStatusu
     *
     * @return Zastepstwo
     */
    public function setWniosekHistoriaStatusu(?Wniosek $wniosekHistoriaStatusu): Zastepstwo
    {
        $this->wniosekHistoriaStatusu = $wniosekHistoriaStatusu;

        return $this;
    }

    /**
     * Get wniosekHistoriaStatusu
     *
     * @return Wniosek|null
     */
    public function getWniosekHistoriaStatusu(): ?Wniosek
    {
        return $this->wniosekHistoriaStatusu;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->wniosekHistoriaStatusu = new ArrayCollection();
    }

    /**
     * Add wniosekHistoriaStatusu
     *
     * @param Wniosek $wniosekHistoriaStatusu
     *
     * @return Zastepstwo
     */
    public function addWniosekHistoriaStatusu(Wniosek $wniosekHistoriaStatusu): Zastepstwo
    {
        $this->wniosekHistoriaStatusu[] = $wniosekHistoriaStatusu;

        return $this;
    }

    /**
     * Remove wniosekHistoriaStatusu
     *
     * @param Wniosek $wniosekHistoriaStatusu
     */
    public function removeWniosekHistoriaStatusu(Wniosek $wniosekHistoriaStatusu)
    {
        $this->wniosekHistoriaStatusu->removeElement($wniosekHistoriaStatusu);
    }

    /**
     * Set dataOd
     *
     * @param DateTime $dataOd
     *
     * @return Zastepstwo
     */
    public function setDataOd(DateTime $dataOd): Zastepstwo
    {
        $this->dataOd = $dataOd;

        return $this;
    }

    /**
     * Get dataOd
     *
     * @return DateTime|null
     */
    public function getDataOd(): ?DateTime
    {
        return $this->dataOd;
    }

    /**
     * Set dataDo
     *
     * @param DateTime $dataDo
     *
     * @return Zastepstwo
     */
    public function setDataDo(DateTime $dataDo): Zastepstwo
    {
        $this->dataDo = $dataDo;

        return $this;
    }

    /**
     * Get dataDo
     *
     * @return DateTime|null
     */
    public function getDataDo(): ?DateTime
    {
        return $this->dataDo;
    }

    /**
     * Set lastModifiedBy.
     *
     * @param string|null $lastModifiedBy
     *
     * @return Zastepstwo
     */
    public function setLastModifiedBy(?string $lastModifiedBy): Zastepstwo
    {
        $this->lastModifiedBy = $lastModifiedBy;

        return $this;
    }

    /**
     * Get lastModifiedBy.
     *
     * @return string|null
     */
    public function getLastModifiedBy(): ?string
    {
        return $this->lastModifiedBy;
    }

    /**
     * Get Current Date.
     *
     * @return DateTime
     */
    public function getCurrentDate(): DateTime
    {
        return new DateTime();
    }
}
