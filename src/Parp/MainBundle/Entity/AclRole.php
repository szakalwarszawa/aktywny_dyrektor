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
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, name")
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
     * @ORM\ManyToMany(targetEntity="AclAction", inversedBy="roles")
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
    
}