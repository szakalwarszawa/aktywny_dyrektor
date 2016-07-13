<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek_nadanie_odebranie_zasobow")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,wniosek.status.nazwa,wniosek.createdBy,wniosek.createdAt,wniosek.lockedBy,pracownicy,userZasoby.opis:group_concat,wniosek.editornames")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class WniosekUtworzenieZasobu
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
     *
     * @ORM\OneToOne(targetEntity="Wniosek", inversedBy="wniosekNadanieOdebranieZasobow")
     * @ORM\JoinColumn(name="wniosek_id", referencedColumnName="id")
     * @GRID\Column(field="wniosek.status.nazwa", title="Status")
     * @GRID\Column(field="wniosek.createdBy", title="Utworzony przez")
     * @GRID\Column(field="wniosek.createdAt", type="date", title="Utworzono")
     * @GRID\Column(field="wniosek.lockedBy", title="Zablokowany przez")
     * @GRID\Column(field="wniosek.lockedAt", type="date", title="Zablokowano")
     * @GRID\Column(field="wniosek.editornames", title="Edytorzy")
     */
    private $wniosek; 
    

    /**
     *
     * @ORM\OneToOne(targetEntity="Zasoby", inversedBy="Zasob")
     * @ORM\JoinColumn(name="zasob_id", referencedColumnName="id")
     * @GRID\Column(field="wniosek.zasob.nazwa", title="Zasob")
     */
    private $zasob; 
    
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zrealizowany = false;
    
    

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
     * @return WniosekUtworzenieZasobu
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
     * Set zrealizowany
     *
     * @param boolean $zrealizowany
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setZrealizowany($zrealizowany)
    {
        $this->zrealizowany = $zrealizowany;

        return $this;
    }

    /**
     * Get zrealizowany
     *
     * @return boolean
     */
    public function getZrealizowany()
    {
        return $this->zrealizowany;
    }

    /**
     * Set wniosek
     *
     * @param \Parp\MainBundle\Entity\Wniosek $wniosek
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setWniosek(\Parp\MainBundle\Entity\Wniosek $wniosek = null)
    {
        $this->wniosek = $wniosek;

        return $this;
    }

    /**
     * Get wniosek
     *
     * @return \Parp\MainBundle\Entity\Wniosek
     */
    public function getWniosek()
    {
        return $this->wniosek;
    }

    /**
     * Set zasob
     *
     * @param \Parp\MainBundle\Entity\Zasoby $zasob
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setZasob(\Parp\MainBundle\Entity\Zasoby $zasob = null)
    {
        $this->zasob = $zasob;

        return $this;
    }

    /**
     * Get zasob
     *
     * @return \Parp\MainBundle\Entity\Zasoby
     */
    public function getZasob()
    {
        return $this->zasob;
    }
}
