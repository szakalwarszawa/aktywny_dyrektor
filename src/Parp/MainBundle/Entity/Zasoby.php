<?php

namespace Parp\MainBundle\Entity;
use APY\DataGridBundle\Grid\Mapping as GRID;
use Doctrine\ORM\Mapping as ORM;

/**
 * Zasoby
 *
 * @ORM\Table(name="zasoby")
 * @ORM\Entity
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, nazwa, opis, biuro")
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
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dkPolskaPelnaNazwaKlastraWynikajacaZDokumentu;


    /**
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dkAngielskaNazwaKlastra;


    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dkFormaOrganizacyjnoprawnaKlastra;


    /**
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dkAdresStronyInternetowejKlastra;


    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dkDominujacaBranza;


    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dkRokPowolaniaKlastra;


    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dkLiczbaCzlonkowKlastraZlokalizowanaNaTerenie;


    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dkNazwaWojewodztwaWKtorymZlokalizowaniSaCzlon;




    /**
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dkNazwaKoordynatoraZgodnaZDokumentemRejestrow;



    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $asUlica;


    /**
     *
     * @ORM\Column(type="string", length=10, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $asNumerDomu;


    /**
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $asNumerLokalu;


    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $asMiejscowosc;


    /**
     *
     * @ORM\Column(type="string", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $asKodPocztowy;


    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $asWojewodztwo;




    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $asNazwaDokumentuKlastraWKtorymPodmiotJestWska;





    /**
     *
     * @ORM\Column(type="string", length=30, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $odNazwisko;


    /**
     *
     * @ORM\Column(type="string", length=30, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $odImie;


    /**
     *
     * @ORM\Column(type="integer", length=30, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $odTelefon;


    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $odAdresEmail;




    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $srKlasterPosiadaStrategie;


    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $srStrategiaSpisanaWFormieDokumentu;




    /**
     *
     * @ORM\Column(type="integer", length=4, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skSumarycznaLiczbaCzlonkowKlastra;


    /**
     *
     * @ORM\Column(type="integer", length=4, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skSumarycznaLiczbaPrzedsiebiorstw;


    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skLiczbaMikroPrzedsiebiorstw;



    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skLiczbaMalychPrzedsiebiorstw;



    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skLiczbaSrednichPrzedsiebiorstw;



    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skLiczbaDuzychPrzedsiebiorstw;



    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skSumarycznaLiczbaIob;


    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skLiczbaOsrodkowInnowacji;



    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skLiczbaOsrodkowPrzedsiebiorczosci;



    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skLiczbaNiebankowychInstytucjiFinansowych;



    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skLiczbaJednostekNaukowych;



    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skLiczbaInnychPodmiotow;





    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zSumaryczneZatrudnienieWKlastrze;


    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zSumaryczneZatrudnienieWPrzedsiebiorstwach;


    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zLiczbaZatrudnionychWMikroPrzedsiebiorstwach;



    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zLiczbaZatrudnionychWMalychPrzedsiebiorstwach;



    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zLiczbaZatrudnionychWSrednichPrzedsiebiorstwa;



    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zLiczbaZatrudnionychWDuzychPrzedsiebiorstwach;



    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zSumarycznaLiczbaZatrudnionychWIob;


    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zLiczbaZatrudnionychWOsrodkachInnowacji;



    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zLiczbaZatrudnionychWOsrodkachPrzedsiebiorczo;



    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zLiczbaZatrudnionychWNiebankowychInstytucjach;



    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zLiczbaZatrudnionychWJednostkachNaukowychDoty;



    /**
     *
     * @ORM\Column(type="integer", length=6, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $zLiczbaZatrudnionychWInnychPodmiotach;





    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iNajwazniejszeKrajeWKtorychCzlonkowieKlastraP;



    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iPozostaleKrajeWKtorychCzlonkowieKlastraProwa;



    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iCzyKlasterNalezyDoEuropeanCollborationPlatfo;


    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iCzyKlasterNalezyDoEuropeanClusterObservatory;


    /**
     *
     * @ORM\Column(type="string", length=250, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iNazwyMiedzynarodowychSieciStowarzyszenDoKtor;


    /**
     *
     * @ORM\Column(type="string", length=250, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iNazwyZagranicznychKlastrowZKtorymiWspolpracu;


    /**
     *
     * @ORM\Column(type="string", length=250, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iNazwyZagranicznychPodmiotowNieBedacychSiecia;




    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iLiczbaSalKonferencyjnych;


    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iLiczbaSalSzkoleniowych;


    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iLiczbaCentrowBadawczych;


    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iLiczbaLaboratoriowSpecjalistycznych;


    /**
     *
     * @ORM\Column(type="integer", length=3, nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $iLiczbaInnychElementowInfrastruktury;




    /**
     *
     * @ORM\Column(type="string", nullable=false)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $oZasiegOddzialywaniaKlastraNaOtoczenie;







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
        return $this->getNazwa();
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
}
