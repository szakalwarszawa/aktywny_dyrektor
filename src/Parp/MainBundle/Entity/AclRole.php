<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Position
 *
 * @ORM\Table(name="acl_role")
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"name"},
 *     errorPath="name",
 *     message="Nazwa stanowiska musi być unikalna")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, name, opis")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class AclRole
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
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;
    
    /**
     * @ORM\ManyToMany(targetEntity="AclAction", inversedBy="roles", cascade={"persist","remove"})
     * @ORM\JoinTable(name="acl_role_action")
     *GRID\Column(field="uprawnienia.opis:group_concat", title="Uprawnienia", filter="select", selectMulti="true")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $actions;
    
    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="AclUserRole", mappedBy="roles")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $users;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->actions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * @return AclRole
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
     * Set name
     *
     * @param string $name
     *
     * @return AclRole
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
     * Add action
     *
     * @param \Parp\MainBundle\Entity\AclAction $action
     *
     * @return AclRole
     */
    public function addAction(\Parp\MainBundle\Entity\AclAction $action)
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * Remove action
     *
     * @param \Parp\MainBundle\Entity\AclAction $action
     */
    public function removeAction(\Parp\MainBundle\Entity\AclAction $action)
    {
        $this->actions->removeElement($action);
    }

    /**
     * Get actions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Add user
     *
     * @param \Parp\MainBundle\Entity\AclUserRole $user
     *
     * @return AclRole
     */
    public function addUser(\Parp\MainBundle\Entity\AclUserRole $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \Parp\MainBundle\Entity\AclUserRole $user
     */
    public function removeUser(\Parp\MainBundle\Entity\AclUserRole $user)
    {
        $this->users->removeElement($user);
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
     * Set opis
     *
     * @param string $opis
     *
     * @return AclRole
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
    
    
    public function __toString()
    {
        return $this->opis;
    }
}
