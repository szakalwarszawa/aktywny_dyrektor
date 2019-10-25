<?php

declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ParpV1\MainBundle\Entity\AclRole;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * RolePrivilege
 *
 * @ORM\Table(
 *  name="jasper_role_privilege",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_role", columns={"role_id"}),
 *  })
 * )
 * @ORM\Entity(repositoryClass="ParpV1\JasperReportsBundle\Repository\RolePrivilegeRepository")
 */
class RolePrivilege
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
     * @var AclRole
     *
     * @ORM\ManyToOne(targetEntity="ParpV1\MainBundle\Entity\AclRole")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id", unique=true)
     * @Assert\NotNull
     */
    protected $role;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Path")
     * @ORM\JoinTable(name="jasper_role_paths",
     *      joinColumns={@ORM\JoinColumn(name="role_privilege_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="path_id", referencedColumnName="id")}
     *      )
     */
    protected $paths;

    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->paths = new ArrayCollection();
    }

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
     * Set role.
     *
     * @param AclRole $role
     *
     * @return JasperFetchConfig
     */
    public function setRole(AclRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @return AclRole
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set paths.
     *
     * @param ArrayCollection $paths
     *
     * @return RolePrivilege
     */
    public function setPaths(ArrayCollection $paths): self
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * Add path
     *
     * @param Path
     *
     * @return RolePrivilege
     */
    public function addPath(Path $path): self
    {
        $this->paths->add($path);

        return $this;
    }

    /**
     * Remove path
     *
     * @param Path
     *
     * @return RolePrivilege
     */
    public function removePath(Path $path): self
    {
        $this->paths->remove($path);

        return $this;
    }

    /**
     * Get path.
     *
     * @return ArrayCollection|PersistentCollection
     */
    public function getPaths()
    {
        return $this->paths;
    }
}
