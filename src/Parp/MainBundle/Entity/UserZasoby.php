<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserZasoby
 *
 * @ORM\Table(name="userzasoby")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\UserZasobyRepository")
 * @ORM\HasLifecycleCallbacks
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,samaccountname,zasobId")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class UserZasoby
{
    
    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     * @ORM\PreRemove()
     */
    public function preUpdate(){
        
        //die("preUpdate");
        if($this->wniosek){
            $this->wniosek->ustawPoleZasoby();
        }
        
        if($this->wniosekOdebranie){
            $this->wniosekOdebranie->ustawPoleZasoby();
        }
    }
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
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $importedFromEcm = false;

    /**
     * @var string
     *
     * @ORM\Column(name="samaccountname", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $samaccountname;

    /**
     * @var integer
     *
     * @ORM\Column(name="zasob_id", type="integer")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zasobId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="zasobOpis", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zasobOpis;
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $loginDoZasobu;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=4000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $modul;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=4000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $poziomDostepu;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $aktywneOd;
    
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $bezterminowo;
    
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $aktywneOdPomijac;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $aktywneDo;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $kanalDostepu;
    
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $uprawnieniaAdministracyjne;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $odstepstwoOdProcedury;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $powodNadania;
    
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $powodOdebrania;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="czy_aktywne", type="boolean")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $czyAktywne;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="czy_nadane", type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $czyNadane = false;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $sumowanieUprawnien = false;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="WniosekNadanieOdebranieZasobow", inversedBy="userZasoby")
     * @ORM\JoinColumn(name="wniosek_id", referencedColumnName="id")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wniosek;
    
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="WniosekNadanieOdebranieZasobow", inversedBy="userZasobyOdbierane")
     * @ORM\JoinColumn(name="wniosek_odebranie_id", referencedColumnName="id")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wniosekOdebranie;
    
    private $_ADUser;
    /**
     * Set _ADUser
     *
     * @param array $_ADUser
     * @return array
     */
    public function setADuser($_ADUser)
    {
        $this->_ADUser = $_ADUser;

        return $this;
    }

    /**
     * Get _ADUser
     *
     * @return array 
     */
    public function getADUser()
    {
        return $this->_ADUser;
    }
    
    
    private $_zasobNazwa;
    /**
     * Set _zasobNazwa
     *
     * @param string $_zasobNazwa
     * @return string
     */
    public function setZasobNazwa($_zasobNazwa)
    {
        $this->_zasobNazwa = $_zasobNazwa;

        return $this;
    }

    /**
     * Get _zasobNazwa
     *
     * @return string 
     */
    public function getZasobNazwa()
    {
        return $this->_zasobNazwa;
    }
    
    protected $idd;
    public function get_Idd()
    {
        return $this->idd;
    }
    public function getIdd()
    {
        return $this->id;
    }
    public function setIdd($idd)
    {
        return $this->idd = $idd;
    }
    
    
    
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
     * Set samaccountname
     *
     * @param string $samaccountname
     * @return UserZasoby
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

    public function getSamaccountnames(){
        //uzywane przy generaowaniu linka do edycji userZasoby przy wniosku
        return '{"'.$this->samaccountname.'" : 1}';
    }
    /**
     * Set zasobId
     *
     * @param integer $zasobId
     * @return UserZasoby
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
     * Set loginDoZasobu
     *
     * @param string $loginDoZasobu
     * @return UserZasoby
     */
    public function setLoginDoZasobu($loginDoZasobu)
    {
        $this->loginDoZasobu = $loginDoZasobu;

        return $this;
    }

    /**
     * Get loginDoZasobu
     *
     * @return string 
     */
    public function getLoginDoZasobu()
    {
        return $this->loginDoZasobu;
    }

    /**
     * Set modul
     *
     * @param string $modul
     * @return UserZasoby
     */
    public function setModul($modul)
    {
        if(is_array($modul)){
            $modul = implode(";", $modul);
        }
        $this->modul = $modul;

        return $this;
    }

    /**
     * Get modul
     *
     * @return string 
     */
    public function getModul()
    {
        
        if(is_array($this->modul)){
            $modul = implode(";", $this->modul);
        }
        return $this->modul;
    }

    /**
     * Set poziomDostepu
     *
     * @param string $poziomDostepu
     * @return UserZasoby
     */
    public function setPoziomDostepu($poziomDostepu)
    {
        if(is_array($poziomDostepu)){
            $poziomDostepu = implode(";", $poziomDostepu);
        }
        $this->poziomDostepu = $poziomDostepu;

        return $this;
    }

    /**
     * Get poziomDostepu
     *
     * @return string 
     */
    public function getPoziomDostepu()
    {
        if(is_array($this->poziomDostepu)){
            $this->poziomDostepu = implode(";", $this->poziomDostepu);
        }
        return $this->poziomDostepu;
    }

    /**
     * Set aktywneOd
     *
     * @param \DateTime $aktywneOd
     * @return UserZasoby
     */
    public function setAktywneOd($aktywneOd)
    {
        $this->aktywneOd = $aktywneOd;

        return $this;
    }

    /**
     * Get aktywneOd
     *
     * @return \DateTime 
     */
    public function getAktywneOd()
    {
        return $this->aktywneOd;
    }

    /**
     * Set bezterminowo
     *
     * @param boolean $bezterminowo
     * @return UserZasoby
     */
    public function setBezterminowo($bezterminowo)
    {
        $this->bezterminowo = $bezterminowo;

        return $this;
    }

    /**
     * Get bezterminowo
     *
     * @return boolean 
     */
    public function getBezterminowo()
    {
        return $this->bezterminowo;
    }

    /**
     * Set aktywneOdPomijac
     *
     * @param boolean $aktywneOdPomijac
     * @return UserZasoby
     */
    public function setAktywneOdPomijac($aktywneOdPomijac)
    {
        $this->aktywneOdPomijac = $aktywneOdPomijac;

        return $this;
    }

    /**
     * Get aktywneOdPomijac
     *
     * @return boolean 
     */
    public function getAktywneOdPomijac()
    {
        return $this->aktywneOdPomijac;
    }

    /**
     * Set aktywneDo
     *
     * @param \DateTime $aktywneDo
     * @return UserZasoby
     */
    public function setAktywneDo($aktywneDo)
    {
        $this->aktywneDo = $aktywneDo;

        return $this;
    }

    /**
     * Get aktywneDo
     *
     * @return \DateTime 
     */
    public function getAktywneDo()
    {
        return $this->aktywneDo;
    }

    /**
     * Set kanalDostepu
     *
     * @param string $kanalDostepu
     * @return UserZasoby
     */
    public function setKanalDostepu($kanalDostepu)
    {
        $this->kanalDostepu = $kanalDostepu;

        return $this;
    }

    /**
     * Get kanalDostepu
     *
     * @return string 
     */
    public function getKanalDostepu()
    {
        return $this->kanalDostepu;
    }

    /**
     * Set uprawnieniaAdministracyjne
     *
     * @param boolean $uprawnieniaAdministracyjne
     * @return UserZasoby
     */
    public function setUprawnieniaAdministracyjne($uprawnieniaAdministracyjne)
    {
        $this->uprawnieniaAdministracyjne = $uprawnieniaAdministracyjne;

        return $this;
    }

    /**
     * Get uprawnieniaAdministracyjne
     *
     * @return boolean 
     */
    public function getUprawnieniaAdministracyjne()
    {
        return $this->uprawnieniaAdministracyjne;
    }

    /**
     * Set odstepstwoOdProcedury
     *
     * @param string $odstepstwoOdProcedury
     * @return UserZasoby
     */
    public function setOdstepstwoOdProcedury($odstepstwoOdProcedury)
    {
        $this->odstepstwoOdProcedury = $odstepstwoOdProcedury;

        return $this;
    }

    /**
     * Get odstepstwoOdProcedury
     *
     * @return string 
     */
    public function getOdstepstwoOdProcedury()
    {
        return $this->odstepstwoOdProcedury;
    }

    /**
     * Set powodNadania
     *
     * @param string $powodNadania
     *
     * @return UserZasoby
     */
    public function setPowodNadania($powodNadania)
    {
        $this->powodNadania = $powodNadania;

        return $this;
    }

    /**
     * Get powodNadania
     *
     * @return string
     */
    public function getPowodNadania()
    {
        return $this->powodNadania;
    }

    /**
     * Set powodOdebrania
     *
     * @param string $powodOdebrania
     *
     * @return UserZasoby
     */
    public function setPowodOdebrania($powodOdebrania)
    {
        $this->powodOdebrania = $powodOdebrania;

        return $this;
    }

    /**
     * Get powodOdebrania
     *
     * @return string
     */
    public function getPowodOdebrania()
    {
        return $this->powodOdebrania;
    }

    /**
     * Set czyAktywne
     *
     * @param boolean $czyAktywne
     *
     * @return UserZasoby
     */
    public function setCzyAktywne($czyAktywne)
    {
        $this->czyAktywne = $czyAktywne;

        return $this;
    }

    /**
     * Get czyAktywne
     *
     * @return boolean
     */
    public function getCzyAktywne()
    {
        return $this->czyAktywne;
    }
    

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return UserZasoby
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
     * Set importedFromEcm
     *
     * @param boolean $importedFromEcm
     *
     * @return UserZasoby
     */
    public function setImportedFromEcm($importedFromEcm)
    {
        $this->importedFromEcm = $importedFromEcm;

        return $this;
    }

    /**
     * Get importedFromEcm
     *
     * @return boolean
     */
    public function getImportedFromEcm()
    {
        return $this->importedFromEcm;
    }
    public function getOpisHtml($spacer = "<br>", $stripTags = false){
        $html = "";
        if($this->getLoginDoZasobu() != "")
            $html .= "<b>Login:</b> ".$this->getLoginDoZasobu().$spacer;
        if($this->getModul() != "")
            $html .= "<b>Moduł:</b> ".$this->getModul().$spacer;
        if($this->getPoziomDostepu() != "")
            $html .= "<b>Poziom dostępu:</b> ".$this->getPoziomDostepu().$spacer;
        if($this->getAktywneOd() != "")
            $html .= "<b>Aktywne od:</b> ".$this->getAktywneOd()->format("Y-m-d").$spacer;
        if($this->getAktywneDo() != "")
            $html .= "<b>Aktywne do:</b> ".$this->getAktywneDo()->format("Y-m-d")." ".($this->getBezterminowo() ? "(bezterminowo)" : "").$spacer;
        if($this->getKanalDostepu() != "")
            $html .= "<b>Kanał dostępu:</b> ".$this->getKanalDostepu().$spacer;
        if($this->getUprawnieniaAdministracyjne() != "")
            $html .= "<b>Uprawnienia Administracyjne:</b> TAK".$spacer;
            
        $html = "<div>".$html."</div>";
        if($stripTags)
            $html = strip_tags($html);
        return $html;
    }

    /**
     * Set czyNadane
     *
     * @param boolean $czyNadane
     *
     * @return UserZasoby
     */
    public function setCzyNadane($czyNadane)
    {
        $this->czyNadane = $czyNadane;

        return $this;
    }

    /**
     * Get czyNadane
     *
     * @return boolean
     */
    public function getCzyNadane()
    {
        return $this->czyNadane;
    }

    /**
     * Set sumowanieUprawnien
     *
     * @param boolean $sumowanieUprawnien
     *
     * @return UserZasoby
     */
    public function setSumowanieUprawnien($sumowanieUprawnien)
    {
        $this->sumowanieUprawnien = $sumowanieUprawnien;

        return $this;
    }

    /**
     * Get sumowanieUprawnien
     *
     * @return boolean
     */
    public function getSumowanieUprawnien()
    {
        return $this->sumowanieUprawnien;
    }

    /**
     * Set wniosek
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wniosek
     *
     * @return UserZasoby
     */
    public function setWniosek(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wniosek = null)
    {
        if($wniosek){
            $wniosek->addUserZasoby($this);        
        }
        if($wniosek === null && $this->wniosek != null){
            $this->wniosek->ustawPoleZasoby();
        }
        $this->wniosek = $wniosek;
        if($this->wniosek){
            $this->wniosek->ustawPoleZasoby();
        }

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

    /**
     * Set wniosekOdebranie
     *
     * @param \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wniosekOdebranie
     *
     * @return UserZasoby
     */
    public function setWniosekOdebranie(\Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow $wniosekOdebranie = null)
    {
        $this->wniosekOdebranie = $wniosekOdebranie;
        if($this->wniosekOdebranie){
            $this->wniosekOdebranie->ustawPoleZasoby();
        }

        return $this;
    }

    /**
     * Get wniosekOdebranie
     *
     * @return \Parp\MainBundle\Entity\WniosekNadanieOdebranieZasobow
     */
    public function getWniosekOdebranie()
    {
        return $this->wniosekOdebranie;
    }

    /**
     * Set zasobOpis
     *
     * @param string $zasobOpis
     *
     * @return UserZasoby
     */
    public function setZasobOpis($zasobOpis)
    {
        $this->zasobOpis = $zasobOpis;

        return $this;
    }

    /**
     * Get zasobOpis
     *
     * @return string
     */
    public function getZasobOpis()
    {
        return $this->zasobOpis;
    }
}
