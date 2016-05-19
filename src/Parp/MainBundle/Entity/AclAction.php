<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Position
 *
 * @ORM\Table(name="acl_action")
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"skrot"},
 *     errorPath="skrot",
 *     message="Skrót musi być unikalny")
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, name, skrot")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class AclAction
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
     * @ORM\Column(name="skrot", type="string", length=255)
     * @Assert\NotBlank(message = "Skrót  nie jest wypełniony.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Skrót musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Skrót musi zawierać maxymalnie do {{ limit }} znaków.")* 
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $skrot;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;
    
    /**
     * @ORM\ManyToMany(targetEntity="AclRole", mappedBy="actions")
     * @ORM\JoinTable(name="acl_role_action")
     *GRID\Column(field="grupy.opis:group_concat", title="Grupy", filter="select", selectMulti="true")
     * @@Gedmo\Mapping\Annotation\Versioned
     */
    private $roles;
    
}