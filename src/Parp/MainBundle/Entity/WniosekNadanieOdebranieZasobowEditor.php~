<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek_nadanie_odebranie_zasobow_editor")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowEditorRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,samaccountname,zasobId")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class WniosekNadanieOdebranieZasobowEditor
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
     *
     * @ORM\ManyToOne(targetEntity="WniosekNadanieOdebranieZasobow", inversedBy="editors")
     * @ORM\JoinColumn(name="wniosek_id", referencedColumnName="id")
     * @Gedmo\Mapping\Annotation\Versioned
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
     * Set wniosek
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wniosek
     *
     * @return WniosekNadanieOdebranieZasobowEditor
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
}
