<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosekNumer",uniqueConstraints={
 *     @ORM\UniqueConstraint(name="wniosek_idx", columns={"numer", "rok", "typWniosku"})})
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\WniosekNumerRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,status.nazwa,createdBy,createdAt,lockedBy,pracownicy,userZasoby.opis:group_concat,editornames")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class WniosekNumer
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
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $numer;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $rok;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typWniosku;
    
    /**
     *
     * @ORM\OneToOne(targetEntity="Wniosek", inversedBy="wniosekNumer")
     * @ORM\JoinColumn(name="wniosek_id", referencedColumnName="id")
     */
    private $wniosek;

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
     * @return WniosekNumer
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
     * Set numer
     *
     * @param integer $numer
     *
     * @return WniosekNumer
     */
    public function setNumer($numer)
    {
        $this->numer = $numer;

        return $this;
    }

    /**
     * Get numer
     *
     * @return integer
     */
    public function getNumer()
    {
        return $this->numer;
    }

    /**
     * Set rok
     *
     * @param integer $rok
     *
     * @return WniosekNumer
     */
    public function setRok($rok)
    {
        $this->rok = $rok;

        return $this;
    }

    /**
     * Get rok
     *
     * @return integer
     */
    public function getRok()
    {
        return $this->rok;
    }

    /**
     * Set typWniosku
     *
     * @param string $typWniosku
     *
     * @return WniosekNumer
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
     * Set wniosek
     *
     * @param \Parp\MainBundle\Entity\Wniosek $wniosek
     *
     * @return WniosekNumer
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
}
