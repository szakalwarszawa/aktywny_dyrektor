<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek_utworzenie_zasobu")
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\WniosekUtworzenieZasobuRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,wniosek.numer,typWnioskuZmianaInformacji,typWnioskuWycofanie,wniosek.status.nazwa,typWnioskuDoRejestru,wniosek.createdBy,wniosek.createdAt,wniosek.lockedBy,zasob.nazwa,zmienianyZasob.nazwa,wniosek.editornames", groupBy={"id"})
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class WniosekUtworzenieZasobu
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @GRID\Column(field="id", title="Id")
     */
    private $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $deletedAt;

    /**
     *
     * @ORM\OneToOne(targetEntity="Wniosek", inversedBy="wniosekNadanieOdebranieZasobow")
     * @ORM\JoinColumn(name="wniosek_id", referencedColumnName="id")
     * @GRID\Column(field="wniosek.status.nazwa", title="Status")
     * @GRID\Column(field="wniosek.createdBy", title="Utworzony przez")
     * @GRID\Column(field="wniosek.numer", title="Numer")
     * @GRID\Column(field="wniosek.createdAt", type="date", format="Y-m-d", title="Utworzono")
     * @GRID\Column(field="wniosek.lockedBy", title="Zablokowany przez")
     * @GRID\Column(field="wniosek.lockedAt", type="date", title="Zablokowano")
     * @GRID\Column(field="wniosek.editornames", title="Edytorzy")
     */
    private $wniosek;

    /**
     *
     * @ORM\OneToOne(targetEntity="Zasoby", mappedBy="wniosekUtworzenieZasobu")
     * @ORM\JoinColumn(name="zasob_id", referencedColumnName="id")
     * @GRID\Column(field="zasob.nazwa", title="Zasób")
     */
    private $zasob;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Zasoby", inversedBy="wnioskiZmieniajaceZasob")
     * @ORM\JoinColumn(name="zmienianyZasob_id", referencedColumnName="id")
     * @GRID\Column(field="zmienianyZasob.nazwa", title="Zmieniany zasób", visible=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zmienianyZasob;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zrealizowany = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $imienazwisko;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $login;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $stanowisko;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $telefon;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nrpokoju;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $departament;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $proponowanaNazwa;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @GRID\Column(field="typWnioskuDoRejestru", type="text", title="Typ", visible=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typWnioskuDoRejestru = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typWnioskuDoUruchomienia = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @GRID\Column(field="typWnioskuZmianaInformacji", type="text", title="Typ", visible=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typWnioskuZmianaInformacji = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typWnioskuZmianaWistniejacym = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @GRID\Column(field="typWnioskuWycofanie", type="text", title="Typ", visible=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typWnioskuWycofanie = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $typWnioskuWycofanieZinfrastruktury = false;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=5000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $powodZwrotu;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=5000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zmienionePola;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wniosekDomenowy = false;

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
     * @return WniosekUtworzenieZasobu
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
     * Set zrealizowany
     *
     * @param boolean $zrealizowany
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setZrealizowany($zrealizowany)
    {
        $this->zrealizowany = $zrealizowany;

        return $this;
    }

    /**
     * Get zrealizowany
     *
     * @return boolean
     */
    public function getZrealizowany()
    {
        return $this->zrealizowany;
    }

    /**
     * Set wniosek
     *
     * @param \ParpV1\MainBundle\Entity\Wniosek $wniosek
     *
     * @return WniosekUtworzenieZasobu
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
     * Set zasob
     *
     * @param Zasoby $zasob
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setZasob(Zasoby $zasob = null)
    {
        $this->zasob = $zasob;
        //$zasob->setWniosekUtworzenieZasobu($this);

        return $this;
    }

    /**
     * Get zasob
     *
     * @return Zasoby
     */
    public function getZasob()
    {
        return $this->zasob;
    }

    /**
     * Set imienazwisko
     *
     * @param string $imienazwisko
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setImienazwisko($imienazwisko)
    {
        $this->imienazwisko = $imienazwisko;

        return $this;
    }

    /**
     * Get imienazwisko
     *
     * @return string
     */
    public function getImienazwisko()
    {
        return $this->imienazwisko;
    }

    /**
     * Set login
     *
     * @param string $login
     *
     * @return WniosekUtworzenieZasobu
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
     * Set stanowisko
     *
     * @param string $stanowisko
     *
     * @return WniosekUtworzenieZasobu
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
     * Set telefon
     *
     * @param string $telefon
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setTelefon($telefon)
    {
        $this->telefon = $telefon;

        return $this;
    }

    /**
     * Get telefon
     *
     * @return string
     */
    public function getTelefon()
    {
        return $this->telefon;
    }

    /**
     * Set nrpokoju
     *
     * @param string $nrpokoju
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setNrpokoju($nrpokoju)
    {
        $this->nrpokoju = $nrpokoju;

        return $this;
    }

    /**
     * Get nrpokoju
     *
     * @return string
     */
    public function getNrpokoju()
    {
        return $this->nrpokoju;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return WniosekUtworzenieZasobu
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
     * Set departament
     *
     * @param string $departament
     *
     * @return WniosekUtworzenieZasobu
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
     * Set proponowanaNazwa
     *
     * @param string $proponowanaNazwa
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setProponowanaNazwa($proponowanaNazwa)
    {
        $this->proponowanaNazwa = $proponowanaNazwa;

        return $this;
    }

    /**
     * Get proponowanaNazwa
     *
     * @return string
     */
    public function getProponowanaNazwa()
    {
        return $this->proponowanaNazwa;
    }

    /**
     * Set typWnioskuDoRejestru
     *
     * @param boolean $typWnioskuDoRejestru
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setTypWnioskuDoRejestru($typWnioskuDoRejestru)
    {
        $this->typWnioskuDoRejestru = $typWnioskuDoRejestru;

        return $this;
    }

    /**
     * Get typWnioskuDoRejestru
     *
     * @return boolean
     */
    public function getTypWnioskuDoRejestru()
    {
        return $this->typWnioskuDoRejestru;
    }

    /**
     * Set typWnioskuDoUruchomienia
     *
     * @param boolean $typWnioskuDoUruchomienia
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setTypWnioskuDoUruchomienia($typWnioskuDoUruchomienia)
    {
        $this->typWnioskuDoUruchomienia = $typWnioskuDoUruchomienia;

        return $this;
    }

    /**
     * Get typWnioskuDoUruchomienia
     *
     * @return boolean
     */
    public function getTypWnioskuDoUruchomienia()
    {
        return $this->typWnioskuDoUruchomienia;
    }

    /**
     * Set typWnioskuZmianaInformacji
     *
     * @param boolean $typWnioskuZmianaInformacji
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setTypWnioskuZmianaInformacji($typWnioskuZmianaInformacji)
    {
        $this->typWnioskuZmianaInformacji = $typWnioskuZmianaInformacji;

        return $this;
    }

    /**
     * Get typWnioskuZmianaInformacji
     *
     * @return boolean
     */
    public function getTypWnioskuZmianaInformacji()
    {
        return $this->typWnioskuZmianaInformacji;
    }

    /**
     * Set typWnioskuZmianaWistniejacym
     *
     * @param boolean $typWnioskuZmianaWistniejacym
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setTypWnioskuZmianaWistniejacym($typWnioskuZmianaWistniejacym)
    {
        $this->typWnioskuZmianaWistniejacym = $typWnioskuZmianaWistniejacym;

        return $this;
    }

    /**
     * Get typWnioskuZmianaWistniejacym
     *
     * @return boolean
     */
    public function getTypWnioskuZmianaWistniejacym()
    {
        return $this->typWnioskuZmianaWistniejacym;
    }

    /**
     * Set typWnioskuWycofanie
     *
     * @param boolean $typWnioskuWycofanie
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setTypWnioskuWycofanie($typWnioskuWycofanie)
    {
        $this->typWnioskuWycofanie = $typWnioskuWycofanie;

        return $this;
    }

    /**
     * Get typWnioskuWycofanie
     *
     * @return boolean
     */
    public function getTypWnioskuWycofanie()
    {
        return $this->typWnioskuWycofanie;
    }

    /**
     * Set typWnioskuWycofanieZinfrastruktury
     *
     * @param boolean $typWnioskuWycofanieZinfrastruktury
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setTypWnioskuWycofanieZinfrastruktury($typWnioskuWycofanieZinfrastruktury)
    {
        $this->typWnioskuWycofanieZinfrastruktury = $typWnioskuWycofanieZinfrastruktury;

        return $this;
    }

    /**
     * Get typWnioskuWycofanieZinfrastruktury
     *
     * @return boolean
     */
    public function getTypWnioskuWycofanieZinfrastruktury()
    {
        return $this->typWnioskuWycofanieZinfrastruktury;
    }



    /**
     * Set powodZwrotu
     *
     * @param string $powodZwrotu
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setPowodZwrotu($powodZwrotu)
    {
        $this->powodZwrotu = $powodZwrotu;

        return $this;
    }

    /**
     * Get powodZwrotu
     *
     * @return string
     */
    public function getPowodZwrotu()
    {
        return $this->powodZwrotu;
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        if (!$this->getId()) {
            $this->setWniosek(new Wniosek());
            $this->setZasob(new Zasoby());
        }
        $this->getWniosek()->setWniosekUtworzenieZasobu($this);
        $this->getZasob()->setWniosekUtworzenieZasobu($this);
    }

    /**
     * @return string
     */
    public function getTyp()
    {
        if ($this->getTypWnioskuDoRejestru()) {
            return "nowy";
        }
        if ($this->getTypWnioskuZmianaInformacji()) {
            return "zmiana";
        }
        if ($this->getTypWnioskuWycofanie()) {
            return "kasowanie";
        }
        return "";
    }

    /**
     * Set zmienionePola
     *
     * @param string $zmienionePola
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setZmienionePola($zmienionePola)
    {
        $this->zmienionePola = $zmienionePola;

        return $this;
    }

    /**
     * Get zmienionePola
     *
     * @return string
     */
    public function getZmienionePola()
    {
        return $this->zmienionePola;
    }

    /**
     * Set zmienianyZasob
     *
     * @param Zasoby $zmienianyZasob
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setZmienianyZasob(Zasoby $zmienianyZasob = null)
    {
        $this->zmienianyZasob = $zmienianyZasob;

        return $this;
    }

    /**
     * Get zmienianyZasob
     *
     * @return Zasoby
     */
    public function getZmienianyZasob()
    {
        return $this->zmienianyZasob;
    }

    /**
     * Set wniosekDomenowy
     *
     * @param boolean $wniosekDomenowy
     *
     * @return WniosekUtworzenieZasobu
     */
    public function setWniosekDomenowy($wniosekDomenowy)
    {
        $this->wniosekDomenowy = $wniosekDomenowy;

        return $this;
    }

    /**
     * Get wniosekDomenowy
     *
     * @return boolean
     */
    public function getWniosekDomenowy()
    {
        return $this->wniosekDomenowy;
    }
}
