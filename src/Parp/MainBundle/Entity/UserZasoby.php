<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserZasoby
 *
 * @ORM\Table(name="userzasoby")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\UserZasobyRepository")
 */
class UserZasoby
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
     * @ORM\Column(name="samaccountname", type="string", length=255)
     */
    private $samaccountname;

    /**
     * @var integer
     *
     * @ORM\Column(name="zasob_id", type="integer")
     */
    private $zasobId;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $loginDoZasobu;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $modul;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $poziomDostepu;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $aktywneOd;
    
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $bezterminowo;
    
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $aktywneOdPomijac;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $aktywneDo;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $kanalDostepu;
    
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $uprawnieniaAdministracyjne;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $odstepstwoOdProcedury;
    
    

    
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
}
