<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use APY\DataGridBundle\Grid\Mapping as GRID;

/*,userZasoby.zasobOpis:group_concat:distinct*/
/**
 * UserZasoby
 *
 * @ORM\Table(name="wniosek_nadanie_odebranie_zasobow")
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobowRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,wniosek.numer,wniosek.status.nazwa,odebranie,wniosek.createdBy,wniosek.createdAt,wniosek.lockedBy,pracownicy,wniosek.editornames,zasoby", groupBy={"id", "wniosek.numer", "wniosek.status.nazwa", "wniosek.createdBy", "wniosek.createdAt", "wniosek.lockedBy", "wniosek.editornames"})
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class WniosekNadanieOdebranieZasobow
{
    /**
     * @var string
     */
    const WNIOSKI_WSZYSTKIE = 'wszystkie';

    /**
     * @var string
     */
    const WNIOSKI_W_TOKU = 'wtoku';

    /**
     * @var string
     */
    const WNIOSKI_OCZEKUJACE = 'oczekujace';

    /**
     * @var string
     */
    const WNIOSKI_ZAKONCZONE = 'zakonczone';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @GRID\Column(field="id", title="Id", visible=false)
     */
    private $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $deletedAt;


    /**
     * @var Wniosek
     * @ORM\OneToOne(targetEntity="Wniosek", inversedBy="wniosekNadanieOdebranieZasobow")
     * @ORM\JoinColumn(name="wniosek_id", referencedColumnName="id")
     * @GRID\Column(field="wniosek.numer", title="Numer")
     * @GRID\Column(field="wniosek.status.nazwa", title="Status")
     * @GRID\Column(field="wniosek.createdBy", title="Utworzony przez")
     * @GRID\Column(field="wniosek.createdAt", type="date", format="Y-m-d", title="Utworzono")
     * @GRID\Column(field="wniosek.lockedBy", title="Zablokowany przez", visible=true)
     * @GRID\Column(field="wniosek.editornames", title="Edytorzy")
     */
    private $wniosek;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $pracownikSpozaParp = false;


    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $pracownicy;


    /**
     * @var UserZasoby[]
     *
     * @ORM\OneToMany(targetEntity="UserZasoby", mappedBy="wniosek")
     * @@Gedmo\Mapping\Annotation\Versioned
     * @@GRID\Column(field="userZasoby.zasobOpis:group_concat:distinct", type="text", title="Zasoby")
     */
    private $userZasoby;


    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="UserZasoby", mappedBy="wniosekOdebranie")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $userZasobyOdbierane;


    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zasobId;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @GRID\Column(visible=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $odebranie = false;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $dataOdebrania;



    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $managerSpozaParp;



    /**
     * @var string
     *
     * @ORM\Column(type="text", length=5000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $instytucjaSpozaParp;

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
     * @ORM\Column(type="string", length=5000, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zasoby;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true, name="zawiera_zasoby_z_ad")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zawieraZasobyZAd = false;

    /**
     * @var Departament
     *
     * @ORM\ManyToOne(targetEntity="ParpV1\MainBundle\Entity\Departament", inversedBy="wniosekSpozaParp")
     */
    private $odpowiedzialnyDepartament;

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
     * @return WniosekNadanieOdebranieZasobow
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
     * Set pracownikSpozaParp
     *
     * @param boolean $pracownikSpozaParp
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setPracownikSpozaParp($pracownikSpozaParp)
    {
        $this->pracownikSpozaParp = $pracownikSpozaParp;

        return $this;
    }

    /**
     * Get pracownikSpozaParp
     *
     * @return boolean
     */
    public function getPracownikSpozaParp()
    {
        return $this->pracownikSpozaParp;
    }

    /**
     * Set pracownicy
     *
     * @param string $pracownicy
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setPracownicy($pracownicy)
    {
        $this->pracownicy = $pracownicy;

        return $this;
    }

    /**
     * Get pracownicy
     *
     * @return string
     */
    public function getPracownicy()
    {
        return $this->pracownicy;
    }

    /**
     * Set pracownicy
     *
     * @param string $pracownicy
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setPracownicySpozaParp($pracownicy)
    {
        if ($this->getPracownikSpozaParp()) {
            $this->pracownicy = $pracownicy;
        }

        return $this;
    }

    /**
     * Get pracownicy
     *
     * @return string
     */
    public function getPracownicySpozaParp()
    {
        return $this->getPracownikSpozaParp() ? $this->pracownicy : "";
    }

    /**
     * Add userZasoby
     *
     * @param \ParpV1\MainBundle\Entity\UserZasoby $userZasoby
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function addUserZasoby(\ParpV1\MainBundle\Entity\UserZasoby $userZasoby)
    {
        $this->userZasoby[] = $userZasoby;
        $this->ustawPoleZasoby();
        return $this;
    }

    /**
     * Remove userZasoby
     *
     * @param \ParpV1\MainBundle\Entity\UserZasoby $userZasoby
     */
    public function removeUserZasoby(\ParpV1\MainBundle\Entity\UserZasoby $userZasoby)
    {
        $this->userZasoby->removeElement($userZasoby);
        $this->ustawPoleZasoby();
    }

    /**
     * Get userZasoby[]
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserZasoby()
    {
        if ($this->getOdebranie()) {
            return $this->userZasobyOdbierane;
        } else {
            return $this->userZasoby;
        }
    }

    public function getJsonSams()
    {
        $ar = explode(",", $this->getPracownicy());
        $ars = array();
        foreach ($ar as $a) {
            $ars[$a] = 1;
        }
        return json_encode($ars);
    }


    /**
     * Set zasobId
     *
     * @param integer $zasobId
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setZasobId($zasobId)
    {
        $this->zasobId = $zasobId;

        return $this;
    }

    /**
     * Get zasobId
     *
     * @return integer
     */
    public function getZasobId()
    {
        return $this->zasobId;
    }


    /**
     * Set wniosek
     *
     * @param Wniosek $wniosek
     *
     * @return Wniosek
     */
    public function setWniosek(Wniosek $wniosek = null)
    {
        $this->wniosek = $wniosek;
        $wniosek->setWniosekNadanieOdebranieZasobow($this);
        return $this;
    }

    /**
     * Get wniosek
     *
     * @return Wniosek
     */
    public function getWniosek()
    {
        return $this->wniosek;
    }


    /**
     * Set odebranie
     *
     * @param boolean $odebranie
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setOdebranie($odebranie)
    {
        $this->odebranie = $odebranie;

        return $this;
    }

    /**
     * Get odebranie
     *
     * @return boolean
     */
    public function getOdebranie()
    {
        return $this->odebranie;
    }

    /**
     * Set managerSpozaParp
     *
     * @param string $managerSpozaParp
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setManagerSpozaParp($managerSpozaParp)
    {
        $this->managerSpozaParp = $managerSpozaParp;

        return $this;
    }

    /**
     * Get managerSpozaParp
     *
     * @return string
     */
    public function getManagerSpozaParp()
    {
        return $this->managerSpozaParp;
    }

    /**
     * Set instytucjaSpozaParp
     *
     * @param string $instytucjaSpozaParp
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setInstytucjaSpozaParp($instytucjaSpozaParp)
    {
        $this->instytucjaSpozaParp = $instytucjaSpozaParp;

        return $this;
    }

    /**
     * Get instytucjaSpozaParp
     *
     * @return string
     */
    public function getInstytucjaSpozaParp()
    {
        return $this->instytucjaSpozaParp;
    }

    /**
     * Set powodZwrotu
     *
     * @param string $powodZwrotu
     *
     * @return WniosekNadanieOdebranieZasobow
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
        $this->userZasoby = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userZasobyOdbierane = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setWniosek(new Wniosek());
    }


    /**
     * Add userZasobyOdbierane
     *
     * @param \ParpV1\MainBundle\Entity\UserZasoby $userZasobyOdbierane
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function addUserZasobyOdbierane(\ParpV1\MainBundle\Entity\UserZasoby $userZasobyOdbierane)
    {
        $this->userZasobyOdbierane[] = $userZasobyOdbierane;
        $this->ustawPoleZasoby();

        return $this;
    }

    /**
     * Remove userZasobyOdbierane
     *
     * @param \ParpV1\MainBundle\Entity\UserZasoby $userZasobyOdbierane
     */
    public function removeUserZasobyOdbierane(\ParpV1\MainBundle\Entity\UserZasoby $userZasobyOdbierane)
    {
        $this->userZasobyOdbierane->removeElement($userZasobyOdbierane);
        $this->ustawPoleZasoby();
    }

    /**
     * Get userZasobyOdbierane
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserZasobyOdbierane()
    {
        return $this->userZasobyOdbierane;
    }

    /**
     * Set zasoby
     *
     * @param string $zasoby
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setZasoby($zasoby)
    {
        $this->zasoby = $zasoby;

        return $this;
    }

    /**
     * Get zasoby
     *
     * @return string
     */
    public function getZasoby()
    {
        return $this->zasoby;
    }

    public function ustawPoleZasoby()
    {
        global $kernel;
        if ('AppCache' == get_class($kernel)) {
            $kernel = $kernel->getKernel();
        }
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $ret = [];
        foreach ($this->getUserZasoby() as $uz) {
            $z = $em->getRepository("ParpMainBundle:Zasoby")->find($uz->getZasobId());
            $ret[$z->getNazwa()] = $z->getNazwa();
        }
        foreach ($this->getUserZasobyOdbierane() as $uz) {
            $z = $em->getRepository("ParpMainBundle:Zasoby")->find($uz->getZasobId());
            $ret[$z->getNazwa()] = $z->getNazwa();
        }
        $zass = implode(", ", $ret);
        $this->setZasoby($zass);
        //echo("Ustawilem pole zasoby ".$zass.$this->getId());
    }

    /**
     * Set dataOdebrania
     *
     * @param \DateTime $dataOdebrania
     *
     * @return WniosekNadanieOdebranieZasobow
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
     * Get zawieraZasobyZAd
     *
     * @return bool
     */
    public function getZawieraZasobyZAd()
    {
        return $this->zawieraZasobyZAd;
    }

    /**
     * Set zawieraZasobyZAd
     *
     * @param bool $zawieraZasobyZAd
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setZawieraZasobyZAd(bool $zawieraZasobyZAd): self
    {
        $this->zawieraZasobyZAd = $zawieraZasobyZAd;

        return $this;
    }

    /**
     * Get odpowiedzialnyDepartament
     *
     * @return Departament|null
     */
    public function getOdpowiedzialnyDepartament(): ?Departament
    {
        return $this->odpowiedzialnyDepartament;
    }

    /**
     * Set odpowiedzialnyDepartament
     *
     * @param Departament|null $odpowiedzialnyDepartament
     *
     * @return WniosekNadanieOdebranieZasobow
     */
    public function setOdpowiedzialnyDepartament(?Departament $odpowiedzialnyDepartament): WniosekNadanieOdebranieZasobow
    {
        $this->odpowiedzialnyDepartament = $odpowiedzialnyDepartament;

        return $this;
    }
}
