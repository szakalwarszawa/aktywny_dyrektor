<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use APY\DataGridBundle\Grid\Mapping as GRID;
use DateTime;

/**
 * UserZasoby
 *
 * @ORM\Table(name="komentarz", indexes={@ORM\Index(name="obiekt_id_idx", columns={"obiektId"})})
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\KomentarzRepository")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id,samaccountname,createdAt,tytul,opis")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class Komentarz
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
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
    */
    private $deletedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="samaccountname", type="string", length=255)
     * @GRID\Column(title="Autor")
     * @Gedmo\Versioned
     */
    private $samaccountname;



    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @GRID\Column(type="datetime", format="Y-m-d H:i:s", title="Utworzono")
     * @Gedmo\Timestampable(on="update")
     * @Gedmo\Versioned
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="tytul", type="string", length=100, nullable=false)
     * @Gedmo\Versioned
     * @GRID\Column(title="Tytuł")
     * @Assert\NotBlank
     * @Assert\Length(
     *      min = 5,
     *      max = 100,
     *      minMessage = "Tytuł musi mieć co najmniej {{ limit }} znaków.",
     *      maxMessage = "Tytuł musi mieć maksymalnie {{ limit }} znaków."
     * )
     */
    private $tytul;

    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=5000, nullable=false)
     * @GRID\Column(title="Opis")
     * @Gedmo\Versioned
     * @Assert\NotBlank
     * @Assert\Length(
     *      min = 5,
     *      max = 5000,
     *      minMessage = "Treść musi mieć co najmniej {{ limit }} znaków.",
     *      maxMessage = "Treść musi mieć maksymalnie {{ limit }} znaków."
     * )
     */
    private $opis;


    /**
     * @var string
     *
     * @ORM\Column(name="obiekt", type="string", length=255)
     * @Gedmo\Versioned
     */
    private $obiekt;
    /**
     * @var string
     *
     * @ORM\Column(name="obiektId", type="string", length=255)
     * @Gedmo\Versioned
     */
    private $obiektId;


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
     * @param DateTime $deletedAt
     *
     * @return WniosekNadanieOdebranieZasobowEditor
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set samaccountname
     *
     * @param string $samaccountname
     *
     * @return WniosekNadanieOdebranieZasobowEditor
     */
    public function setSamaccountname($samaccountname)
    {
        $this->samaccountname = $samaccountname;

        return $this;
    }

    /**
     * Get samaccountname
     *
     * @return string
     */
    public function getSamaccountname()
    {
        return $this->samaccountname;
    }

    /**
     * Get createdAt
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set tytul
     *
     * @param string $tytul
     *
     * @return Komentarz
     */
    public function setTytul($tytul)
    {
        $this->tytul = $tytul;

        return $this;
    }

    /**
     * Get tytul
     *
     * @return string
     */
    public function getTytul()
    {
        return $this->tytul;
    }

    /**
     * Set opis
     *
     * @param string $opis
     *
     * @return Komentarz
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
     * Set obiekt
     *
     * @param string $obiekt
     *
     * @return Komentarz
     */
    public function setObiekt($obiekt)
    {
        $this->obiekt = $obiekt;

        return $this;
    }

    /**
     * Get obiekt
     *
     * @return string
     */
    public function getObiekt()
    {
        return $this->obiekt;
    }


    /**
     * Set obiektId
     *
     * @param string $obiektId
     *
     * @return Komentarz
     */
    public function setObiektId($obiektId)
    {
        $this->obiektId = $obiektId;

        return $this;
    }

    /**
     * Get obiektId
     *
     * @return string
     */
    public function getObiektId()
    {
        return $this->obiektId;
    }
}
