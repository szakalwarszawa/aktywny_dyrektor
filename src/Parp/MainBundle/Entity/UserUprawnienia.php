<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserUprawnienia
 *
 * @ORM\Table(name="useruprawnienia")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\UserUprawnieniaRepository")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class UserUprawnienia
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
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="data_nadania", type="datetime")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataNadania;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="data_odebrania", type="datetime",nullable = true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataOdebrania;

    /**
     * @var boolean
     *
     * @ORM\Column(name="czy_aktywne", type="boolean")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $czyAktywne;

    
    
    /**
     * @var integer
     *
     * @ORM\Column(name="uprawnienie_id", type="integer")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $uprawnienie_id;
    
    
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
     * @return UserUprawnienia
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
     * Set opis
     *
     * @param string $opis
     * @return UserUprawnienia
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
     * Set dataNadania
     *
     * @param \DateTime $dataNadania
     * @return UserUprawnienia
     */
    public function setDataNadania($dataNadania)
    {
        $this->dataNadania = $dataNadania;

        return $this;
    }

    /**
     * Get dataNadania
     *
     * @return \DateTime 
     */
    public function getDataNadania()
    {
        return $this->dataNadania;
    }

    /**
     * Set dataOdebrania
     *
     * @param \DateTime $dataOdebrania
     * @return UserUprawnienia
     */
    public function setDataOdebrania($dataOdebrania)
    {
        $this->dataOdebrania = $dataOdebrania;

        return $this;
    }

    /**
     * Get dataOdebrania
     *
     * @return \DateTime 
     */
    public function getDataOdebrania()
    {
        return $this->dataOdebrania;
    }

    /**
     * Set czyAktywne
     *
     * @param boolean $czyAktywne
     * @return UserUprawnienia
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
     * Set uprawnienie_id
     *
     * @param integer $uprawnienieId
     * @return UserUprawnienia
     */
    public function setUprawnienieId($uprawnienieId)
    {
        $this->uprawnienie_id = $uprawnienieId;

        return $this;
    }

    /**
     * Get uprawnienie_id
     *
     * @return integer 
     */
    public function getUprawnienieId()
    {
        return $this->uprawnienie_id;
    }

    /**
     * Set powodNadania
     *
     * @param string $powodNadania
     *
     * @return UserUprawnienia
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
     * @return UserUprawnienia
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
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return UserUprawnienia
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
}
