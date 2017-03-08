<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Email
 *
 * @ORM\Table(name="email")
 * @ORM\Entity
 */
class Email
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="uzytkownik", type="text", nullable=true)
     */
    private $uzytkownik;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="data_wysylki", type="datetime", nullable=true)
     */
    private $dataWysylki;

    /**
     * @var string
     *
     * @ORM\Column(name="temat", type="text", nullable=true)
     */
    private $temat;

    /**
     * @var string
     *
     * @ORM\Column(name="tresc_txt", type="text", nullable=true)
     */
    private $trescTxt;

    /**
     * @var string
     *
     * @ORM\Column(name="tresc_html", type="text", nullable=true)
     */
    private $trescHtml;

    /**
     * @var int
     *
     * @ORM\Column(name="liczba_maili", type="integer", nullable=true)
     */
    private $liczbaMaili;

    /**
     * @var array
     *
     * @ORM\Column(name="odbiorca", type="text", nullable=true)
     */
    private $odbiorca;

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
     * Set dataWysylki
     *
     * @param \DateTime $dataWysylki
     *
     * @return Email
     */
    public function setDataWysylki($dataWysylki)
    {
        $this->dataWysylki = $dataWysylki;

        return $this;
    }

    /**
     * Get dataWysylki
     *
     * @return \DateTime
     */
    public function getDataWysylki()
    {
        return $this->dataWysylki;
    }

    /**
     * Set temat
     *
     * @param string $temat
     *
     * @return Email
     */
    public function setTemat($temat)
    {
        $this->temat = $temat;

        return $this;
    }

    /**
     * Get temat
     *
     * @return string
     */
    public function getTemat()
    {
        return $this->temat;
    }

    /**
     * @return string
     */
    public function getTrescTxt()
    {
        return $this->trescTxt;
    }

    /**
     * @param string $trescTxt
     */
    public function setTrescTxt($trescTxt)
    {
        $this->trescTxt = $trescTxt;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrescHtml()
    {
        return $this->trescHtml;
    }

    /**
     * @param string $trescHtml
     */
    public function setTrescHtml($trescHtml)
    {
        $this->trescHtml = $trescHtml;

        return $this;
    }

    /**
     * @return int
     */
    public function getLiczbaMaili()
    {
        return $this->liczbaMaili;
    }

    /**
     * @param int $liczbaMaili
     */
    public function setLiczbaMaili($liczbaMaili)
    {
        $this->liczbaMaili = $liczbaMaili;

        return $this;
    }

    /**
     * @return array
     */
    public function getOdbiorca()
    {
        return $this->odbiorca;
    }

    /**
     * @param array $odbiorca
     */
    public function setOdbiorca($odbiorca)
    {
        if (is_array($odbiorca)) {
            $odbiorca = implode(";", $odbiorca);
        }

        $this->odbiorca = $odbiorca;

        return $this;
    }

    /**
     * Set uzytkownik
     *
     * @param string $uzytkownik
     *
     * @return Email
     */
    public function setUzytkownik($uzytkownik)
    {
        $this->uzytkownik = $uzytkownik;

        return $this;
    }

    /**
     * Get uzytkownik
     *
     * @return string
     */
    public function getUzytkownik()
    {
        return $this->uzytkownik;
    }
}
