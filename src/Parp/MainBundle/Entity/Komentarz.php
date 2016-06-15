<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserZasoby
 *
 * @ORM\Table(name="komentarz")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\KomentarzRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,samaccountname,createdAt,tytul,opis")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Komentarz
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
     * @ORM\Column(name="samaccountname", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $samaccountname;
    
    
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $createdAt;
    
    /**
     * @var string
     *
     * @ORM\Column(name="tytul", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $tytul;
    
    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=5000)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;

    
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
     * @return WniosekNadanieOdebranieZasobowEditor
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
     * Set samaccountname
     *
     * @param string $samaccountname
     *
     * @return WniosekNadanieOdebranieZasobowEditor
     */
    public function setSamaccountname($samaccountname)
    {
        $this->samaccountname = $samaccountname;

        return $this;
    }

    /**
     * Get samaccountname
     *
     * @return string
     */
    public function getSamaccountname()
    {
        return $this->samaccountname;
    }


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Komentarz
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
     * Set tytul
     *
     * @param string $tytul
     *
     * @return Komentarz
     */
    public function setTytul($tytul)
    {
        $this->tytul = $tytul;

        return $this;
    }

    /**
     * Get tytul
     *
     * @return string
     */
    public function getTytul()
    {
        return $this->tytul;
    }

    /**
     * Set opis
     *
     * @param string $opis
     *
     * @return Komentarz
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
     * Set obiekt
     *
     * @param string $obiekt
     *
     * @return Komentarz
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
     * @return Komentarz
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
