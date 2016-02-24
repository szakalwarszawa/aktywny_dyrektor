<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Engagement
 *
 * @ORM\Table(name="engagement")
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"name"},
 *     errorPath="name",
 *     message="Nazwa zaangażowania musi być unikalna")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Engagement
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
     * @Assert\NotBlank(message = "Nazwa zaangażowania nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa zaangażowania musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa zaangażowania musi zawierać maxymalnie do {{ limit }} znaków.") 
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="UserEngagement", mappedBy="engagement")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $users;

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
     * @return Engagement
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

    public function __toString(){
        return $this->name;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add users
     *
     * @param \Parp\MainBundle\Entity\UserEngagement $users
     * @return Engagement
     */
    public function addUser(\Parp\MainBundle\Entity\UserEngagement $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Parp\MainBundle\Entity\UserEngagement $users
     */
    public function removeUser(\Parp\MainBundle\Entity\UserEngagement $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Engagement
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
}
