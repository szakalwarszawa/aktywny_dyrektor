<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek_viewer")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\WniosekViewerRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,samaccountname,zasobId")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class WniosekViewer
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
     * @ORM\ManyToOne(targetEntity="Wniosek", inversedBy="viewers")
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
     * @return WniosekViewer
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
     * @return WniosekViewer
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
     * @param \Parp\MainBundle\Entity\Wniosek $wniosek
     *
     * @return WniosekViewer
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
