<?php

namespace Parp\MainBundle\Entity;
use APY\DataGridBundle\Grid\Mapping as GRID;
use Doctrine\ORM\Mapping as ORM;

/**
 * Zastepstwo
 *
 * @ORM\Table(name="zastepstwo")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\ZastepstwoRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, opis, ktoZastepuje, kogoZastepuje, dataOd, dataDo")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
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
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $deletedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=5000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $ktoZastepuje;
    
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $kogoZastepuje;
    
    
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=true, type="datetime")
    */
    private $dataOd;
    
    
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=true, type="datetime")
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
     * @return Zastepstwo
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
     * Set opis
     *
     * @param string $opis
     *
     * @return Zastepstwo
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
     * Set ktoZastepuje
     *
     * @param string $ktoZastepuje
     *
     * @return Zastepstwo
     */
    public function setKtoZastepuje($ktoZastepuje)
    {
        $this->ktoZastepuje = $ktoZastepuje;

        return $this;
    }

    /**
     * Get ktoZastepuje
     *
     * @return string
     */
    public function getKtoZastepuje()
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
    public function setKogoZastepuje($kogoZastepuje)
    {
        $this->kogoZastepuje = $kogoZastepuje;

        return $this;
    }

    /**
     * Get kogoZastepuje
     *
     * @return string
     */
    public function getKogoZastepuje()
    {
        return $this->kogoZastepuje;
    }

    /**
     * Set wniosekHistoriaStatusu
     *
     * @param \Parp\MainBundle\Entity\Wniosek $wniosekHistoriaStatusu
     *
     * @return Zastepstwo
     */
    public function setWniosekHistoriaStatusu(\Parp\MainBundle\Entity\Wniosek $wniosekHistoriaStatusu = null)
    {
        $this->wniosekHistoriaStatusu = $wniosekHistoriaStatusu;

        return $this;
    }

    /**
     * Get wniosekHistoriaStatusu
     *
     * @return \Parp\MainBundle\Entity\Wniosek
     */
    public function getWniosekHistoriaStatusu()
    {
        return $this->wniosekHistoriaStatusu;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->wniosekHistoriaStatusu = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add wniosekHistoriaStatusu
     *
     * @param \Parp\MainBundle\Entity\Wniosek $wniosekHistoriaStatusu
     *
     * @return Zastepstwo
     */
    public function addWniosekHistoriaStatusu(\Parp\MainBundle\Entity\Wniosek $wniosekHistoriaStatusu)
    {
        $this->wniosekHistoriaStatusu[] = $wniosekHistoriaStatusu;

        return $this;
    }

    /**
     * Remove wniosekHistoriaStatusu
     *
     * @param \Parp\MainBundle\Entity\Wniosek $wniosekHistoriaStatusu
     */
    public function removeWniosekHistoriaStatusu(\Parp\MainBundle\Entity\Wniosek $wniosekHistoriaStatusu)
    {
        $this->wniosekHistoriaStatusu->removeElement($wniosekHistoriaStatusu);
    }

    /**
     * Set dataOd
     *
     * @param \DateTime $dataOd
     *
     * @return Zastepstwo
     */
    public function setDataOd($dataOd)
    {
        $this->dataOd = $dataOd;

        return $this;
    }

    /**
     * Get dataOd
     *
     * @return \DateTime
     */
    public function getDataOd()
    {
        return $this->dataOd;
    }

    /**
     * Set dataDo
     *
     * @param \DateTime $dataDo
     *
     * @return Zastepstwo
     */
    public function setDataDo($dataDo)
    {
        $this->dataDo = $dataDo;

        return $this;
    }

    /**
     * Get dataDo
     *
     * @return \DateTime
     */
    public function getDataDo()
    {
        return $this->dataDo;
    }
}
