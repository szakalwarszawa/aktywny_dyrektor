<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


// ALe można tez wymusic unikalnośc 2 pól na raz
//@UniqueEntity(
//     fields={"host", "port"},
//     errorPath="port",
//     message="This port is already in use on that host.")
//

/**
 * Departament
 *
 * @ORM\Table(name="departament")
 * aa@Gedmo\Loggable
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"name"},
 *     errorPath="name",
 *     message="Nazwa Biura/Departamentu musi być unikalna")
 * @UniqueEntity(
 *     fields={"shortname"},
 *     errorPath="shortname",
 *     message="Skrót Biura/Departamentu musi być unikalny")
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Departament
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message = "Nazwa Biura/Departamentu nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "Nazwa Biura/Departamentu musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Nazwa Biura/Departamentu musi zawierać maxymalnie do {{ limit }} znaków.")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="shortname", type="string", length=5)
     * @Assert\NotBlank(message = "Skrót Biura/Departamentu nie jest wypełniona.")
     * @Assert\Length(
     *      min = 2,
     *      max = 5,
     *      minMessage = "Skrót Biura/Departamentu musi zawierać od {{ limit }} znaków.",
     *      maxMessage = "Skrót Biura/Departamentu musi zawierać maxymalnie do {{ limit }} znaków.")
     * @Gedmo\Mapping\Annotation\Versioned
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
     * @return Departament
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
     * @return Departament
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
