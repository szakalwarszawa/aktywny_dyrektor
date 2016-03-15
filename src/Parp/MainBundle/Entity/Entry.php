<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entry
 *
 * @ORM\Table(name="entry")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\EntryRepository")
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Entry
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
     * @ORM\Column(name="samaccountname", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message = "Nazwa konta nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa konta musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa konta musi zawierać maxymalnie do {{ limit }} znaków.") 
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $samaccountname;

    /**
     * @var string
     *
     * @ORM\Column(name="cn", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message = "Imię i nazwisko pracownika nie jest wypełnione.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Imię i nazwisko musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Imię i nazwisko musi zawierać maxymalnie do {{ limit }} znaków.") 
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $cn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="accountExpires", type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $accountExpires;

    /**
     * @var integer
     *
     * @ORM\Column(name="department", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message = "Nie wybrano Biura/Departamentu.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa Biura Departamentu musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa Biura Departamentu musi zawierać maxymalnie do {{ limit }} znaków.") 
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $department;

    /**
     * @var string
     *
     * @ORM\Column(name="division", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $division;

    /**
     * @var string
     *
     * @ORM\Column(name="info", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message = "Nie wybrano sekcji")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa sekcji musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa sekcji musi zawierać maxymalnie do {{ limit }} znaków.") 
     */
    private $info;

    /**
     * @var string
     *
     * @ORM\Column(name="manager", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $manager;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message = "Nie wybrano stanowiska")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa stanowiska musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa stanowiska musi zawierać maxymalnie do {{ limit }} znaków.") * 
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="initials", type="string", length=10, nullable=true)
     * @Assert\NotBlank(message = "Inicjały pracownika nie są wypełnione.")
     * @Assert\Length(
     *      min = 2,
     *      max = 4,
     *      minMessage = "Inicjały muszą zawierać od {{ limit }} znaków.",
     *      maxMessage = "Inicjały muszą zawierać maxymalnie do {{ limit }} znaków.")* 
     */
    private $initials;

    /**
     * @var string
     *
     * @ORM\Column(name="distinguishedname", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $distinguishedName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fromWhen", type="datetime", nullable=true)
     * @Assert\NotBlank(message = "Data zmiany nie jest wypełniona.");
     *  
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $fromWhen;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isImplemented", type="boolean")
     */
    private $isImplemented;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="initialrights", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message = "Nie wybrano uprawnień początkowych")
     * 
     */
    private $initialrights;

    
    /**
     * @var string
     *
     * @ORM\Column(name="memberOf", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $memberOf;

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
     * Set cn
     *
     * @param string $cn
     * @return Entry
     */
    public function setCn($cn)
    {
        $this->cn = $cn;

        return $this;
    }

    /**
     * Get cn
     *
     * @return string 
     */
    public function getCn()
    {
        return $this->cn;
    }

    /**
     * Set accountExpires
     *
     * @param \DateTime $accountExpires
     * @return Entry
     */
    public function setAccountExpires($accountExpires)
    {
        $this->accountExpires = $accountExpires;

        return $this;
    }

    /**
     * Get accountExpires
     *
     * @return \DateTime 
     */
    public function getAccountExpires()
    {
        return $this->accountExpires;
    }

    /**
     * Set department
     *
     * @param integer $department
     * @return Entry
     */
    public function setDepartment($department)
    {
        $this->department = $department;

        return $this;
    }

    /**
     * Get department
     *
     * @return integer 
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * Set division
     *
     * @param integer $division
     * @return Entry
     */
    public function setDivision($division)
    {
        $this->division = $division;

        return $this;
    }

    /**
     * Get division
     *
     * @return integer 
     */
    public function getDivision()
    {
        return $this->division;
    }

    /**
     * Set info
     *
     * @param integer $info
     * @return Entry
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get division
     *
     * @return integer 
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set manager
     *
     * @param string $manager
     * @return Entry
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * Get manager
     *
     * @return string 
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Set title
     *
     * @param integer $title
     * @return Entry
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return integer 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set distinguishedName
     *
     * @param string $distinguishedName
     * @return Entry
     */
    public function setDistinguishedName($distinguishedName)
    {
        $this->distinguishedName = $distinguishedName;

        return $this;
    }

    /**
     * Get distinguishedName
     *
     * @return string 
     */
    public function getDistinguishedName()
    {
        return $this->distinguishedName;
    }

    /**
     * Set fromWhen
     *
     * @param \DateTime $fromWhen
     * @return Entry
     */
    public function setFromWhen($fromWhen)
    {
        $this->fromWhen = $fromWhen;

        return $this;
    }

    /**
     * Get fromWhen
     *
     * @return \DateTime 
     */
    public function getFromWhen()
    {
        return $this->fromWhen;
    }

    public function __construct()
    {
        $this->isImplemented = false;
    }

    /**
     * Set isImplemented
     *
     * @param boolean $isImplemented
     * @return Entry
     */
    public function setIsImplemented($isImplemented)
    {
        $this->isImplemented = $isImplemented;

        return $this;
    }

    /**
     * Get isImplemented
     *
     * @return boolean 
     */
    public function getIsImplemented()
    {
        return $this->isImplemented;
    }

    /**
     * Set samaccountname
     *
     * @param string $samaccountname
     * @return Entry
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
     * Set initials
     *
     * @param string $initials
     * @return Entry
     */
    public function setInitials($initials)
    {
        $this->initials = $initials;

        return $this;
    }

    /**
     * Get initials
     *
     * @return string 
     */
    public function getInitials()
    {
        return $this->initials;
    }
    
    
    

    /**
     * Set initialrights
     *
     * @param string $initialrights
     * @return Entry
     */
    public function setInitialrights($initialrights)
    {
        $this->initialrights = $initialrights;

        return $this;
    }

    /**
     * Get initialrights
     *
     * @return string 
     */
    public function getInitialrights()
    {
        return $this->initialrights;
    }

    /**
     * Set memberOf
     *
     * @param string $memberOf
     *
     * @return Entry
     */
    public function setMemberOf($memberOf)
    {
        $this->memberOf = $memberOf;

        return $this;
    }

    /**
     * Get memberOf
     *
     * @return string
     */
    public function getMemberOf()
    {
        return $this->memberOf;
    }
}
