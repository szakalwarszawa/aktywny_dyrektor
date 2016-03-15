<?php

namespace Parp\MainBundle\Entity;
use APY\DataGridBundle\Grid\Mapping as GRID;
use Doctrine\ORM\Mapping as ORM;

/**
 * Zadanie
 *
 * @ORM\Table(name="zadanie")
 * @ORM\Entity
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, nazwa, dataUkonczenia, ukonczonePrzez, opis, obiekt, osoby ")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Zadanie
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
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $deletedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="nazwa", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nazwa;
    
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $status;

    /**
     * @var text
     *
     * @ORM\Column(name="opis", type="text", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;
    
    /**
     * @var text
     *
     * @ORM\Column(name="komentarz", type="text", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $komentarz;

    /**
     * @var string
     *
     * @ORM\Column(name="osoby", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $osoby;
        
    
    /**
    * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataDodania;
    
    /**
    * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataUkonczenia;
    
    /**
     * @var string
     *
     * @ORM\Column(name="ukonczone_przez", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $ukonczonePrzez;
        
    /**
     * @var string
     *
     * @ORM\Column(name="obiekt", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $obiekt;
    /**
     * @var string
     *
     * @ORM\Column(name="obiektId", type="integer")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $obiektId;

    /**
     * Get id
     *
     * @return integer
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
     * @return Zadanie
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
     * Set nazwa
     *
     * @param string $nazwa
     *
     * @return Zadanie
     */
    public function setNazwa($nazwa)
    {
        $this->nazwa = $nazwa;

        return $this;
    }

    /**
     * Get nazwa
     *
     * @return string
     */
    public function getNazwa()
    {
        return $this->nazwa;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Zadanie
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set opis
     *
     * @param string $opis
     *
     * @return Zadanie
     */
    public function setOpis($opis)
    {
        $this->opis = $opis;

        return $this;
    }

    /**
     * Get opis
     *
     * @return string
     */
    public function getOpis()
    {
        return $this->opis;
    }

    /**
     * Set komentarz
     *
     * @param string $komentarz
     *
     * @return Zadanie
     */
    public function setKomentarz($komentarz)
    {
        $this->komentarz = $komentarz;

        return $this;
    }

    /**
     * Get komentarz
     *
     * @return string
     */
    public function getKomentarz()
    {
        return $this->komentarz;
    }

    /**
     * Set osoby
     *
     * @param string $osoby
     *
     * @return Zadanie
     */
    public function setOsoby($osoby)
    {
        $this->osoby = $osoby;

        return $this;
    }

    /**
     * Get osoby
     *
     * @return string
     */
    public function getOsoby()
    {
        return $this->osoby;
    }

    /**
     * Set dataDodania
     *
     * @param \DateTime $dataDodania
     *
     * @return Zadanie
     */
    public function setDataDodania($dataDodania)
    {
        $this->dataDodania = $dataDodania;

        return $this;
    }

    /**
     * Get dataDodania
     *
     * @return \DateTime
     */
    public function getDataDodania()
    {
        return $this->dataDodania;
    }

    /**
     * Set dataUkonczenia
     *
     * @param \DateTime $dataUkonczenia
     *
     * @return Zadanie
     */
    public function setDataUkonczenia($dataUkonczenia)
    {
        $this->dataUkonczenia = $dataUkonczenia;

        return $this;
    }

    /**
     * Get dataUkonczenia
     *
     * @return \DateTime
     */
    public function getDataUkonczenia()
    {
        return $this->dataUkonczenia;
    }

    /**
     * Set ukonczonePrzez
     *
     * @param string $ukonczonePrzez
     *
     * @return Zadanie
     */
    public function setUkonczonePrzez($ukonczonePrzez)
    {
        $this->ukonczonePrzez = $ukonczonePrzez;

        return $this;
    }

    /**
     * Get ukonczonePrzez
     *
     * @return string
     */
    public function getUkonczonePrzez()
    {
        return $this->ukonczonePrzez;
    }

    /**
     * Set obiekt
     *
     * @param string $obiekt
     *
     * @return Zadanie
     */
    public function setObiekt($obiekt)
    {
        $this->obiekt = $obiekt;

        return $this;
    }

    /**
     * Get obiekt
     *
     * @return string
     */
    public function getObiekt()
    {
        return $this->obiekt;
    }

    /**
     * Set obiektId
     *
     * @param integer $obiektId
     *
     * @return Zadanie
     */
    public function setObiektId($obiektId)
    {
        $this->obiektId = $obiektId;

        return $this;
    }

    /**
     * Get obiektId
     *
     * @return integer
     */
    public function getObiektId()
    {
        return $this->obiektId;
    }
}
