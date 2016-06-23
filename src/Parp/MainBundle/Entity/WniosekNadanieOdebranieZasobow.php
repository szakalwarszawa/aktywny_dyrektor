<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek_nadanie_odebranie_zasobow")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,status.nazwa,createdBy,createdAt,pracownicy,userZasoby.opis:group_concat,editornames")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class WniosekNadanieOdebranieZasobow
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
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $pracownikSpozaParp = false;
    
    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="WniosekNadanieOdebranieZasobowEditor", mappedBy="wniosek")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $editors;
    
    
    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="WniosekNadanieOdebranieZasobowViewer", mappedBy="wniosek")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $viewers;
    
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="WniosekNadanieOdebranieZasobowStatus", inversedBy="wnioski")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * @GRID\Column(field="status.nazwa", title="Status")
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
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $pracownicy;

    
    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="UserZasoby", mappedBy="wniosek")
     * @@Gedmo\Mapping\Annotation\Versioned
     * @GRID\Column(field="userZasoby.opis", title="Uprawnienia", filter="select", selectMulti="true")
     */
    private $userZasoby;
    
    
    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zasobId;
    
    
    
    /**
     * @ORM\OneToMany(targetEntity="WniosekNadanieOdebranieZasobow", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="WniosekNadanieOdebranieZasobow", inversedBy="children")
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
     * @return WniosekNadanieOdebranieZasobow
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
     * @return WniosekNadanieOdebranieZasobow
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
     * @return WniosekNadanieOdebranieZasobow
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
     * @return WniosekNadanieOdebranieZasobow
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
     * @return WniosekNadanieOdebranieZasobow
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
     * @return WniosekNadanieOdebranieZasobow
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
        return $this->id ? $this->id : "Wniosek w trakcie tworzenia";
    }

    /**
     * Set jednostkaOrganizacyjna
     *
     * @param string $jednostkaOrganizacyjna
     *
     * @return WniosekNadanieOdebranieZasobow
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
     * Set pracownikSpozaParp
     *
     * @param boolean $pracownikSpozaParp
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setPracownikSpozaParp($pracownikSpozaParp)
    {
        $this->pracownikSpozaParp = $pracownikSpozaParp;

        return $this;
    }

    /**
     * Get pracownikSpozaParp
     *
     * @return boolean
     */
    public function getPracownikSpozaParp()
    {
        return $this->pracownikSpozaParp;
    }

    /**
     * Add editor
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowEditor $editor
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function addEditor(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowEditor $editor)
    {
        $this->editors[] = $editor;

        return $this;
    }

    /**
     * Remove editor
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowEditor $editor
     */
    public function removeEditor(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowEditor $editor)
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
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowViewer $viewer
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function addViewer(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowViewer $viewer)
    {
        $this->viewers[] = $viewer;

        return $this;
    }

    /**
     * Remove viewer
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowViewer $viewer
     */
    public function removeViewer(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowViewer $viewer)
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
     * Set status
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowStatus $status
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setStatus(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowStatus $status = null)
    {
        $this->status = $status;
        $this->setStatusname($status->__toString());
        return $this;
    }

    /**
     * Get status
     *
     * @return \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobowStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set pracownicy
     *
     * @param string $pracownicy
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setPracownicy($pracownicy)
    {
        $this->pracownicy = $pracownicy;

        return $this;
    }

    /**
     * Get pracownicy
     *
     * @return string
     */
    public function getPracownicy()
    {
        return $this->pracownicy;
    }
    
    /**
     * Set pracownicy
     *
     * @param string $pracownicy
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setPracownicySpozaParp($pracownicy)
    {
        if($this->getPracownikSpozaParp())
            $this->pracownicy = $pracownicy;

        return $this;
    }

    /**
     * Get pracownicy
     *
     * @return string
     */
    public function getPracownicySpozaParp()
    {
        return $this->getPracownikSpozaParp() ? $this->pracownicy : "";
    }

    /**
     * Add userZasoby
     *
     * @param \Parp\MainBundle\Entity\UserZasoby $userZasoby
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function addUserZasoby(\Parp\MainBundle\Entity\UserZasoby $userZasoby)
    {
        $this->userZasoby[] = $userZasoby;

        return $this;
    }

    /**
     * Remove userZasoby
     *
     * @param \Parp\MainBundle\Entity\UserZasoby $userZasoby
     */
    public function removeUserZasoby(\Parp\MainBundle\Entity\UserZasoby $userZasoby)
    {
        $this->userZasoby->removeElement($userZasoby);
    }

    /**
     * Get userZasoby
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserZasoby()
    {
        return $this->userZasoby;
    }
    
    public function getJsonSams(){
        $ar = explode(",", $this->getPracownicy());
        $ars = array();
        foreach($ar as $a){
            $ars[$a] = 1;
        }
        return json_encode($ars);
    }


    /**
     * Set zasobId
     *
     * @param integer $zasobId
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setZasobId($zasobId)
    {
        $this->zasobId = $zasobId;

        return $this;
    }

    /**
     * Get zasobId
     *
     * @return integer
     */
    public function getZasobId()
    {
        return $this->zasobId;
    }

    /**
     * Set statusname
     *
     * @param string $statusname
     *
     * @return WniosekNadanieOdebranieZasobow
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
     * @return WniosekNadanieOdebranieZasobow
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
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setViewernamesSet()
    {
        $names = array();
        foreach($this->getViewers() as $v){
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
     * @return WniosekNadanieOdebranieZasobow
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
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setEditornamesSet()
    {
        $names = array();
        foreach($this->getEditors() as $v){
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
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $child
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function addChild(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $child
     */
    public function removeChild(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $child)
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
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $parent
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setParent(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow
     */
    public function getParent()
    {
        return $this->parent;
    }
    
    
    public function getViewersNames()
    {
        $names = [];
        foreach($this->getViewers() as $v){
            $names[$v->getSamaccountname()] = $v->getSamaccountname();
        }    
        return implode(", ", $names);
    }
    
    public function getEditorsNames()
    {
        if(substr($this->getStatus()->getNazwaSystemowa(), 0, 1) == "1"){
            return "";    
        }
        $names = [];
        foreach($this->getEditors() as $v){
            $names[$v->getSamaccountname()] = $v->getSamaccountname();
        }    
        return implode(", ", $names);
    }

    /**
     * Add statusy
     *
     * @param \Parp\MainBundle\Entity\WniosekHistoriaStatusow $statusy
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function addStatusy(\Parp\MainBundle\Entity\WniosekHistoriaStatusow $statusy)
    {
        $this->statusy[] = $statusy;

        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->editors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->viewers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userZasoby = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->statusy = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Remove statusy
     *
     * @param \Parp\MainBundle\Entity\WniosekHistoriaStatusow $statusy
     */
    public function removeStatusy(\Parp\MainBundle\Entity\WniosekHistoriaStatusow $statusy)
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
        return $this->statusy;
    }
}
