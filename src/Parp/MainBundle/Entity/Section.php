<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Section
 *
 * @ORM\Table(name="section")
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"name"},
 *     errorPath="name",
 *     message="Nazwa sekcji musi być unikalna")
 * @UniqueEntity(
 *     fields={"shortname"},
 *     errorPath="shortname",
 *     message="Skrót sekcji musi być unikalny")
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message = "Nazwa sekcji nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa sekcji musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa sekcji musi zawierać maxymalnie do {{ limit }} znaków.")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="shortname", type="string", length=8)
     * @Assert\NotBlank(message = "Skrót sekcji nie jest wypełniony.")
     * @Assert\Length(
     *      min = 2,
     *      max = 5,
     *      minMessage = "Skrót sekcji zawierać od {{ limit }} znaków.",
     *      maxMessage = "Skrót sekcji musi zawierać maxymalnie do {{ limit }} znaków.")* 
     */
    private $shortname;


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
}
