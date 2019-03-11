<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OdebranieZasobowEntry
 *
 * @ORM\Table(name="odebranie_zasobow_entry")
 * @ORM\Entity()
 */
class OdebranieZasobowEntry
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="uzytkownik", type="text")
     */
    protected $uzytkownik;

    /**
     * @var string
     *
     * @ORM\Column(name="powod_odebrania", type="text")
     */
    protected $powodOdebrania;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uzytkownik.
     *
     * @param string $uzytkownik
     *
     * @return OdebranieZasobowEntry
     */
    public function setUzytkownik(string $uzytkownik)
    {
        $this->uzytkownik = $uzytkownik;

        return $this;
    }

    /**
     * Get uzytkownik.
     *
     * @return string
     */
    public function getUzytkownik(): string
    {
        return $this->uzytkownik;
    }

    /**
     * Set powodOdebrania.
     *
     * @param string $powodOdebrania
     *
     * @return OdebranieZasobowEntry
     */
    public function setPowodOdebrania($powodOdebrania)
    {
        $this->powodOdebrania = $powodOdebrania;

        return $this;
    }

    /**
     * Get powodOdebrania.
     *
     * @return string
     */
    public function getPowodOdebrania()
    {
        return $this->powodOdebrania;
    }
}
