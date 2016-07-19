<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;;


// ALe można tez wymusic unikalnośc 2 pól na raz
//@UniqueEntity(
//     fields={"host", "port"},
//     errorPath="port",
//     message="This port is already in use on that host.")
//

/**
 * Departament
 *
 * @ORM\Table(name="departament")
 * a@Gedmo\Loggable
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"name"},
 *     errorPath="name",
 *     message="Nazwa Biura/Departamentu musi być unikalna")
 * @UniqueEntity(
 *     fields={"shortname"},
 *     errorPath="shortname",
 *     message="Skrót Biura/Departamentu musi być unikalny")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, name, shortname")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Departament
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
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
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message = "Nazwa Biura/Departamentu nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa Biura/Departamentu musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa Biura/Departamentu musi zawierać maxymalnie do {{ limit }} znaków.")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="shortname", type="string", length=5)
     * @Assert\NotBlank(message = "Skrót Biura/Departamentu nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 5,
     *      minMessage = "Skrót Biura/Departamentu musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Skrót Biura/Departamentu musi zawierać maxymalnie do {{ limit }} znaków.")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $shortname;

    /**
     * @var string
     *
     * @ORM\Column(name="name_in_rekord", type="string", length=255)
     * @Assert\NotBlank(message = "Nazwa Biura/Departamentu w Systemie Rekod nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa Biura/Departamentu w Systemie Rekodmusi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa Biura/Departamentu w Systemie Rekodmusi zawierać maxymalnie do {{ limit }} znaków.")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nameInRekord;

    
    /**
     * @var string
     *
     * @ORM\Column(name="grupyAD", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $grupyAD;
    
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="ouAD", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $ouAD;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="nowaStruktura", type="boolean", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nowaStruktura;

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
     * Set name
     *
     * @param string $name
     * @return Departament
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set shortname
     *
     * @param string $shortname
     * @return Departament
     */
    public function setShortname($shortname)
    {
        $this->shortname = $shortname;

        return $this;
    }

    /**
     * Get shortname
     *
     * @return string 
     */
    public function getShortname()
    {
        return $this->shortname;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Departament
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
    public function __toString(){
        return $this->name;
    }

    /**
     * Set nameInRekord
     *
     * @param string $nameInRekord
     *
     * @return Departament
     */
    public function setNameInRekord($nameInRekord)
    {
        $this->nameInRekord = $nameInRekord;

        return $this;
    }

    /**
     * Get nameInRekord
     *
     * @return string
     */
    public function getNameInRekord()
    {
        return $this->nameInRekord;
    }

    /**
     * Set grupyAD
     *
     * @param string $grupyAD
     *
     * @return Departament
     */
    public function setGrupyAD($grupyAD)
    {
        $this->grupyAD = $grupyAD;

        return $this;
    }

    /**
     * Get grupyAD
     *
     * @return string
     */
    public function getGrupyAD()
    {
        return $this->grupyAD;
    }

    /**
     * Set ouAD
     *
     * @param string $ouAD
     *
     * @return Departament
     */
    public function setOuAD($ouAD)
    {
        $this->ouAD = $ouAD;

        return $this;
    }

    /**
     * Get ouAD
     *
     * @return string
     */
    public function getOuAD()
    {
        return $this->ouAD;
    }

    /**
     * Set nowaStruktura
     *
     * @param boolean $nowaStruktura
     *
     * @return Departament
     */
    public function setNowaStruktura($nowaStruktura)
    {
        $this->nowaStruktura = $nowaStruktura;

        return $this;
    }

    /**
     * Get nowaStruktura
     *
     * @return boolean
     */
    public function getNowaStruktura()
    {
        return $this->nowaStruktura;
    }
}
