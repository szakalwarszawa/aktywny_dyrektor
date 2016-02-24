<?php

namespace Parp\MainBundle\Entity;
use APY\DataGridBundle\Grid\Mapping as GRID;

use Doctrine\ORM\Mapping as ORM;

/**
 * GrupyUprawnien
 *
 * @ORM\Table(name="grupyuprawnien")
 * @GRID\Source(columns="id, kod, opis")
 * @ORM\Entity
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class GrupyUprawnien
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
     * @var string
     *
     * @ORM\Column(name="kod", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $kod;

    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;

    /**
     * @ORM\ManyToMany(targetEntity="Uprawnienia", inversedBy="grupy")
     * @ORM\JoinTable(name="uprawnienia_w_grupach")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $uprawnienia;

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
     * Set kod
     *
     * @param string $kod
     * @return GrupyUprawnien
     */
    public function setKod($kod)
    {
        $this->kod = $kod;

        return $this;
    }

    /**
     * Get kod
     *
     * @return string 
     */
    public function getKod()
    {
        return $this->kod;
    }

    /**
     * Set opis
     *
     * @param string $opis
     * @return GrupyUprawnien
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
        $this->uprawnienia = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add uprawnienia
     *
     * @param \Parp\MainBundle\Entity\Uprawnienia $uprawnienia
     * @return GrupyUprawnien
     */
    public function addUprawnienie(\Parp\MainBundle\Entity\Uprawnienia $uprawnienia)
    {
        $this->uprawnienia[] = $uprawnienia;

        return $this;
    }

    /**
     * Remove uprawnienia
     *
     * @param \Parp\MainBundle\Entity\Uprawnienia $uprawnienia
     */
    public function removeUprawnienie(\Parp\MainBundle\Entity\Uprawnienia $uprawnienia)
    {
        $this->uprawnienia->removeElement($uprawnienia);
    }

    /**
     * Get uprawnienia
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUprawnienia()
    {
        return $this->uprawnienia;
    }
    public function __toString(){
        return $this->getOpis();
    }
}
