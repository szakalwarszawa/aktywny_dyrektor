<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * Position
 *
 * @ORM\Table(name="position")
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"name"},
 *     errorPath="name",
 *     message="Nazwa stanowiska musi być unikalna")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, name, group.name")
 */
class Position
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
     * @GRID\Column(field="name", title="Nazwa stanowiska")
     * @Assert\NotBlank(message = "Nazwa stanowiska nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa stanowiska musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa stanowiska musi zawierać maxymalnie do {{ limit }} znaków.")*
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="ParpV1\MainBundle\Entity\PositionGroups", inversedBy="positions")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     * @GRID\Column(field="group.name", title="Grupa uprawnień")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $group;


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
     * @return Position
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

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Position
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
     * Get group
     *
     * @return PositionGroups
     */
    public function getGroup(): ?PositionGroups
    {
        return $this->group;
    }

    /**
     * Set group
     *
     * @param PositionGroups $group
     *
     * @return Position
     */
    public function setGroup(?PositionGroups $group): self
    {
        $this->group = $group;

        return $this;
    }
}
