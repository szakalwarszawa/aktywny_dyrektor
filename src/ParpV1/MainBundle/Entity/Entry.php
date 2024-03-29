<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ParpV1\MainBundle\Entity\OdebranieZasobowEntry;

/**
 * Entry
 *
 * @ORM\Table(name="entry")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\EntryRepository")
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class Entry
{
    /**
     */
    public function preUpdate()
    {
        $d = new \Datetime();
        if (!$this->getId()) {
            $this->setCreatedAt($d);
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
     * @ORM\Column(name="memberOf", type="string", length=8000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $memberOf;



    /**
     * @var boolean
     *
     * @ORM\Column(name="isDisabled", type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $isDisabled;

    /**
     * @var string
     *
     * @ORM\Column(name="disableDescription", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $disableDescription;


    /**
     *
     * @ORM\ManyToOne(targetEntity="Wniosek", inversedBy="entries")
     * @ORM\JoinColumn(name="wniosek_id", referencedColumnName="id")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wniosek;


    /**
     * @var string
     *
     * @ORM\Column(name="publishedBy", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $publishedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="publishedAt", type="datetime", nullable=true)
     *
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $publishedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="logfile", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $logfile;

    /**
     *
     * @ORM\ManyToOne(targetEntity="DaneRekord", inversedBy="entries")
     * @ORM\JoinColumn(name="daneRekord_id", referencedColumnName="id")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $daneRekord;


    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
    */
    private $createdAt;


    /**
     * @var string
     *
     * @ORM\Column(name="createdBy", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $createdBy;


    /**
     * @var boolean
     *
     * @ORM\Column(name="activateDeactivated", type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $activateDeactivated = false;

    /**
     * Powiązane z wypchnięciem zmian do AD - reset zasobów użytkownika.
     *
     * @var OdebranieZasobowEntry
     *
     * @ORM\OneToOne(targetEntity="OdebranieZasobowEntry", orphanRemoval=true)
     * @ORM\JoinColumn(name="odebranie_zasobow_entry_id", referencedColumnName="id", nullable=true)
     */
    protected $odebranieZasobowEntry = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="odblokowanieKonta", type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    protected $odblokowanieKonta = false;

    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=2000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;

    /**
     * @var bool
     *
     * @ORM\Column(name="renaming", type="boolean", nullable=true)
     */
    protected $renaming = false;

    /**
     * Dodatkowa linia podpisu w stopce maili
     *
     * @var string
     *
     * @ORM\Column(name="extensionAttribute10", type="string", length=255, nullable=true)
     */
    private $extensionAttribute10;

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
    public function setAccountExpires($accountExpires = null)
    {
        $this->accountExpires = $accountExpires;
        if (null !== $accountExpires) {
            $this->accountExpires = $accountExpires->setTime(23, 59);
        }


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

    public function __construct($samaccountname = "undefined")
    {
        $this->createdBy = $samaccountname;
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
        $find = array('ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż');
        $repl = array('a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z');

        $samaccountname = strlen($samaccountname) > 20 ? substr($samaccountname, 0, 20) : $samaccountname;
        $samaccountname = mb_strtolower($samaccountname);
        $samaccountname = str_replace($find, $repl, $samaccountname);
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
     * Set isDisabled
     *
     * @param boolean $isDisabled
     *
     * @return Entry
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
     * Set disableDescription
     *
     * @param string $disableDescription
     *
     * @return Entry
     */
    public function setDisableDescription($disableDescription)
    {
        $this->disableDescription = $disableDescription;

        return $this;
    }

    /**
     * Get disableDescription
     *
     * @return string
     */
    public function getDisableDescription()
    {
        return $this->disableDescription;
    }

    public function getRoles()
    {
        return array();
    }
    public function setRoles()
    {
        return $this;
    }

    public function setGrupyAD($departament, $czyDodaj = "+")
    {
        if ($departament) {
            $grupy = explode(",", $departament->getGrupyAD());
            $gr = [];
            foreach ($grupy as $g) {
                if (strlen($g) > 0) {
                    $gr[] = $czyDodaj . $g;
                }
            }
            $this->setMemberOf(implode(",", $gr));
        }
    }
    public function addGrupyAD($grupy, $znak)
    {
        $grupyJuzSa = explode(",", $this->getMemberOf());
        foreach ($grupy as $g) {
            $grupyJuzSa[] = $znak . $g;
        }
        $this->setMemberOf(implode(",", $grupyJuzSa));
    }

    /**
     * Set wniosek
     *
     * @param \ParpV1\MainBundle\Entity\Wniosek $wniosek
     *
     * @return Entry
     */
    public function setWniosek(\ParpV1\MainBundle\Entity\Wniosek $wniosek = null)
    {
        $this->wniosek = $wniosek;

        return $this;
    }

    /**
     * Get wniosek
     *
     * @return \ParpV1\MainBundle\Entity\Wniosek
     */
    public function getWniosek()
    {
        return $this->wniosek;
    }

    /**
     * Set publishedBy
     *
     * @param string $publishedBy
     *
     * @return Entry
     */
    public function setPublishedBy($publishedBy)
    {
        $this->publishedBy = $publishedBy;

        return $this;
    }

    /**
     * Get publishedBy
     *
     * @return string
     */
    public function getPublishedBy()
    {
        return $this->publishedBy;
    }

    /**
     * Set publishedAt
     *
     * @param \DateTime $publishedAt
     *
     * @return Entry
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * Get publishedAt
     *
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * Set logfile
     *
     * @param string $logfile
     *
     * @return Entry
     */
    public function setLogfile($logfile)
    {
        $this->logfile = $logfile;

        return $this;
    }

    /**
     * Get logfile
     *
     * @return string
     */
    public function getLogfile()
    {
        return $this->logfile;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Entry
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
     * Set daneRekord
     *
     * @param \ParpV1\MainBundle\Entity\DaneRekord $daneRekord
     *
     * @return Entry
     */
    public function setDaneRekord(\ParpV1\MainBundle\Entity\DaneRekord $daneRekord = null)
    {
        $this->daneRekord = $daneRekord;

        return $this;
    }

    /**
     * Get daneRekord
     *
     * @return \ParpV1\MainBundle\Entity\DaneRekord
     */
    public function getDaneRekord()
    {
        return $this->daneRekord;
    }

    /**
     * Set createdBy
     *
     * @param string $createdBy
     *
     * @return Entry
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
     * Set activateDeactivated
     *
     * @param boolean $activateDeactivated
     *
     * @return Entry
     */
    public function setActivateDeactivated($activateDeactivated)
    {
        $this->activateDeactivated = $activateDeactivated;

        return $this;
    }

    /**
     * Get activateDeactivated
     *
     * @return boolean
     */
    public function getActivateDeactivated()
    {
        return $this->activateDeactivated;
    }

    public function getUstawUprawnieniaPoczatkowe()
    {
        return true;
    }
    public function setUstawUprawnieniaPoczatkowe($var)
    {
        return $this;
    }

    /**
     * Set opis
     *
     * @param string $opis
     *
     * @return Entry
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

    /**
     * Get odebranieZasobowEntry
     *
     * @return OdebranieZasobowEntry
     */
    public function getOdebranieZasobowEntry()
    {
        return $this->odebranieZasobowEntry;
    }

    /**
     * Set odebranieZasobowEntry
     *
     * @param OdebranieZasobowEntry $odebranieZasobowEntry
     *
     * @return self
     */
    public function setOdebranieZasobowEntry(OdebranieZasobowEntry $odebranieZasobowEntry)
    {
        $this->odebranieZasobowEntry = $odebranieZasobowEntry;

        return $this;
    }

    public function getOdblokowanieKonta(): bool
    {
        return $this->odblokowanieKonta;
    }

    public function setOdblokowanieKonta(bool $odblokowanieKonta = false): self
    {
        $this->odblokowanieKonta = $odblokowanieKonta;

        return $this;
    }

    /**
     * Is renaming
     *
     * @return bool|null
     */
    public function isRenaming(): ?bool
    {
        return $this->renaming;
    }

    /**
     * Set renaming
     *
     * @param bool $renaming
     *
     * @return Entry
     */
    public function setRenaming(bool $renaming): Entry
    {
        $this->renaming = $renaming;

        return $this;
    }

    /**
     * Get extensionAttribute10.
     *
     * @return string|null
     */
    public function getExtensionAttribute10(): ?string
    {
        return $this->extensionAttribute10;
    }

    /**
     * Set extensionAttribute10
     *
     * @param string|null $extensionAttribute10
     *
     * @return Entry
     */
    public function setExtensionAttribute10(?string $extensionAttribute10): Entry
    {
        $this->extensionAttribute10 = $extensionAttribute10;

        return $this;
    }
}
