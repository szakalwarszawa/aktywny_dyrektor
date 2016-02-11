<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Uprawnienia
 *
 * @ORM\Table(name="uprawnienia")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\UprawnieniaRepository")
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
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=255)
     */
    private $opis;
    
     /**
     * @var boolean
     *
     * @ORM\Column(name="czy_sekcja", type="boolean")
     */
    private $czy_sekcja;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="czy_edycja", type="boolean")
     */
    private $czy_edycja;

    /**
     * @ORM\ManyToMany(targetEntity="GrupyUprawnien", mappedBy="uprawnienia")
     */
    private $grupy;

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
        
        return $this;
    }

    /**
     * Remove grupy
     *
     * @param \Parp\MainBundle\Entity\GrupyUprawnien $grupy
     */
    public function removeGrupy(\Parp\MainBundle\Entity\GrupyUprawnien $grupy)
    {
        $this->grupy->removeElement($grupy);
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
}
