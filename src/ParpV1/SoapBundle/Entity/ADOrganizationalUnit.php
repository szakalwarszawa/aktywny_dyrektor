<?php

namespace ParpV1\SoapBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * UserZasoby
 *
 * @ORM\Table(name="ad_ou")
 * @ORM\Entity(repositoryClass="ParpV1\SoapBundle\Entity\ADOrganizationalUnitRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,ou")
 * @UniqueEntity(
 *     fields={"dn"},
 *     errorPath="dn",
 *     message="Samaccountname musi być unikalna")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class ADOrganizationalUnit
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
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $objectclass;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $ou;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $distinguishedname;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $instancetype;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $whencreated;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $whenchanged;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $usncreated;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $usnchanged;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $objectguid;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $objectcategory;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dscorepropagationdata;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dn;
    

    /**
     * @var text
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $member;
    
    
    /**
     * @ORM\ManyToMany(targetEntity="ADUser", inversedBy="ADOUs")
     * @ORM\JoinTable(name="aduser_adou")
     * @GRID\Column(field="ADUsers.samaccountname:group_concat", title="ADUsers", filter="select", selectMulti="true")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $ADUsers;
    
    
    
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
     */
    private $createdAt;

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
     * @return ADOrganizationalUnit
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
     * Set objectclass
     *
     * @param string $objectclass
     *
     * @return ADOrganizationalUnit
     */
    public function setObjectclass($objectclass)
    {
        $this->objectclass = $objectclass;

        return $this;
    }


    /**
     * Get objectclass
     *
     * @return string
     */
    public function getObjectclass()
    {
        return $this->objectclass;
    }


    /**
     * Set ou
     *
     * @param string $ou
     *
     * @return ADOrganizationalUnit
     */
    public function setOu($ou)
    {
        $this->ou = $ou;

        return $this;
    }


    /**
     * Get ou
     *
     * @return string
     */
    public function getOu()
    {
        return $this->ou;
    }


    /**
     * Set distinguishedname
     *
     * @param string $distinguishedname
     *
     * @return ADOrganizationalUnit
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
     * Set instancetype
     *
     * @param string $instancetype
     *
     * @return ADOrganizationalUnit
     */
    public function setInstancetype($instancetype)
    {
        $this->instancetype = $instancetype;

        return $this;
    }


    /**
     * Get instancetype
     *
     * @return string
     */
    public function getInstancetype()
    {
        return $this->instancetype;
    }


    /**
     * Set whencreated
     *
     * @param string $whencreated
     *
     * @return ADOrganizationalUnit
     */
    public function setWhencreated($whencreated)
    {
        $this->whencreated = $whencreated;

        return $this;
    }


    /**
     * Get whencreated
     *
     * @return string
     */
    public function getWhencreated()
    {
        return $this->whencreated;
    }


    /**
     * Set whenchanged
     *
     * @param string $whenchanged
     *
     * @return ADOrganizationalUnit
     */
    public function setWhenchanged($whenchanged)
    {
        $this->whenchanged = $whenchanged;

        return $this;
    }


    /**
     * Get whenchanged
     *
     * @return string
     */
    public function getWhenchanged()
    {
        return $this->whenchanged;
    }


    /**
     * Set usncreated
     *
     * @param string $usncreated
     *
     * @return ADOrganizationalUnit
     */
    public function setUsncreated($usncreated)
    {
        $this->usncreated = $usncreated;

        return $this;
    }


    /**
     * Get usncreated
     *
     * @return string
     */
    public function getUsncreated()
    {
        return $this->usncreated;
    }


    /**
     * Set usnchanged
     *
     * @param string $usnchanged
     *
     * @return ADOrganizationalUnit
     */
    public function setUsnchanged($usnchanged)
    {
        $this->usnchanged = $usnchanged;

        return $this;
    }


    /**
     * Get usnchanged
     *
     * @return string
     */
    public function getUsnchanged()
    {
        return $this->usnchanged;
    }


    /**
     * Set name
     *
     * @param string $name
     *
     * @return ADOrganizationalUnit
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
     * Set objectguid
     *
     * @param string $objectguid
     *
     * @return ADOrganizationalUnit
     */
    public function setObjectguid($objectguid)
    {
        $this->objectguid = $objectguid;

        return $this;
    }


    /**
     * Get objectguid
     *
     * @return string
     */
    public function getObjectguid()
    {
        return $this->objectguid;
    }


    /**
     * Set objectcategory
     *
     * @param string $objectcategory
     *
     * @return ADOrganizationalUnit
     */
    public function setObjectcategory($objectcategory)
    {
        $this->objectcategory = $objectcategory;

        return $this;
    }


    /**
     * Get objectcategory
     *
     * @return string
     */
    public function getObjectcategory()
    {
        return $this->objectcategory;
    }


    /**
     * Set dscorepropagationdata
     *
     * @param string $dscorepropagationdata
     *
     * @return ADOrganizationalUnit
     */
    public function setDscorepropagationdata($dscorepropagationdata)
    {
        $this->dscorepropagationdata = $dscorepropagationdata;

        return $this;
    }


    /**
     * Get dscorepropagationdata
     *
     * @return string
     */
    public function getDscorepropagationdata()
    {
        return $this->dscorepropagationdata;
    }


    /**
     * Set dn
     *
     * @param string $dn
     *
     * @return ADOrganizationalUnit
     */
    public function setDn($dn)
    {
        $this->dn = $dn;

        return $this;
    }


    /**
     * Get dn
     *
     * @return string
     */
    public function getDn()
    {
        return $this->dn;
    }


    /**
     * Set member
     *
     * @param string $member
     *
     * @return ADOrganizationalUnit
     */
    public function setMember($member)
    {
        $this->member = $member;

        return $this;
    }

    /**
     * Get member
     *
     * @return string
     */
    public function getMember()
    {
        return $this->member;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ADUsers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setCreatedAt(new \Datetime());
    }

    /**
     * Add aDUser
     *
     * @param \ParpV1\SoapBundle\Entity\ADUser $aDUser
     *
     * @return ADOrganizationalUnit
     */
    public function addADUser(\ParpV1\SoapBundle\Entity\ADUser $aDUser)
    {
        $this->ADUsers[] = $aDUser;

        return $this;
    }

    /**
     * Remove aDUser
     *
     * @param \ParpV1\SoapBundle\Entity\ADUser $aDUser
     */
    public function removeADUser(\ParpV1\SoapBundle\Entity\ADUser $aDUser)
    {
        $this->ADUsers->removeElement($aDUser);
    }

    /**
     * Get aDUsers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getADUsers()
    {
        return $this->ADUsers;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return ADOrganizationalUnit
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
}
