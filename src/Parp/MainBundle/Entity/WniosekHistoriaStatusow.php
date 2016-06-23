<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek_historia_statusow")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\WniosekHistoriaStatusowRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,status,createdBy,createdAt,statusName")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class WniosekHistoriaStatusow
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @GRID\Column(field="id", title="Numer")
     */
    private $id;
    
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $deletedAt;


    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $rejected = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $createdBy;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $createdAt;
  
    /**
     *
     * @ORM\ManyToOne(targetEntity="WniosekNadanieOdebranieZasobow", inversedBy="statusy")
     * @ORM\JoinColumn(name="wniosek_id", referencedColumnName="id")
     * @GRID\Column(field="wniosek.id", title="Numer wniosku")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wniosek;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="WniosekNadanieOdebranieZasobowStatus")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * @GRID\Column(field="status.nazwa", title="Status")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $status;
    
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $statusname;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;

    

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
     * @return WniosekHistoriaStatusow
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
     * Set rejected
     *
     * @param boolean $rejected
     *
     * @return WniosekHistoriaStatusow
     */
    public function setRejected($rejected)
    {
        $this->rejected = $rejected;

        return $this;
    }

    /**
     * Get rejected
     *
     * @return boolean
     */
    public function getRejected()
    {
        return $this->rejected;
    }

    /**
     * Set createdBy
     *
     * @param string $createdBy
     *
     * @return WniosekHistoriaStatusow
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return WniosekHistoriaStatusow
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set statusname
     *
     * @param string $statusname
     *
     * @return WniosekHistoriaStatusow
     */
    public function setStatusname($statusname)
    {
        $this->statusname = $statusname;

        return $this;
    }

    /**
     * Get statusname
     *
     * @return string
     */
    public function getStatusname()
    {
        return $this->statusname;
    }

    /**
     * Set opis
     *
     * @param string $opis
     *
     * @return WniosekHistoriaStatusow
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
     * Set wniosek
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wniosek
     *
     * @return WniosekHistoriaStatusow
     */
    public function setWniosek(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wniosek = null)
    {
        $this->wniosek = $wniosek;

        return $this;
    }

    /**
     * Get wniosek
     *
     * @return \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow
     */
    public function getWniosek()
    {
        return $this->wniosek;
    }

    /**
     * Set status
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowStatus $status
     *
     * @return WniosekHistoriaStatusow
     */
    public function setStatus(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowStatus $status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowStatus
     */
    public function getStatus()
    {
        return $this->status;
    }
    public function __toString(){
        return $this->getStatusname();
    }
}
