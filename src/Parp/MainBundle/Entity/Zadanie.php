<?php

namespace Parp\MainBundle\Entity;
use APY\DataGridBundle\Grid\Mapping as GRID;
use Doctrine\ORM\Mapping as ORM;

/**
 * Zadanie
 *
 * @ORM\Table(name="zadanie")
 * @ORM\Entity
 * @APY\DataGridBundle\Grid\Mapping\Source(columns="id, nazwa, opis, obiekt, osoby ")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="Parp\MainBundle\Entity\HistoriaWersji")
 */
class Zadanie
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
     * @ORM\Column(name="nazwa", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $nazwa;
    
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="opis", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $opis;
    
    /**
     * @var string
     *
     * @ORM\Column(name="komentarz", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $komentarz;

    /**
     * @var string
     *
     * @ORM\Column(name="osoby", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $osoby;
        
    
    /**
    * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataDodania;
    
    /**
    * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $dataUkonczenia;
    
    /**
     * @var string
     *
     * @ORM\Column(name="ukonczone_przez", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $ukonczonePrzez;
        
    /**
     * @var string
     *
     * @ORM\Column(name="obiekt", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $obiekt;
    /**
     * @var string
     *
     * @ORM\Column(name="obiektId", type="integer")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $obiektId;
}