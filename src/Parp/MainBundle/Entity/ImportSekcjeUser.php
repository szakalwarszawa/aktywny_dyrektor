<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Annotations\UniqueConstraint;
/**
 * DaneRekord
 *
 * @ORM\Table(name="import_sekcje_user")
 * a@Gedmo\Loggable
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\ImportSekcjeUserRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, symbolRekordId, login, imie, nazwisko, departament, stanowisko, umowa, umowaOd, umowaDo")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class ImportSekcjeUser
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
     * @ORM\Column(name="login", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="login", title="Login")
     */
    private $login;
    
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="pracownik", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="pracownik", title="Pracownik")
     */
    private $pracownik;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="sekcja", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="sekcja", title="Sekcja")
     */
    private $sekcja;
    
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="sekcjaSkrot", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="sekcjaSkrot", title="Sekcja skrót")
     */
    private $sekcjaSkrot;
    
    
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
     * @ORM\Column(name="departamentSkrot", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="departamentSkrot", title="Departament skrót")
     */
    private $departamentSkrot;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="stanowisko", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(field="stanowisko", title="Stanowisko")
     */
    private $stanowisko;

    
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
     * @ORM\Column(type="text", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typPracownika;
    
    
    
    /**
     * @var string
     *
     * @ORM\Column(type="text", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataZakonczenia;

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
     * @return ImportSekcjeUser
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
     * Set login
     *
     * @param string $login
     *
     * @return ImportSekcjeUser
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
     * Set pracownik
     *
     * @param string $pracownik
     *
     * @return ImportSekcjeUser
     */
    public function setPracownik($pracownik)
    {
        $this->pracownik = $pracownik;

        return $this;
    }

    /**
     * Get pracownik
     *
     * @return string
     */
    public function getPracownik()
    {
        return $this->pracownik;
    }

    /**
     * Set sekcja
     *
     * @param string $sekcja
     *
     * @return ImportSekcjeUser
     */
    public function setSekcja($sekcja)
    {
        $this->sekcja = $sekcja;

        return $this;
    }

    /**
     * Get sekcja
     *
     * @return string
     */
    public function getSekcja()
    {
        return $this->sekcja;
    }

    /**
     * Set sekcjaSkrot
     *
     * @param string $sekcjaSkrot
     *
     * @return ImportSekcjeUser
     */
    public function setSekcjaSkrot($sekcjaSkrot)
    {
        $this->sekcjaSkrot = $sekcjaSkrot;

        return $this;
    }

    /**
     * Get sekcjaSkrot
     *
     * @return string
     */
    public function getSekcjaSkrot()
    {
        return $this->sekcjaSkrot;
    }

    /**
     * Set departament
     *
     * @param string $departament
     *
     * @return ImportSekcjeUser
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
     * Set departamentSkrot
     *
     * @param string $departamentSkrot
     *
     * @return ImportSekcjeUser
     */
    public function setDepartamentSkrot($departamentSkrot)
    {
        $this->departamentSkrot = $departamentSkrot;

        return $this;
    }

    /**
     * Get departamentSkrot
     *
     * @return string
     */
    public function getDepartamentSkrot()
    {
        return $this->departamentSkrot;
    }
    
    public function __construct(){
        
        $d = new \Datetime();
        if(!$this->getId())
            $this->setCreatedAt($d);
        
        $this->setLastModifiedAt($d);
    }

    /**
     * Set stanowisko
     *
     * @param string $stanowisko
     *
     * @return ImportSekcjeUser
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return ImportSekcjeUser
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
     * Set lastModifiedAt
     *
     * @param \DateTime $lastModifiedAt
     *
     * @return ImportSekcjeUser
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
     * Set typPracownika
     *
     * @param string $typPracownika
     *
     * @return ImportSekcjeUser
     */
    public function setTypPracownika($typPracownika)
    {
        $this->typPracownika = $typPracownika;

        return $this;
    }

    /**
     * Get typPracownika
     *
     * @return string
     */
    public function getTypPracownika()
    {
        return $this->typPracownika;
    }

    /**
     * Set dataZakonczenia
     *
     * @param string $dataZakonczenia
     *
     * @return ImportSekcjeUser
     */
    public function setDataZakonczenia($dataZakonczenia)
    {
        $this->dataZakonczenia = $dataZakonczenia;

        return $this;
    }

    /**
     * Get dataZakonczenia
     *
     * @return string
     */
    public function getDataZakonczenia()
    {
        return $this->dataZakonczenia;
    }
}
