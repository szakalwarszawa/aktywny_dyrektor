<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AccessLevelGroup
 *
 * @ORM\Table(name="access_level_group")
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Repository\AccessLevelGroupRepository")
 */
class AccessLevelGroup
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="group_name", type="string", length=100)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 1,
     *      max = 100,
     *      minMessage = "Nazwa grupy musi mieć minimalnie {{ limit }} znaków długości.",
     *      maxMessage = "Nazwa grupy musi mieć maksymalnie {{ limit }} znaków długości."
     *  )
     *
     */
    protected $groupName;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=500)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 2,
     *      max = 500,
     *      minMessage = "Opis musi mieć minimalnie {{ limit }} znaków długości.",
     *      maxMessage = "Opis musi mieć maksymalnie {{ limit }} znaków długości."
     *  )
     */
    protected $description;

    /**
     * Poziomy dostępu przechowywane jako string oddzielany średnikiem.
     *
     * @var string
     *
     * @ORM\Column(name="access_levels", type="text")
     * @Assert\NotBlank(
     *      message = "Musisz określić co najmniej jeden poziom dostępu dla grupy"
     * )
     */
    protected $accessLevels;

    /**
     * @var Zasoby
     *
     * @ORM\ManyToOne(targetEntity="Zasoby", inversedBy="accessLevelGroups")
     * @ORM\JoinColumn(name="zasob_id", referencedColumnName="id")
     */
    protected $zasob;

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
     * Set groupName.
     *
     * @param string $groupName
     *
     * @return AccessLevelGroup
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * Get groupName.
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * Set accessLevels.
     *
     * @param string $accessLevels
     *
     * @return AccessLevelGroup
     */
    public function setAccessLevels($accessLevels)
    {
        $this->accessLevels = $accessLevels;

        return $this;
    }

    /**
     * Get accessLevels.
     *
     * @return string
     */
    public function getAccessLevels()
    {
        return $this->accessLevels;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get zasob
     *
     * @return Zasoby
     */
    public function getZasob()
    {
        return $this->zasob;
    }

    /**
     * Set zasob
     *
     * @param Zasoby $zasob
     *
     * @return self
     */
    public function setZasob(Zasoby $zasob)
    {
        $this->zasob = $zasob;

        return $this;
    }
}
