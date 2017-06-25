<?php

namespace Parp\MainBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;

use Doctrine\ORM\Mapping as ORM;

/**
 * GrupyUprawnien
 *
 * @ORM\Table(name="grupyuprawnien")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, kod, opis, uprawnienia.opis:group_concat", groupBy={"id"})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
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
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $deletedAt;

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
     * @GRID\Column(field="uprawnienia.opis:group_concat", title="Uprawnienia", filter="select", selectMulti="true")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $uprawnienia;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $uprawnieniaHistoriaZmian;
    
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
        $this->setUprawnieniaHistoriaZmian();

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
        $this->setUprawnieniaHistoriaZmian();
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
    public function __toString()
    {
        return $this->getOpis();
    }

    /**
     * Set uprawnieniaHistoriaZmian
     *
     * @param string $uprawnieniaHistoriaZmian
     *
     * @return GrupyUprawnien
     */
    public function setUprawnieniaHistoriaZmian($uprawnieniaHistoriaZmian = null)
    {
        //die('aa');
        if ($uprawnieniaHistoriaZmian === null) {
            $uprawnieniaHistoriaZmian = array();
            foreach ($this->getUprawnienia() as $g) {
                $uprawnieniaHistoriaZmian[] = $g->getOpis();
            }
            $uprawnieniaHistoriaZmian = implode(",", $uprawnieniaHistoriaZmian);
        }
        $this->uprawnieniaHistoriaZmian = $uprawnieniaHistoriaZmian;

        return $this;
    }

    /**
     * Get uprawnieniaHistoriaZmian
     *
     * @return string
     */
    public function getUprawnieniaHistoriaZmian()
    {
        return $this->uprawnieniaHistoriaZmian;
    }
    
    /**
     * @@ORM\PrePersist()
     * @@ORM\PreUpdate()
     */
    public function preUpdate()
    {
        $this->setUprawnieniaHistoriaZmian();
        //die($this->getUprawnieniaHistoriaZmian());
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return GrupyUprawnien
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
     * Add uprawnienium
     *
     * @param \Parp\MainBundle\Entity\Uprawnienia $uprawnienium
     *
     * @return GrupyUprawnien
     */
    public function addUprawnienium(\Parp\MainBundle\Entity\Uprawnienia $uprawnienium)
    {
        $this->uprawnienia[] = $uprawnienium;

        return $this;
    }

    /**
     * Remove uprawnienium
     *
     * @param \Parp\MainBundle\Entity\Uprawnienia $uprawnienium
     */
    public function removeUprawnienium(\Parp\MainBundle\Entity\Uprawnienia $uprawnienium)
    {
        $this->uprawnienia->removeElement($uprawnienium);
    }
}
