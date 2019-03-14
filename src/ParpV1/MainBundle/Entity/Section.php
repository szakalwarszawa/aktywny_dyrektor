<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * Section
 *
 * @ORM\Table(name="section")
 * @ORM\Entity
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, name, shortname, departament.name, departament.shortname, kierownikName")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class Section
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
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message = "Nazwa sekcji nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa sekcji musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa sekcji musi zawierać maxymalnie do {{ limit }} znaków.")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="shortname", type="string", length=8)
     * @Assert\NotBlank(message = "Skrót sekcji nie jest wypełniony.")
     * @Assert\Length(
     *      min = 2,
     *      max = 8,
     *      minMessage = "Skrót sekcji zawierać od {{ limit }} znaków.",
     *      maxMessage = "Skrót sekcji musi zawierać maxymalnie do {{ limit }} znaków.")*
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $shortname;



    /**
     *
     * @ORM\ManyToOne(targetEntity="Departament", inversedBy="section")
     * @ORM\JoinColumn(name="departament_id", referencedColumnName="id")
     * @GRID\Column(field="departament.name", title="Departament", visible=true)
     * @GRID\Column(field="departament.shortname", title="Departamen skrót", visible=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $departament;


    /**
     * @var string
     *
     * @ORM\Column(name="kierownikName", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $kierownikName;

    /**
     * @var string
     *
     * @ORM\Column(name="kierownikDN", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $kierownikDN;

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
     * Set name
     *
     * @param string $name
     * @return Section
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set shortname
     *
     * @param string $shortname
     * @return Section
     */
    public function setShortname($shortname)
    {
        $this->shortname = $shortname;

        return $this;
    }

    /**
     * Get shortname
     *
     * @return string
     */
    public function getShortname()
    {
        return $this->shortname;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Section
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
     * Set kierownikName
     *
     * @param string $kierownikName
     *
     * @return Section
     */
    public function setKierownikName($kierownikName)
    {
        $this->kierownikName = $kierownikName;

        return $this;
    }

    /**
     * Get kierownikName
     *
     * @return string
     */
    public function getKierownikName()
    {
        return $this->kierownikName;
    }

    /**
     * Set kierownikDN
     *
     * @param string $kierownikDN
     *
     * @return Section
     */
    public function setKierownikDN($kierownikDN)
    {
        $this->kierownikDN = $kierownikDN;

        return $this;
    }

    /**
     * Get kierownikDN
     *
     * @return string
     */
    public function getKierownikDN()
    {
        return $this->kierownikDN;
    }

    /**
     * Set departament
     *
     * @param \ParpV1\MainBundle\Entity\Departament $departament
     *
     * @return Section
     */
    public function setDepartament(\ParpV1\MainBundle\Entity\Departament $departament = null)
    {
        $this->departament = $departament;

        return $this;
    }

    /**
     * Get departament
     *
     * @return \ParpV1\MainBundle\Entity\Departament
     */
    public function getDepartament()
    {
        return $this->departament;
    }

    /**
     * Obiekt jako string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
