<?php

namespace Parp\MainBundle\Entity;
use APY\DataGridBundle\Grid\Mapping as GRID;
use Doctrine\ORM\Mapping as ORM;

/**
 * Zasoby
 *
 * @ORM\Table(name="zasoby")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\ZasobyRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, nazwa, opis,wlascicielZasobu,administratorZasobu,administratorTechnicznyZasobu,wniosekUtworzenieZasobu.wniosek.numer,wniosekUtworzenieZasobu.id")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Zasoby
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
     * @ORM\Column(name="nazwa", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nazwa;

    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="text", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;

    /**
     * @var string
     *
     * @ORM\Column(name="biuro", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $biuro;
    
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wlascicielZasobu;
    
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $powiernicyWlascicielaZasobu;
    
    
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $administratorZasobu;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $administratorTechnicznyZasobu;
    
    
    
    
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wlascicielZasobuEcm;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $administratorZasobuEcm;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $administratorTechnicznyZasobuEcm;
    
    
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wlascicielZasobuZgubieni;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $administratorZasobuZgubieni;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $administratorTechnicznyZasobuZgubieni;
    
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $uzytkownicy;
    /**
    * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $daneOsobowe;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $komorkaOrgazniacyjna;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $miejsceInstalacji;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opisZasobu;
    /**
    * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $modulFunkcja;
    /**
    * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $poziomDostepu;
    /**
    * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $grupyAD;
    /**
    * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataZakonczeniaWdrozenia;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wykonawca;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nazwaWykonawcy;
    /**
    * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $asystaTechniczna;
    /**
    * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataWygasnieciaAsystyTechnicznej;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dokumentacjaFormalna;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dokumentacjaProjektowoTechniczna;
    /**
    * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $technologia;
    /**
    * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $testyBezpieczenstwa;
    /**
    * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $testyWydajnosciowe;
    /**
    * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataZleceniaOstatniegoPrzegladuUprawnien;
    /**
    * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $interwalPrzegladuUprawnien;
    /**
    * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataZleceniaOstatniegoPrzegladuAktywnosci;
    /**
    * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $interwalPrzegladuAktywnosci;
    /**
    * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych;
    /**
    * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $interwalZmianyHaselKontaAdministracyjnychISerwisowych;



    /**
     *
     * @ORM\OneToOne(targetEntity="WniosekUtworzenieZasobu", inversedBy="zasob")
     * @ORM\JoinColumn(name="wniosekUtworzenieZasobu_id", referencedColumnName="id")
     * @GRID\Column(field="wniosekUtworzenieZasobu.wniosek.numer", title="Numer")
     * @GRID\Column(field="wniosekUtworzenieZasobu.id", visible=false)
     */
    private $wniosekUtworzenieZasobu;
    
    
    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="WniosekUtworzenieZasobu", mappedBy="zmienianyZasob")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $wnioskiZmieniajaceZasob;
    
    


    /**
     *
     * @ORM\ManyToOne(targetEntity="WniosekUtworzenieZasobu", inversedBy="zasobyDoSkasowania")
     * @ORM\JoinColumn(name="WniosekUtworzenieZasobuDoSkasowania_id", referencedColumnName="id")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $wniosekSkasowanieZasobu;


    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $dataUtworzeniaZasobu;
    
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $dataZmianyZasobu;
    
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $dataUsunieciaZasobu;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="published", type="boolean")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $published = false;

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
     * Set nazwa
     *
     * @param string $nazwa
     * @return Zasoby
     */
    public function setNazwa($nazwa)
    {
        $this->nazwa = $nazwa;

        return $this;
    }

    /**
     * Get nazwa
     *
     * @return string 
     */
    public function getNazwa()
    {
        return $this->nazwa;
    }

    /**
     * Set opis
     *
     * @param string $opis
     * @return Zasoby
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
     * Set biuro
     *
     * @param string $biuro
     * @return Zasoby
     */
    public function setBiuro($biuro)
    {
        $this->biuro = $biuro;

        return $this;
    }

    /**
     * Get biuro
     *
     * @return string 
     */
    public function getBiuro()
    {
        return $this->biuro;
    }

    /**
     * Set wlascicielZasobu
     *
     * @param string $wlascicielZasobu
     * @return Zasoby
     */
    public function setWlascicielZasobu($wlascicielZasobu)
    {
        $this->wlascicielZasobu = $wlascicielZasobu;

        return $this;
    }

    /**
     * Get wlascicielZasobu
     *
     * @return string 
     */
    public function getWlascicielZasobu()
    {
        return $this->wlascicielZasobu;
    }

    /**
     * Set administratorZasobu
     *
     * @param string $administratorZasobu
     * @return Zasoby
     */
    public function setAdministratorZasobu($administratorZasobu)
    {
        $this->administratorZasobu = $administratorZasobu;

        return $this;
    }

    /**
     * Get administratorZasobu
     *
     * @return string 
     */
    public function getAdministratorZasobu()
    {
        return $this->administratorZasobu;
    }

    /**
     * Set administratorTechnicznyZasobu
     *
     * @param string $administratorTechnicznyZasobu
     * @return Zasoby
     */
    public function setAdministratorTechnicznyZasobu($administratorTechnicznyZasobu)
    {
        $this->administratorTechnicznyZasobu = $administratorTechnicznyZasobu;

        return $this;
    }

    /**
     * Get administratorTechnicznyZasobu
     *
     * @return string 
     */
    public function getAdministratorTechnicznyZasobu()
    {
        return $this->administratorTechnicznyZasobu;
    }

    /**
     * Set uzytkownicy
     *
     * @param string $uzytkownicy
     * @return Zasoby
     */
    public function setUzytkownicy($uzytkownicy)
    {
        $this->uzytkownicy = $uzytkownicy;

        return $this;
    }

    /**
     * Get uzytkownicy
     *
     * @return string 
     */
    public function getUzytkownicy()
    {
        return $this->uzytkownicy;
    }

    /**
     * Set daneOsobowe
     *
     * @param boolean $daneOsobowe
     * @return Zasoby
     */
    public function setDaneOsobowe($daneOsobowe)
    {
        $this->daneOsobowe = $daneOsobowe;

        return $this;
    }

    /**
     * Get daneOsobowe
     *
     * @return boolean 
     */
    public function getDaneOsobowe()
    {
        return $this->daneOsobowe;
    }

    /**
     * Set komorkaOrgazniacyjna
     *
     * @param string $komorkaOrgazniacyjna
     * @return Zasoby
     */
    public function setKomorkaOrgazniacyjna($komorkaOrgazniacyjna)
    {
        $this->komorkaOrgazniacyjna = $komorkaOrgazniacyjna;

        return $this;
    }

    /**
     * Get komorkaOrgazniacyjna
     *
     * @return string 
     */
    public function getKomorkaOrgazniacyjna()
    {
        return $this->komorkaOrgazniacyjna;
    }

    /**
     * Set miejsceInstalacji
     *
     * @param string $miejsceInstalacji
     * @return Zasoby
     */
    public function setMiejsceInstalacji($miejsceInstalacji)
    {
        $this->miejsceInstalacji = $miejsceInstalacji;

        return $this;
    }

    /**
     * Get miejsceInstalacji
     *
     * @return string 
     */
    public function getMiejsceInstalacji()
    {
        return $this->miejsceInstalacji;
    }

    /**
     * Set opisZasobu
     *
     * @param string $opisZasobu
     * @return Zasoby
     */
    public function setOpisZasobu($opisZasobu)
    {
        $this->opisZasobu = $opisZasobu;

        return $this;
    }

    /**
     * Get opisZasobu
     *
     * @return string 
     */
    public function getOpisZasobu()
    {
        return $this->opisZasobu;
    }

    /**
     * Set modulFunkcja
     *
     * @param string $modulFunkcja
     * @return Zasoby
     */
    public function setModulFunkcja($modulFunkcja)
    {
        $this->modulFunkcja = $modulFunkcja;

        return $this;
    }

    /**
     * Get modulFunkcja
     *
     * @return string 
     */
    public function getModulFunkcja()
    {
        return $this->modulFunkcja;
    }

    /**
     * Set poziomDostepu
     *
     * @param string $poziomDostepu
     * @return Zasoby
     */
    public function setPoziomDostepu($poziomDostepu)
    {
        $this->poziomDostepu = $poziomDostepu;

        return $this;
    }

    /**
     * Get poziomDostepu
     *
     * @return string 
     */
    public function getPoziomDostepu()
    {
        return $this->poziomDostepu;
    }

    /**
     * Set dataZakonczeniaWdrozenia
     *
     * @param \DateTime $dataZakonczeniaWdrozenia
     * @return Zasoby
     */
    public function setDataZakonczeniaWdrozenia($dataZakonczeniaWdrozenia)
    {
        $this->dataZakonczeniaWdrozenia = $dataZakonczeniaWdrozenia;

        return $this;
    }

    /**
     * Get dataZakonczeniaWdrozenia
     *
     * @return \DateTime 
     */
    public function getDataZakonczeniaWdrozenia()
    {
        return $this->dataZakonczeniaWdrozenia;
    }

    /**
     * Set wykonawca
     *
     * @param string $wykonawca
     * @return Zasoby
     */
    public function setWykonawca($wykonawca)
    {
        $this->wykonawca = $wykonawca;

        return $this;
    }

    /**
     * Get wykonawca
     *
     * @return string 
     */
    public function getWykonawca()
    {
        return $this->wykonawca;
    }

    /**
     * Set nazwaWykonawcy
     *
     * @param string $nazwaWykonawcy
     * @return Zasoby
     */
    public function setNazwaWykonawcy($nazwaWykonawcy)
    {
        $this->nazwaWykonawcy = $nazwaWykonawcy;

        return $this;
    }

    /**
     * Get nazwaWykonawcy
     *
     * @return string 
     */
    public function getNazwaWykonawcy()
    {
        return $this->nazwaWykonawcy;
    }

    /**
     * Set asystaTechniczna
     *
     * @param boolean $asystaTechniczna
     * @return Zasoby
     */
    public function setAsystaTechniczna($asystaTechniczna)
    {
        $this->asystaTechniczna = $asystaTechniczna;

        return $this;
    }

    /**
     * Get asystaTechniczna
     *
     * @return boolean 
     */
    public function getAsystaTechniczna()
    {
        return $this->asystaTechniczna;
    }

    /**
     * Set dataWygasnieciaAsystyTechnicznej
     *
     * @param \DateTime $dataWygasnieciaAsystyTechnicznej
     * @return Zasoby
     */
    public function setDataWygasnieciaAsystyTechnicznej($dataWygasnieciaAsystyTechnicznej)
    {
        $this->dataWygasnieciaAsystyTechnicznej = $dataWygasnieciaAsystyTechnicznej;

        return $this;
    }

    /**
     * Get dataWygasnieciaAsystyTechnicznej
     *
     * @return \DateTime 
     */
    public function getDataWygasnieciaAsystyTechnicznej()
    {
        return $this->dataWygasnieciaAsystyTechnicznej;
    }

    /**
     * Set dokumentacjaFormalna
     *
     * @param string $dokumentacjaFormalna
     * @return Zasoby
     */
    public function setDokumentacjaFormalna($dokumentacjaFormalna)
    {
        $this->dokumentacjaFormalna = $dokumentacjaFormalna;

        return $this;
    }

    /**
     * Get dokumentacjaFormalna
     *
     * @return string 
     */
    public function getDokumentacjaFormalna()
    {
        return $this->dokumentacjaFormalna;
    }

    /**
     * Set dokumentacjaProjektowoTechniczna
     *
     * @param string $dokumentacjaProjektowoTechniczna
     * @return Zasoby
     */
    public function setDokumentacjaProjektowoTechniczna($dokumentacjaProjektowoTechniczna)
    {
        $this->dokumentacjaProjektowoTechniczna = $dokumentacjaProjektowoTechniczna;

        return $this;
    }

    /**
     * Get dokumentacjaProjektowoTechniczna
     *
     * @return string 
     */
    public function getDokumentacjaProjektowoTechniczna()
    {
        return $this->dokumentacjaProjektowoTechniczna;
    }

    /**
     * Set technologia
     *
     * @param string $technologia
     * @return Zasoby
     */
    public function setTechnologia($technologia)
    {
        $this->technologia = $technologia;

        return $this;
    }

    /**
     * Get technologia
     *
     * @return string 
     */
    public function getTechnologia()
    {
        return $this->technologia;
    }

    /**
     * Set testyBezpieczenstwa
     *
     * @param boolean $testyBezpieczenstwa
     * @return Zasoby
     */
    public function setTestyBezpieczenstwa($testyBezpieczenstwa)
    {
        $this->testyBezpieczenstwa = $testyBezpieczenstwa;

        return $this;
    }

    /**
     * Get testyBezpieczenstwa
     *
     * @return boolean 
     */
    public function getTestyBezpieczenstwa()
    {
        return $this->testyBezpieczenstwa;
    }

    /**
     * Set testyWydajnosciowe
     *
     * @param boolean $testyWydajnosciowe
     * @return Zasoby
     */
    public function setTestyWydajnosciowe($testyWydajnosciowe)
    {
        $this->testyWydajnosciowe = $testyWydajnosciowe;

        return $this;
    }

    /**
     * Get testyWydajnosciowe
     *
     * @return boolean 
     */
    public function getTestyWydajnosciowe()
    {
        return $this->testyWydajnosciowe;
    }

    /**
     * Set dataZleceniaOstatniegoPrzegladuUprawnien
     *
     * @param \DateTime $dataZleceniaOstatniegoPrzegladuUprawnien
     * @return Zasoby
     */
    public function setDataZleceniaOstatniegoPrzegladuUprawnien($dataZleceniaOstatniegoPrzegladuUprawnien)
    {
        $this->dataZleceniaOstatniegoPrzegladuUprawnien = $dataZleceniaOstatniegoPrzegladuUprawnien;

        return $this;
    }

    /**
     * Get dataZleceniaOstatniegoPrzegladuUprawnien
     *
     * @return \DateTime 
     */
    public function getDataZleceniaOstatniegoPrzegladuUprawnien()
    {
        return $this->dataZleceniaOstatniegoPrzegladuUprawnien;
    }

    /**
     * Set interwalPrzegladuUprawnien
     *
     * @param integer $interwalPrzegladuUprawnien
     * @return Zasoby
     */
    public function setInterwalPrzegladuUprawnien($interwalPrzegladuUprawnien)
    {
        $this->interwalPrzegladuUprawnien = $interwalPrzegladuUprawnien;

        return $this;
    }

    /**
     * Get interwalPrzegladuUprawnien
     *
     * @return integer 
     */
    public function getInterwalPrzegladuUprawnien()
    {
        return $this->interwalPrzegladuUprawnien;
    }

    /**
     * Set dataZleceniaOstatniegoPrzegladuAktywnosci
     *
     * @param \DateTime $dataZleceniaOstatniegoPrzegladuAktywnosci
     * @return Zasoby
     */
    public function setDataZleceniaOstatniegoPrzegladuAktywnosci($dataZleceniaOstatniegoPrzegladuAktywnosci)
    {
        $this->dataZleceniaOstatniegoPrzegladuAktywnosci = $dataZleceniaOstatniegoPrzegladuAktywnosci;

        return $this;
    }

    /**
     * Get dataZleceniaOstatniegoPrzegladuAktywnosci
     *
     * @return \DateTime 
     */
    public function getDataZleceniaOstatniegoPrzegladuAktywnosci()
    {
        return $this->dataZleceniaOstatniegoPrzegladuAktywnosci;
    }

    /**
     * Set interwalPrzegladuAktywnosci
     *
     * @param integer $interwalPrzegladuAktywnosci
     * @return Zasoby
     */
    public function setInterwalPrzegladuAktywnosci($interwalPrzegladuAktywnosci)
    {
        $this->interwalPrzegladuAktywnosci = $interwalPrzegladuAktywnosci;

        return $this;
    }

    /**
     * Get interwalPrzegladuAktywnosci
     *
     * @return integer 
     */
    public function getInterwalPrzegladuAktywnosci()
    {
        return $this->interwalPrzegladuAktywnosci;
    }

    /**
     * Set dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych
     *
     * @param \DateTime $dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych
     * @return Zasoby
     */
    public function setDataOstatniejZmianyHaselKontAdministracyjnychISerwisowych($dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych)
    {
        $this->dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych = $dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych;

        return $this;
    }

    /**
     * Get dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych
     *
     * @return \DateTime 
     */
    public function getDataOstatniejZmianyHaselKontAdministracyjnychISerwisowych()
    {
        return $this->dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych;
    }

    /**
     * Set interwalZmianyHaselKontaAdministracyjnychISerwisowych
     *
     * @param integer $interwalZmianyHaselKontaAdministracyjnychISerwisowych
     * @return Zasoby
     */
    public function setInterwalZmianyHaselKontaAdministracyjnychISerwisowych($interwalZmianyHaselKontaAdministracyjnychISerwisowych)
    {
        $this->interwalZmianyHaselKontaAdministracyjnychISerwisowych = $interwalZmianyHaselKontaAdministracyjnychISerwisowych;

        return $this;
    }

    /**
     * Get interwalZmianyHaselKontaAdministracyjnychISerwisowych
     *
     * @return integer 
     */
    public function getInterwalZmianyHaselKontaAdministracyjnychISerwisowych()
    {
        return $this->interwalZmianyHaselKontaAdministracyjnychISerwisowych;
    }
    public function __toString(){
        return $this->getNazwa() ? $this->getNazwa() : "";
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Zasoby
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
     * Set grupyAD
     *
     * @param string $grupyAD
     *
     * @return Zasoby
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
    
    public function parseZasobGroupName(){
        $ret = $this->getNazwa();
        $ret = preg_replace("/\([^)]+\)/", "", $ret);
        return trim($ret);
    }

    /**
     * Set wniosekUtworzenieZasobu
     *
     * @param \Parp\MainBundle\Entity\WniosekUtworzenieZasobu $wniosekUtworzenieZasobu
     *
     * @return Zasoby
     */
    public function setWniosekUtworzenieZasobu(\Parp\MainBundle\Entity\WniosekUtworzenieZasobu $wniosekUtworzenieZasobu = null)
    {
        $this->wniosekUtworzenieZasobu = $wniosekUtworzenieZasobu;
        $this->wniosekUtworzenieZasobu->setZasob($this);
        return $this;
    }

    /**
     * Get wniosekUtworzenieZasobu
     *
     * @return \Parp\MainBundle\Entity\WniosekUtworzenieZasobu
     */
    public function getWniosekUtworzenieZasobu()
    {
        return $this->wniosekUtworzenieZasobu;
    }


    /**
     * Set wniosekSkasowanieZasobu
     *
     * @param \Parp\MainBundle\Entity\WniosekUtworzenieZasobu $wniosekSkasowanieZasobu
     *
     * @return Zasoby
     */
    public function setWniosekSkasowanieZasobu(\Parp\MainBundle\Entity\WniosekUtworzenieZasobu $wniosekSkasowanieZasobu = null)
    {
        $this->wniosekSkasowanieZasobu = $wniosekSkasowanieZasobu;

        return $this;
    }

    /**
     * Get wniosekSkasowanieZasobu
     *
     * @return \Parp\MainBundle\Entity\WniosekUtworzenieZasobu
     */
    public function getWniosekSkasowanieZasobu()
    {
        return $this->wniosekSkasowanieZasobu;
    }

    /**
     * Set dataUtworzeniaZasobu
     *
     * @param \DateTime $dataUtworzeniaZasobu
     *
     * @return Zasoby
     */
    public function setDataUtworzeniaZasobu($dataUtworzeniaZasobu)
    {
        $this->dataUtworzeniaZasobu = $dataUtworzeniaZasobu;

        return $this;
    }

    /**
     * Get dataUtworzeniaZasobu
     *
     * @return \DateTime
     */
    public function getDataUtworzeniaZasobu()
    {
        return $this->dataUtworzeniaZasobu;
    }

    /**
     * Set dataZmianyZasobu
     *
     * @param \DateTime $dataZmianyZasobu
     *
     * @return Zasoby
     */
    public function setDataZmianyZasobu($dataZmianyZasobu)
    {
        $this->dataZmianyZasobu = $dataZmianyZasobu;

        return $this;
    }

    /**
     * Get dataZmianyZasobu
     *
     * @return \DateTime
     */
    public function getDataZmianyZasobu()
    {
        return $this->dataZmianyZasobu;
    }

    /**
     * Set dataUsunieciaZasobu
     *
     * @param \DateTime $dataUsunieciaZasobu
     *
     * @return Zasoby
     */
    public function setDataUsunieciaZasobu($dataUsunieciaZasobu)
    {
        $this->dataUsunieciaZasobu = $dataUsunieciaZasobu;

        return $this;
    }

    /**
     * Get dataUsunieciaZasobu
     *
     * @return \DateTime
     */
    public function getDataUsunieciaZasobu()
    {
        return $this->dataUsunieciaZasobu;
    }

    /**
     * Set published
     *
     * @param boolean $published
     *
     * @return Zasoby
     */
    public function setPublished($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Get published
     *
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        if($this->getWniosekUtworzenieZasobu()){
           $this->getWniosekUtworzenieZasobu()->setZasob($this); 
        }
        $this->wnioskiZmieniajaceZasob = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add wnioskiZmieniajaceZasob
     *
     * @param \Parp\MainBundle\Entity\WniosekUtworzenieZasobu $wnioskiZmieniajaceZasob
     *
     * @return Zasoby
     */
    public function addWnioskiZmieniajaceZasob(\Parp\MainBundle\Entity\WniosekUtworzenieZasobu $wnioskiZmieniajaceZasob)
    {
        $this->wnioskiZmieniajaceZasob[] = $wnioskiZmieniajaceZasob;

        return $this;
    }

    /**
     * Remove wnioskiZmieniajaceZasob
     *
     * @param \Parp\MainBundle\Entity\WniosekUtworzenieZasobu $wnioskiZmieniajaceZasob
     */
    public function removeWnioskiZmieniajaceZasob(\Parp\MainBundle\Entity\WniosekUtworzenieZasobu $wnioskiZmieniajaceZasob)
    {
        $this->wnioskiZmieniajaceZasob->removeElement($wnioskiZmieniajaceZasob);
    }

    /**
     * Get wnioskiZmieniajaceZasob
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWnioskiZmieniajaceZasob()
    {
        return $this->wnioskiZmieniajaceZasob;
    }

    /**
     * Set wlascicielZasobuEcm
     *
     * @param string $wlascicielZasobuEcm
     *
     * @return Zasoby
     */
    public function setWlascicielZasobuEcm($wlascicielZasobuEcm)
    {
        $this->wlascicielZasobuEcm = $wlascicielZasobuEcm;

        return $this;
    }

    /**
     * Get wlascicielZasobuEcm
     *
     * @return string
     */
    public function getWlascicielZasobuEcm()
    {
        return $this->wlascicielZasobuEcm;
    }

    /**
     * Set administratorZasobuEcm
     *
     * @param string $administratorZasobuEcm
     *
     * @return Zasoby
     */
    public function setAdministratorZasobuEcm($administratorZasobuEcm)
    {
        $this->administratorZasobuEcm = $administratorZasobuEcm;

        return $this;
    }

    /**
     * Get administratorZasobuEcm
     *
     * @return string
     */
    public function getAdministratorZasobuEcm()
    {
        return $this->administratorZasobuEcm;
    }

    /**
     * Set administratorTechnicznyZasobuEcm
     *
     * @param string $administratorTechnicznyZasobuEcm
     *
     * @return Zasoby
     */
    public function setAdministratorTechnicznyZasobuEcm($administratorTechnicznyZasobuEcm)
    {
        $this->administratorTechnicznyZasobuEcm = $administratorTechnicznyZasobuEcm;

        return $this;
    }

    /**
     * Get administratorTechnicznyZasobuEcm
     *
     * @return string
     */
    public function getAdministratorTechnicznyZasobuEcm()
    {
        return $this->administratorTechnicznyZasobuEcm;
    }

    /**
     * Set wlascicielZasobuZgubieni
     *
     * @param string $wlascicielZasobuZgubieni
     *
     * @return Zasoby
     */
    public function setWlascicielZasobuZgubieni($wlascicielZasobuZgubieni)
    {
        $this->wlascicielZasobuZgubieni = $wlascicielZasobuZgubieni;

        return $this;
    }

    /**
     * Get wlascicielZasobuZgubieni
     *
     * @return string
     */
    public function getWlascicielZasobuZgubieni()
    {
        return $this->wlascicielZasobuZgubieni;
    }

    /**
     * Set administratorZasobuZgubieni
     *
     * @param string $administratorZasobuZgubieni
     *
     * @return Zasoby
     */
    public function setAdministratorZasobuZgubieni($administratorZasobuZgubieni)
    {
        $this->administratorZasobuZgubieni = $administratorZasobuZgubieni;

        return $this;
    }

    /**
     * Get administratorZasobuZgubieni
     *
     * @return string
     */
    public function getAdministratorZasobuZgubieni()
    {
        return $this->administratorZasobuZgubieni;
    }

    /**
     * Set administratorTechnicznyZasobuZgubieni
     *
     * @param string $administratorTechnicznyZasobuZgubieni
     *
     * @return Zasoby
     */
    public function setAdministratorTechnicznyZasobuZgubieni($administratorTechnicznyZasobuZgubieni)
    {
        $this->administratorTechnicznyZasobuZgubieni = $administratorTechnicznyZasobuZgubieni;

        return $this;
    }

    /**
     * Get administratorTechnicznyZasobuZgubieni
     *
     * @return string
     */
    public function getAdministratorTechnicznyZasobuZgubieni()
    {
        return $this->administratorTechnicznyZasobuZgubieni;
    }

    /**
     * Set powiernicyWlascicielaZasobu
     *
     * @param string $powiernicyWlascicielaZasobu
     *
     * @return Zasoby
     */
    public function setPowiernicyWlascicielaZasobu($powiernicyWlascicielaZasobu)
    {
        $this->powiernicyWlascicielaZasobu = $powiernicyWlascicielaZasobu;

        return $this;
    }

    /**
     * Get powiernicyWlascicielaZasobu
     *
     * @return string
     */
    public function getPowiernicyWlascicielaZasobu()
    {
        return $this->powiernicyWlascicielaZasobu;
    }

}
