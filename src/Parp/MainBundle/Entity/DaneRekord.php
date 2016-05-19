<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Annotations\UniqueConstraint;
/**
 * DaneRekord
 *
 * @ORM\Table(name="dane_rekord", uniqueConstraints={@ORM\UniqueConstraint(name="imie_naziwsko", columns={"imie", "nazwisko"})})
 * a@Gedmo\Loggable
 * @ORM\Entity
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, imie, nazwisko, departament, stanowisko, umowa, umowaOd, umowaDo")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class DaneRekord
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
}
