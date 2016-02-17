<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserUprawnienia
 *
 * @ORM\Table(name="useruprawnienia")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\UserUprawnieniaRepository")
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
     * @var string
     *
     * @ORM\Column(name="samaccountname", type="string", length=255)
     */
    private $samaccountname;

    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=255)
     */
    private $opis;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="data_nadania", type="datetime")
     */
    private $dataNadania;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="data_odebrania", type="datetime",nullable = true)
     */
    private $dataOdebrania;

    /**
     * @var boolean
     *
     * @ORM\Column(name="czy_aktywne", type="boolean")
     */
    private $czyAktywne;

    
    
    /**
     * @var integer
     *
     * @ORM\Column(name="uprawnienie_id", type="integer")
     */
    private $uprawnienie_id;

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
}
