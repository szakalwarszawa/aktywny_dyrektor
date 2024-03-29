<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek_status")
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\WniosekStatusRepository")
  * @UniqueEntity(
 *     fields={"nazwaSystemowa"},
 *     errorPath="nazwaSystemowa",
 *     message="Nazwa systemowa musi być unikalna")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,nazwa,typWniosku,nazwaSystemowa,viewers,editors")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class WniosekStatus
{
    const ANULOWANO_ADMINISTRACYJNIE = '101_ANULOWANO_ADMINISTRACYJNIE';
    const ODEBRANO_ADMINISTRACYJNIE = '102_ODEBRANO_ADMINISTRACYJNIE';

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
     * @ORM\OneToMany(targetEntity="Wniosek", mappedBy="status")
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
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $finished = false;



    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typWniosku;

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
     * @return WniosekStatus
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
     * @return WniosekStatus
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
     * @return WniosekStatus
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
     * @return WniosekStatus
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
     * @return WniosekStatus
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
     * @return WniosekStatus
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
     * Set finished
     *
     * @param boolean $finished
     *
     * @return WniosekStatus
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;

        return $this;
    }

    /**
     * Get finished
     *
     * @return boolean
     */
    public function getFinished()
    {
        return $this->finished;
    }

    public function __toString()
    {
        return $this->getNazwa();
    }

    /**
     * Set typWniosku
     *
     * @param string $typWniosku
     *
     * @return WniosekStatus
     */
    public function setTypWniosku($typWniosku)
    {
        $this->typWniosku = $typWniosku;

        return $this;
    }

    /**
     * Get typWniosku
     *
     * @return string
     */
    public function getTypWniosku()
    {
        return $this->typWniosku;
    }

    /**
     * Add wnioski
     *
     * @param \ParpV1\MainBundle\Entity\Wniosek $wnioski
     *
     * @return WniosekStatus
     */
    public function addWnioski(\ParpV1\MainBundle\Entity\Wniosek $wnioski)
    {
        $this->wnioski[] = $wnioski;

        return $this;
    }

    /**
     * Remove wnioski
     *
     * @param \ParpV1\MainBundle\Entity\Wniosek $wnioski
     */
    public function removeWnioski(\ParpV1\MainBundle\Entity\Wniosek $wnioski)
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
}
