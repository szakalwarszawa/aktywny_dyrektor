<?php

namespace Parp\MainBundle\Entity;
use APY\DataGridBundle\Grid\Mapping as GRID;

use Doctrine\ORM\Mapping as ORM;

/**
 * Zasoby
 *
 * @ORM\Table(name="zasoby")
 * @ORM\Entity
 * @GRID\Source(columns="id, nazwa, opis, biuro")
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
     * @var string
     *
     * @ORM\Column(name="nazwa", type="string", length=255)
     */
    private $nazwa;

    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="text")
     */
    private $opis;

    /**
     * @var string
     *
     * @ORM\Column(name="biuro", type="string", length=255)
     */
    private $biuro;
    
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $wlascicielZasobu;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $administratorZasobu;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $administratorTechnicznyZasobu;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $uzytkownicy;
    /**
    * @var boolean
     *
     * @ORMColumn(type="boolean", nullable=true)
     */
    private $daneOsobowe;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $komorkaOrgazniacyjna;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $miejsceInstalacji;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $opisZasobu;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $modulFunkcja;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $poziomDostepu;
    /**
    * @var \DateTime
     *
     * @ORMColumn(type="datetime", nullable=true)
     */
    private $dataZakonczeniaWdrozenia;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $wykonawca;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $nazwaWykonawcy;
    /**
    * @var boolean
     *
     * @ORMColumn(type="boolean", nullable=true)
     */
    private $asystaTechniczna;
    /**
    * @var \DateTime
     *
     * @ORMColumn(type="datetime", nullable=true)
     */
    private $dataWygasnieciaAsystyTechnicznej;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $dokumentacjaFormalna;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $dokumentacjaProjektowoTechniczna;
    /**
    * @var string
     *
     * @ORMColumn(type="string", length=255, nullable=true)
     */
    private $technologia;
    /**
    * @var boolean
     *
     * @ORMColumn(type="boolean", nullable=true)
     */
    private $testyBezpieczenstwa;
    /**
    * @var boolean
     *
     * @ORMColumn(type="boolean", nullable=true)
     */
    private $testyWydajnosciowe;
    /**
    * @var \DateTime
     *
     * @ORMColumn(type="datetime", nullable=true)
     */
    private $dataZleceniaOstatniegoPrzegladuUprawnien;
    /**
    * @var integer
     *
     * @ORMColumn(type="integer", nullable=true)
     */
    private $interwalPrzegladuUprawnien;
    /**
    * @var \DateTime
     *
     * @ORMColumn(type="datetime", nullable=true)
     */
    private $dataZleceniaOstatniegoPrzegladuAktywnosci;
    /**
    * @var integer
     *
     * @ORMColumn(type="integer", nullable=true)
     */
    private $interwalPrzegladuAktywnosci;
    /**
    * @var \DateTime
     *
     * @ORMColumn(type="datetime", nullable=true)
     */
    private $dataOstatniejZmianyHaselKontAdministracyjnychISerwisowych;
    /**
    * @var integer
     *
     * @ORMColumn(type="integer", nullable=true)
     */
    private $interwalZmianyHaselKontaAdministracyjnychISerwisowych;



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
}
