<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="position_groups")
 *
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, name")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class PositionGroups
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message = "Nazwa grupy stanowisk nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa grupy stanowisk musi zawierać minimum {{ limit }} znaków.",
     *      maxMessage = "Nazwa grupy stanowisk może zawierać do {{ limit }} znaków.")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $description;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var Position
     *
     * @ORM\OneToMany(targetEntity="ParpV1\MainBundle\Entity\Position", mappedBy="group")
     */
    private $positions;

    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->positions = new ArrayCollection();
    }

    /**
     * Get ID.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get Name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return PositionGroups
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return PositionGroups
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get deletedAt.
     *
     * @return DateTimeInterface|null
     */
    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    /**
     * Set deletedAt.
     *
     * @param DateTimeInterface|null $deletedAt
     *
     * @return PositionGroups
     */
    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get Positions
     *
     * @return Collection|Position[]
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    /**
     * Add Position
     *
     * @param Position $position
     *
     * @return PositionGroups
     */
    public function addPosition(Position $position): self
    {
        if (!$this->positions->contains($position)) {
            $this->positions[] = $position;
            $position->setGroupId($this);
        }

        return $this;
    }

    /**
     * Remove Position
     *
     * @param Position $position
     *
     * @return PositionGroups
     */
    public function removePosition(Position $position): self
    {
        if ($this->positions->contains($position)) {
            $this->positions->removeElement($position);
            if ($position->getGroupId() === $this) {
                $position->setGroupId(null);
            }
        }

        return $this;
    }
}
