<?php

namespace Parp\SoapBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * UserZasoby
 *
 * @ORM\Table(name="ad_user")
 * @ORM\Entity(repositoryClass="Parp\SoapBundle\Entity\ADUserRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,samaccountname")
 * @UniqueEntity(
 *     fields={"samaccountname"},
 *     errorPath="samaccountname",
 *     message="Samaccountname musi być unikalna")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class ADUser
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
     * @var boolean
     *
     * @ORM\Column(type="boolean", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $isDisabled;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $accountExpires;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $name;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $email;
                
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $initials;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $title;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $info;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $description;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $department;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $division;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $lastlogon;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $manager;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $thumbnailphoto;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $useraccountcontrol;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $distinguishedname;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $cn;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    //private $memberOf;
    
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    //private $roles;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $memberOfNames;
    
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $rolesNames;
    
    
    /**
     * @ORM\ManyToMany(targetEntity="ADGroup", mappedBy="ADUsers")
     * @ORM\JoinTable(name="aduser_adgroup")
     * @GRID\Column(field="ADGroups.cn:group_concat", title="ADGroups", filter="select", selectMulti="true")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $ADGroups;
    
    /**
     * @ORM\ManyToMany(targetEntity="ADOrganizationalUnit", mappedBy="ADUsers")
     * @ORM\JoinTable(name="aduser_adou")
     * @GRID\Column(field="ADOUs.dn:group_concat", title="ADOUs", filter="select", selectMulti="true")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $ADOUs;

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
     * @return ADUser
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
     * @return ADUser
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
     * Set isDisabled
     *
     * @param boolean $isDisabled
     *
     * @return ADUser
     */
    public function setIsDisabled($isDisabled)
    {
        $this->isDisabled = $isDisabled;

        return $this;
    }

    /**
     * Get isDisabled
     *
     * @return boolean
     */
    public function getIsDisabled()
    {
        return $this->isDisabled;
    }

    /**
     * Set accountExpires
     *
     * @param string $accountExpires
     *
     * @return ADUser
     */
    public function setAccountExpires($accountExpires)
    {
        $this->accountExpires = $accountExpires;

        return $this;
    }

    /**
     * Get accountExpires
     *
     * @return string
     */
    public function getAccountExpires()
    {
        return $this->accountExpires;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ADUser
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
     * Set email
     *
     * @param string $email
     *
     * @return ADUser
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set initials
     *
     * @param string $initials
     *
     * @return ADUser
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
     * Set title
     *
     * @param string $title
     *
     * @return ADUser
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set info
     *
     * @param string $info
     *
     * @return ADUser
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set department
     *
     * @param string $department
     *
     * @return ADUser
     */
    public function setDepartment($department)
    {
        $this->department = $department;

        return $this;
    }

    /**
     * Get department
     *
     * @return string
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * Set division
     *
     * @param string $division
     *
     * @return ADUser
     */
    public function setDivision($division)
    {
        $this->division = $division;

        return $this;
    }

    /**
     * Get division
     *
     * @return string
     */
    public function getDivision()
    {
        return $this->division;
    }

    /**
     * Set lastlogon
     *
     * @param string $lastlogon
     *
     * @return ADUser
     */
    public function setLastlogon($lastlogon)
    {
        $this->lastlogon = $lastlogon;

        return $this;
    }

    /**
     * Get lastlogon
     *
     * @return string
     */
    public function getLastlogon()
    {
        return $this->lastlogon;
    }

    /**
     * Set manager
     *
     * @param string $manager
     *
     * @return ADUser
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
     * Set thumbnailphoto
     *
     * @param string $thumbnailphoto
     *
     * @return ADUser
     */
    public function setThumbnailphoto($thumbnailphoto)
    {
        $this->thumbnailphoto = $thumbnailphoto;

        return $this;
    }

    /**
     * Get thumbnailphoto
     *
     * @return string
     */
    public function getThumbnailphoto()
    {
        return $this->thumbnailphoto;
    }

    /**
     * Set useraccountcontrol
     *
     * @param string $useraccountcontrol
     *
     * @return ADUser
     */
    public function setUseraccountcontrol($useraccountcontrol)
    {
        $this->useraccountcontrol = $useraccountcontrol;

        return $this;
    }

    /**
     * Get useraccountcontrol
     *
     * @return string
     */
    public function getUseraccountcontrol()
    {
        return $this->useraccountcontrol;
    }

    /**
     * Set distinguishedname
     *
     * @param string $distinguishedname
     *
     * @return ADUser
     */
    public function setDistinguishedname($distinguishedname)
    {
        $this->distinguishedname = $distinguishedname;

        return $this;
    }

    /**
     * Get distinguishedname
     *
     * @return string
     */
    public function getDistinguishedname()
    {
        return $this->distinguishedname;
    }

    /**
     * Set cn
     *
     * @param string $cn
     *
     * @return ADUser
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
     * Set description
     *
     * @param string $description
     *
     * @return ADUser
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * Set memberOfNames
     *
     * @param string $memberOfNames
     *
     * @return ADUser
     */
    public function setMemberOfNames($memberOfNames)
    {
        $this->memberOfNames = $memberOfNames;

        return $this;
    }

    /**
     * Get memberOfNames
     *
     * @return string
     */
    public function getMemberOfNames()
    {
        return $this->memberOfNames;
    }

    /**
     * Set rolesNames
     *
     * @param string $rolesNames
     *
     * @return ADUser
     */
    public function setRolesNames($rolesNames)
    {
        $this->rolesNames = $rolesNames;

        return $this;
    }

    /**
     * Get rolesNames
     *
     * @return string
     */
    public function getRolesNames()
    {
        return $this->rolesNames;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ADGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->ADOUs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add aDGroup
     *
     * @param \Parp\SoapBundle\Entity\ADGroup $aDGroup
     *
     * @return ADUser
     */
    public function addADGroup(\Parp\SoapBundle\Entity\ADGroup $aDGroup)
    {
        $this->ADGroups[] = $aDGroup;

        return $this;
    }

    /**
     * Remove aDGroup
     *
     * @param \Parp\SoapBundle\Entity\ADGroup $aDGroup
     */
    public function removeADGroup(\Parp\SoapBundle\Entity\ADGroup $aDGroup)
    {
        $this->ADGroups->removeElement($aDGroup);
    }

    /**
     * Get aDGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getADGroups()
    {
        return $this->ADGroups;
    }

    /**
     * Add aDOU
     *
     * @param \Parp\SoapBundle\Entity\ADOrganizationalUnit $aDOU
     *
     * @return ADUser
     */
    public function addADOU(\Parp\SoapBundle\Entity\ADOrganizationalUnit $aDOU)
    {
        $this->ADOUs[] = $aDOU;

        return $this;
    }

    /**
     * Remove aDOU
     *
     * @param \Parp\SoapBundle\Entity\ADOrganizationalUnit $aDOU
     */
    public function removeADOU(\Parp\SoapBundle\Entity\ADOrganizationalUnit $aDOU)
    {
        $this->ADOUs->removeElement($aDOU);
    }

    /**
     * Get aDOUs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getADOUs()
    {
        return $this->ADOUs;
    }
}
