<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek")
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\WniosekRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,status.nazwa,createdBy,createdAt,lockedBy,pracownicy,userZasoby.opis:group_concat,editornames")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt",timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class Wniosek
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
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $createdBy;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $lockedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $lockedAt;


    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $numer;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $jednostkaOrganizacyjna;


    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="WniosekEditor", mappedBy="wniosek")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $editors;


    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="WniosekViewer", mappedBy="wniosek")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $viewers;


    /**
     *
     * @ORM\ManyToOne(targetEntity="WniosekStatus", inversedBy="wnioski")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * @GRID\Column(field="status.nazwa", title="Status")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $status;


    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $statusname;


    /**
     * @ORM\OneToMany(targetEntity="Wniosek", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Wniosek", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;


    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true, length=5000)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $viewernames;
    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true, length=5000)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $editornames;


    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="WniosekHistoriaStatusow", mappedBy="wniosek")
     * @@Gedmo\Mapping\Annotation\Versioned
     * @GRID\Column(field="statusy.nazwa:group_concat", title="Statusy", filter="select", selectMulti="true")
     */
    private $statusy;


    /**
     *
     * @ORM\OneToOne(targetEntity="WniosekNadanieOdebranieZasobow", inversedBy="wniosek")
     * @ORM\JoinColumn(name="WniosekNadanieOdebranieZasobow_id", referencedColumnName="id")
     */
    private $wniosekNadanieOdebranieZasobow;



    /**
     *
     * @ORM\OneToOne(targetEntity="WniosekUtworzenieZasobu", inversedBy="wniosek")
     * @ORM\JoinColumn(name="wniosekUtworzenieZasobu_id", referencedColumnName="id")
     */
    private $wniosekUtworzenieZasobu;



    /**
     *
     * @ORM\OneToOne(targetEntity="WniosekNumer", inversedBy="wniosek")
     * @ORM\JoinColumn(name="WniosekNumer_id", referencedColumnName="id")
     */
    private $wniosekNumer;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="Entry", mappedBy="wniosek")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $ADentries;

    /**
     * @ORM\Column(type="boolean", name="is_blocked")
     */
    protected $isBlocked = false;

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
     * @return Wniosek
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
     * Set createdBy
     *
     * @param string $createdBy
     *
     * @return Wniosek
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Wniosek
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
     * Set lockedBy
     *
     * @param string $lockedBy
     *
     * @return Wniosek
     */
    public function setLockedBy($lockedBy)
    {
        $this->lockedBy = $lockedBy;

        return $this;
    }

    /**
     * Get lockedBy
     *
     * @return string
     */
    public function getLockedBy()
    {
        return $this->lockedBy;
    }

    /**
     * Set lockedAt
     *
     * @param \DateTime $lockedAt
     *
     * @return Wniosek
     */
    public function setLockedAt($lockedAt)
    {
        $this->lockedAt = $lockedAt;

        return $this;
    }

    /**
     * Get lockedAt
     *
     * @return \DateTime
     */
    public function getLockedAt()
    {
        return $this->lockedAt;
    }

    /**
     * Set numer
     *
     * @param string $numer
     *
     * @return Wniosek
     */
    public function setNumer($numer)
    {
        $this->numer = $numer;

        return $this;
    }

    /**
     * Get numer
     *
     * @return string
     */
    public function getNumer()
    {
        return $this->numer;
    }

    public function czyWtrakcieTworzenia()
    {
        return $this->numer  == "wniosek w trakcie tworzenia";
    }

    /**
     * Set jednostkaOrganizacyjna
     *
     * @param string $jednostkaOrganizacyjna
     *
     * @return Wniosek
     */
    public function setJednostkaOrganizacyjna($jednostkaOrganizacyjna)
    {
        $this->jednostkaOrganizacyjna = $jednostkaOrganizacyjna;

        return $this;
    }

    /**
     * Get jednostkaOrganizacyjna
     *
     * @return string
     */
    public function getJednostkaOrganizacyjna()
    {
        return $this->jednostkaOrganizacyjna;
    }
    /**
     * Set status
     *
     * @param \ParpV1\MainBundle\Entity\WniosekStatus $status
     *
     * @return WniosekStatus
     */
    public function setStatus(\ParpV1\MainBundle\Entity\WniosekStatus $status = null)
    {
        if ($status) {
            $this->status = $status;
            $this->setStatusname($status->__toString());
        }
        return $this;
    }

    /**
     * Get status
     *
     * @return \ParpV1\MainBundle\Entity\WniosekStatus
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * Set statusname
     *
     * @param string $statusname
     *
     * @return Wniosek
     */
    public function setStatusname($statusname)
    {
        $this->statusname = $statusname;

        return $this;
    }

    /**
     * Get statusname
     *
     * @return string
     */
    public function getStatusname()
    {
        return $this->statusname;
    }

    /**
     * Set viewernames
     *
     * @param string $viewernames
     *
     * @return Wniosek
     */
    public function setViewernames($viewername)
    {
        $this->viewernames = $viewername;

        return $this;
    }
    /**
     * Set viewernames
     *
     * @param string $viewernames
     *
     * @return Wniosek
     */
    public function setViewernamesSet()
    {
        $names = array();
        foreach ($this->getViewers() as $v) {
            $names[] = $v->getSamaccountname();
        }
        $this->setViewernames(implode(",", $names));

        return $this;
    }

    /**
     * Get viewernames
     *
     * @return string
     */
    public function getViewernames()
    {
        return $this->viewernames;
    }

    /**
     * Set editornames
     *
     * @param string $editornames
     *
     * @return Wniosek
     */
    public function setEditornames($editornames)
    {
        $this->editornames = $editornames;

        return $this;
    }
    /**
     * Set editornames
     *
     * @param string $editornames
     *
     * @return Wniosek
     */
    public function setEditornamesSet()
    {
        $names = array();
        foreach ($this->getEditors() as $v) {
            $names[] = $v->getSamaccountname();
        }
        $this->setEditornames(implode(",", $names));

        return $this;
    }

    /**
     * Get editornames
     *
     * @return string
     */
    public function getEditornames()
    {
        return $this->editornames;
    }

    /**
     * Add child
     *
     * @param \ParpV1\MainBundle\Entity\Wniosek $child
     *
     * @return Wniosek
     */
    public function addChild(\ParpV1\MainBundle\Entity\Wniosek $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \ParpV1\MainBundle\Entity\Wniosek $child
     */
    public function removeChild(\ParpV1\MainBundle\Entity\Wniosek $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \ParpV1\MainBundle\Entity\Wniosek $parent
     *
     * @return Wniosek
     */
    public function setParent(\ParpV1\MainBundle\Entity\Wniosek $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \ParpV1\MainBundle\Entity\Wniosek
     */
    public function getParent()
    {
        return $this->parent;
    }


    public function getViewersNames()
    {
        $names = [];
        foreach ($this->getViewers() as $v) {
            $names[$v->getSamaccountname()] = $v->getSamaccountname();
        }
        return implode(", ", $names);
    }

    public function getEditorsNames()
    {
        if (substr($this->getStatus()->getNazwaSystemowa(), 0, 1) == "1") {
            return "";
        }
        $names = [];
        foreach ($this->getEditors() as $v) {
            $names[$v->getSamaccountname()] = $v->getSamaccountname();
        }
        return implode(", ", $names);
    }

    /**
     * Add statusy
     *
     * @param \ParpV1\MainBundle\Entity\WniosekHistoriaStatusow $statusy
     *
     * @return Wniosek
     */
    public function addStatusy(\ParpV1\MainBundle\Entity\WniosekHistoriaStatusow $statusy)
    {
        $this->statusy[] = $statusy;

        return $this;
    }

    /**
     * Remove statusy
     *
     * @param \ParpV1\MainBundle\Entity\WniosekHistoriaStatusow $statusy
     */
    public function removeStatusy(\ParpV1\MainBundle\Entity\WniosekHistoriaStatusow $statusy)
    {
        $this->statusy->removeElement($statusy);
    }

    /**
     * Get statusy
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStatusy()
    {
        $sort = \Doctrine\Common\Collections\Criteria::create();
        $sort->orderBy(
            array(
            'createdAt' => \Doctrine\Common\Collections\Criteria::ASC
            )
        )
            ->where(\Doctrine\Common\Collections\Criteria::expr()->isNull("deletedAt"));
        return $this->statusy->matching($sort);
        return $this->statusy;
    }

    /**
     * Add editor
     *
     * @param \ParpV1\MainBundle\Entity\WniosekEditor $editor
     *
     * @return Wniosek
     */
    public function addEditor(\ParpV1\MainBundle\Entity\WniosekEditor $editor)
    {
        $this->editors[] = $editor;

        return $this;
    }

    /**
     * Remove editor
     *
     * @param \ParpV1\MainBundle\Entity\WniosekEditor $editor
     */
    public function removeEditor(\ParpV1\MainBundle\Entity\WniosekEditor $editor)
    {
        $this->editors->removeElement($editor);
    }

    /**
     * Get editors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEditors()
    {
        return $this->editors;
    }

    /**
     * Add viewer
     *
     * @param \ParpV1\MainBundle\Entity\WniosekViewer $viewer
     *
     * @return Wniosek
     */
    public function addViewer(\ParpV1\MainBundle\Entity\WniosekViewer $viewer)
    {
        $this->viewers[] = $viewer;

        return $this;
    }

    /**
     * Remove viewer
     *
     * @param \ParpV1\MainBundle\Entity\WniosekViewer $viewer
     */
    public function removeViewer(\ParpV1\MainBundle\Entity\WniosekViewer $viewer)
    {
        $this->viewers->removeElement($viewer);
    }

    /**
     * Get viewers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getViewers()
    {
        return $this->viewers;
    }

    /**
     * Set wniosekNadanieOdebranieZasobow
     *
     * @param \ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow
     *
     * @return Wniosek
     */
    public function setWniosekNadanieOdebranieZasobow(\ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wniosekNadanieOdebranieZasobow = null)
    {
        $this->wniosekNadanieOdebranieZasobow = $wniosekNadanieOdebranieZasobow;

        return $this;
    }

    /**
     * Get wniosekNadanieOdebranieZasobow
     *
     * @return \ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow
     */
    public function getWniosekNadanieOdebranieZasobow()
    {
        return $this->wniosekNadanieOdebranieZasobow;
    }

    /**
     * Set wniosekUtworzenieZasobu
     *
     * @param \ParpV1\MainBundle\Entity\WniosekUtworzenieZasobu $wniosekUtworzenieZasobu
     *
     * @return Wniosek
     */
    public function setWniosekUtworzenieZasobu(\ParpV1\MainBundle\Entity\WniosekUtworzenieZasobu $wniosekUtworzenieZasobu = null)
    {
        $this->wniosekUtworzenieZasobu = $wniosekUtworzenieZasobu;

        return $this;
    }

    /**
     * Get wniosekUtworzenieZasobu
     *
     * @return \ParpV1\MainBundle\Entity\WniosekUtworzenieZasobu
     */
    public function getWniosekUtworzenieZasobu()
    {
        return $this->wniosekUtworzenieZasobu;
    }

    /**
     * Set wniosekNumer
     *
     * @param \ParpV1\MainBundle\Entity\WniosekNumer $wniosekNumer
     *
     * @return Wniosek
     */
    public function setWniosekNumer(\ParpV1\MainBundle\Entity\WniosekNumer $wniosekNumer = null)
    {
        $this->wniosekNumer = $wniosekNumer;

        return $this;
    }

    /**
     * Get wniosekNumer
     *
     * @return \ParpV1\MainBundle\Entity\WniosekNumer
     */
    public function getWniosekNumer()
    {
        return $this->wniosekNumer;
    }

    /**
     * Add aDentry
     *
     * @param \ParpV1\MainBundle\Entity\Entry $aDentry
     *
     * @return Wniosek
     */
    public function addADentry(\ParpV1\MainBundle\Entity\Entry $aDentry)
    {
        $this->ADentries[] = $aDentry;

        return $this;
    }

    /**
     * Remove aDentry
     *
     * @param \ParpV1\MainBundle\Entity\Entry $aDentry
     */
    public function removeADentry(\ParpV1\MainBundle\Entity\Entry $aDentry)
    {
        $this->ADentries->removeElement($aDentry);
    }

    /**
     * Get aDentries
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getADentries()
    {
        return $this->ADentries;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->editors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->viewers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->statusy = new \Doctrine\Common\Collections\ArrayCollection();
        $this->ADentries = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get isBlocked
     */
    public function getIsBlocked()
    {
        return $this->isBlocked;
    }

    /**
     * Blokuje końcowo wniosek.
     *
     * @return Wniosek
     */
    public function zablokujKoncowoWniosek()
    {
        $this->isBlocked = true;

        return $this;
    }

    /**
     * Set isBlocked
     *
     * @return Wniosek
     */
    public function setIsBlocked($isBlocked)
    {
        $this->isBlocked = $isBlocked;

        return $this;
    }
}
