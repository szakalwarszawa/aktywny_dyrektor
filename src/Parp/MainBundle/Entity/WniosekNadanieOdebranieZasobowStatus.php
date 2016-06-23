<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek_nadanie_odebranie_zasobow_status")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowStatusRepository")
  * @UniqueEntity(
 *     fields={"nazwaSystemowa"},
 *     errorPath="nazwaSystemowa",
 *     message="Nazwa systemowa musi być unikalna")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,nazwa,nazwaSystemowa,viewers,editors")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class WniosekNadanieOdebranieZasobowStatus
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
     * @ORM\OneToMany(targetEntity="WniosekNadanieOdebranieZasobow", mappedBy="status")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $wnioski;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nazwa;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nazwaSystemowa;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;
    
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $viewers;
    
    
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $editors;
    
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->wnioski = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * @return WniosekNadanieOdebranieZasobowStatus
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
     * @return WniosekNadanieOdebranieZasobowStatus
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
     * Set nazwaSystemowa
     *
     * @param string $nazwaSystemowa
     *
     * @return WniosekNadanieOdebranieZasobowStatus
     */
    public function setNazwaSystemowa($nazwaSystemowa)
    {
        $this->nazwaSystemowa = $nazwaSystemowa;

        return $this;
    }

    /**
     * Get nazwaSystemowa
     *
     * @return string
     */
    public function getNazwaSystemowa()
    {
        return $this->nazwaSystemowa;
    }

    /**
     * Set opis
     *
     * @param string $opis
     *
     * @return WniosekNadanieOdebranieZasobowStatus
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
     * Set viewers
     *
     * @param string $viewers
     *
     * @return WniosekNadanieOdebranieZasobowStatus
     */
    public function setViewers($viewers)
    {
        $this->viewers = $viewers;

        return $this;
    }

    /**
     * Get viewers
     *
     * @return string
     */
    public function getViewers()
    {
        return $this->viewers;
    }
    

    /**
     * Set editors
     *
     * @param string $editors
     *
     * @return WniosekNadanieOdebranieZasobowStatus
     */
    public function setEditors($editors)
    {
        $this->editors = $editors;

        return $this;
    }

    /**
     * Get editors
     *
     * @return string
     */
    public function getEditors()
    {
        return $this->editors;
    }

    /**
     * Add wnioski
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wnioski
     *
     * @return WniosekNadanieOdebranieZasobowStatus
     */
    public function addWnioski(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wnioski)
    {
        $this->wnioski[] = $wnioski;

        return $this;
    }

    /**
     * Remove wnioski
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wnioski
     */
    public function removeWnioski(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wnioski)
    {
        $this->wnioski->removeElement($wnioski);
    }

    /**
     * Get wnioski
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWnioski()
    {
        return $this->wnioski;
    }
    
    public function __toString(){
        return $this->nazwa;
    }
}
