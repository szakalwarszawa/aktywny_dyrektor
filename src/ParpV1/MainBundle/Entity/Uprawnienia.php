<?php

namespace ParpV1\MainBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;

use Doctrine\ORM\Mapping as ORM;

/**
 * Uprawnienia
 *
 * @ORM\Table(name="uprawnienia")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\UprawnieniaRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, opis, grupaAd, grupy.opis:group_concat", groupBy={"id"})
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Uprawnienia
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
     * @ORM\Column(name="opis", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;
    
     /**
     * @var boolean
     *
     * @ORM\Column(name="czy_sekcja", type="boolean")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $czy_sekcja;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="czy_edycja", type="boolean")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $czy_edycja;

    /**
     * @ORM\ManyToMany(targetEntity="GrupyUprawnien", mappedBy="uprawnienia")
     * @ORM\JoinTable(name="uprawnienia_w_grupach")
     * @GRID\Column(field="grupy.opis:group_concat", title="Grupy", filter="select", selectMulti="true")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $grupy;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $grupyHistoriaZmian;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $grupaAd;

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
     * Set opis
     *
     * @param string $opis
     * @return Uprawnienia
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
     * Constructor
     */
    public function __construct()
    {
        $this->grupy = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add grupy
     *
     * @param \Parp\MainBundle\Entity\GrupyUprawnien $grupy
     * @return Uprawnienia
     */
    public function addGrupy(\Parp\MainBundle\Entity\GrupyUprawnien $grupy)
    {
        $this->grupy[] = $grupy;
        $grupy->addUprawnienie($uprawnienia);
        $this->setGrupyHistoriaZmian();
        return $this;
    }

    /**
     * Remove grupy
     *
     * @param \Parp\MainBundle\Entity\GrupyUprawnien $grupy
     */
    public function removeGrupy(\Parp\MainBundle\Entity\GrupyUprawnien $grupy)
    {
        $grupy->removeUprawnienie($uprawnienia);
        $this->grupy->removeElement($grupy);
        $this->setGrupyHistoriaZmian();
    }

    /**
     * Get grupy
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGrupy()
    {
        return $this->grupy;
    }

    /**
     * Set czy_sekcja
     *
     * @param boolean $czySekcja
     * @return Uprawnienia
     */
    public function setCzySekcja($czySekcja)
    {
        $this->czy_sekcja = $czySekcja;

        return $this;
    }

    /**
     * Get czy_sekcja
     *
     * @return boolean
     */
    public function getCzySekcja()
    {
        return $this->czy_sekcja;
    }

    /**
     * Set czy_edycja
     *
     * @param boolean $czyEdycja
     * @return Uprawnienia
     */
    public function setCzyEdycja($czyEdycja)
    {
        $this->czy_edycja = $czyEdycja;

        return $this;
    }

    /**
     * Get czy_edycja
     *
     * @return boolean
     */
    public function getCzyEdycja()
    {
        return $this->czy_edycja;
    }
    public function __toString()
    {
        return $this->getOpis();
    }

    /**
     * Set grupyHistoriaZmian
     *
     * @param string $grupyHistoriaZmian
     *
     * @return Uprawnienia
     */
    public function setGrupyHistoriaZmian($grupyHistoriaZmian = null)
    {
        if ($grupyHistoriaZmian === null) {
            $grupyHistoriaZmian = array();
            foreach ($this->getGrupy() as $g) {
                $grupyHistoriaZmian[] = $g->getOpis();
            }
            $grupyHistoriaZmian = implode(",", $grupyHistoriaZmian);
        }
        $this->grupyHistoriaZmian = $grupyHistoriaZmian;

        return $this;
    }

    /**
     * Get grupyHistoriaZmian
     *
     * @return string
     */
    public function getGrupyHistoriaZmian()
    {
        return $this->grupyHistoriaZmian;
    }
    
    /**
     * @@ORM\PrePersist()
     * @@ORM\PreUpdate()
     */
    public function preUpdate()
    {
        //die('a');
        $this->setGrupyHistoriaZmian();
        //die($this->getUprawnieniaHistoriaZmian());
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Uprawnienia
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
     * Set grupaAd
     *
     * @param string $grupaAd
     *
     * @return Uprawnienia
     */
    public function setGrupaAd($grupaAd)
    {
        $this->grupaAd = $grupaAd;

        return $this;
    }

    /**
     * Get grupaAd
     *
     * @return string
     */
    public function getGrupaAd()
    {
        return $this->grupaAd;
    }
}
