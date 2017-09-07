<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Annotations\UniqueConstraint;

/**
 * DaneRekord
 *
 * @ORM\Table(name="dane_rekord", uniqueConstraints={@ORM\UniqueConstraint(name="imie_naziwsko", columns={"imie", "nazwisko", "symbolRekordId"})})
 * a@Gedmo\Loggable
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\DaneRekordRepository")
 * @ORM\HasLifecycleCallbacks
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, symbolRekordId, login, imie, nazwisko, departament, stanowisko, umowa, umowaOd, umowaDo")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class DaneRekord
{

    /**
     *
     */
    public function preUpdate()
    {
        $d = new \Datetime();
        if (!$this->getId()) {
            $this->setCreatedAt($d);
        }

        $this->setLastModifiedAt($d);
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
     * @var string
     *
     * @ORM\Column(name="symbolRekordId", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="symbolRekordId", title="Id w rekord")
     */
    private $symbolRekordId;



    /**
     * @var string
     *
     * @ORM\Column(name="login", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="login", title="Login")
     */
    private $login;

    /**
     * @var string
     *
     * @ORM\Column(name="imie", type="string", length=255, nullable=false)
     * @Assert\NotBlank(message = "Imię nie może być puste.")
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="imie", title="Imię")
     */
    private $imie;

    /**
     * @var string
     *
     * @ORM\Column(name="nazwisko", type="string", length=255, nullable=false)
     * @Assert\NotBlank(message = "Imię nie może być puste.")
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="nazwisko", title="Nazwisko")
     */
    private $nazwisko;

    /**
     * @var string
     *
     * @ORM\Column(name="departament", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="departament", title="Departament")
     */
    private $departament;

    /**
     * @var string
     *
     * @ORM\Column(name="stanowisko", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="stanowisko", title="Stanowisko")
     */
    private $stanowisko;

    /**
     * @var string
     *
     * @ORM\Column(name="umowa", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="umowa", title="Umowa")
     */
    private $umowa;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
     * @APY\DataGridBundle\Grid\Mapping\Column(field="umowaOd", title="Umowa od")
    */
    private $umowaOd;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
     * @APY\DataGridBundle\Grid\Mapping\Column(field="umowaDo", title="Umowa do")
    */
    private $umowaDo;


    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
    */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
    */
    private $lastModifiedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="createdBy", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $createdBy;

     /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="Entry", mappedBy="daneRekord")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $entries;


    /**
    * @var boolean
     *
     * @ORM\Column(type="integer")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $newUnproccessed = 0;


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
     * @return DaneRekord
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
     * Set imie
     *
     * @param string $imie
     *
     * @return DaneRekord
     */
    public function setImie($imie)
    {
        $this->imie = $imie;

        return $this;
    }

    /**
     * Get imie
     *
     * @return string
     */
    public function getImie()
    {
        return $this->imie;
    }

    /**
     * Set nazwisko
     *
     * @param string $nazwisko
     *
     * @return DaneRekord
     */
    public function setNazwisko($nazwisko)
    {
        $this->nazwisko = $nazwisko;

        return $this;
    }

    /**
     * Get nazwisko
     *
     * @return string
     */
    public function getNazwisko()
    {
        return $this->nazwisko;
    }

    /**
     * Set departament
     *
     * @param string $departament
     *
     * @return DaneRekord
     */
    public function setDepartament($departament)
    {
        $this->departament = $departament;

        return $this;
    }

    /**
     * Get departament
     *
     * @return string
     */
    public function getDepartament()
    {
        return $this->departament;
    }

    /**
     * Set stanowisko
     *
     * @param string $stanowisko
     *
     * @return DaneRekord
     */
    public function setStanowisko($stanowisko)
    {
        $this->stanowisko = $stanowisko;

        return $this;
    }

    /**
     * Get stanowisko
     *
     * @return string
     */
    public function getStanowisko()
    {
        return $this->stanowisko;
    }

    /**
     * Set umowa
     *
     * @param string $umowa
     *
     * @return DaneRekord
     */
    public function setUmowa($umowa)
    {
        $this->umowa = $umowa;

        return $this;
    }

    /**
     * Get umowa
     *
     * @return string
     */
    public function getUmowa()
    {
        return $this->umowa;
    }

    /**
     * Set umowaOd
     *
     * @param \DateTime $umowaOd
     *
     * @return DaneRekord
     */
    public function setUmowaOd($umowaOd)
    {
        $this->umowaOd = $umowaOd;

        return $this;
    }

    /**
     * Get umowaOd
     *
     * @return \DateTime
     */
    public function getUmowaOd()
    {
        return $this->umowaOd;
    }

    /**
     * Set umowaDo
     *
     * @param \DateTime $umowaDo
     *
     * @return DaneRekord
     */
    public function setUmowaDo($umowaDo)
    {
        $this->umowaDo = $umowaDo;

        return $this;
    }

    /**
     * Get umowaDo
     *
     * @return \DateTime
     */
    public function getUmowaDo()
    {
        return $this->umowaDo;
    }

    /**
     * Set symbolRekordId
     *
     * @param string $symbolRekordId
     *
     * @return DaneRekord
     */
    public function setSymbolRekordId($symbolRekordId)
    {
        $this->symbolRekordId = $symbolRekordId;

        return $this;
    }

    /**
     * Get symbolRekordId
     *
     * @return string
     */
    public function getSymbolRekordId()
    {
        return $this->symbolRekordId;
    }

    /**
     * Set login
     *
     * @param string $login
     *
     * @return DaneRekord
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return DaneRekord
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
     * Set createdBy
     *
     * @param string $createdBy
     *
     * @return DaneRekord
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
     * Set lastModifiedAt
     *
     * @param \DateTime $lastModifiedAt
     *
     * @return DaneRekord
     */
    public function setLastModifiedAt($lastModifiedAt)
    {
        $this->lastModifiedAt = $lastModifiedAt;

        return $this;
    }

    /**
     * Get lastModifiedAt
     *
     * @return \DateTime
     */
    public function getLastModifiedAt()
    {
        return $this->lastModifiedAt;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->entries = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add entry
     *
     * @param \ParpV1\MainBundle\Entity\Entry $entry
     *
     * @return DaneRekord
     */
    public function addEntry(\ParpV1\MainBundle\Entity\Entry $entry)
    {
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * Remove entry
     *
     * @param \ParpV1\MainBundle\Entity\Entry $entry
     */
    public function removeEntry(\ParpV1\MainBundle\Entity\Entry $entry)
    {
        $this->entries->removeElement($entry);
    }

    /**
     * Get entries
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Set newUnproccessed
     *
     * @param boolean $newUnproccessed
     *
     * @return DaneRekord
     */
    public function setNewUnproccessed($newUnproccessed)
    {
        $this->newUnproccessed = $newUnproccessed;

        return $this;
    }

    /**
     * Get newUnproccessed
     *
     * @return boolean
     */
    public function getNewUnproccessed()
    {
        return $this->newUnproccessed;
    }

    public function getImieNazwisko()
    {
        return $this->getImie()." ".$this->getNazwisko();
    }
}
