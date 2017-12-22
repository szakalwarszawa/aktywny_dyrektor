<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserEngagement
 *
 * @ORM\Table(name="userengagement")
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\UserEngagementRepository")
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @Gedmo\Mapping\Annotation\Loggable(logEntryClass="ParpV1\MainBundle\Entity\HistoriaWersji")
 */
class UserEngagement
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
     * @ORM\Column(name="samaccountname", type="string", length=255)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $samaccountname;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Engagement", inversedBy="users")
     * @ORM\JoinColumn(name="engagement_id", referencedColumnName="id")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $engagement;

    /**
     * @var int
     *
     * @ORM\Column(name="percent", type="integer",nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $percent;

    /**
     * @var integer
     *
     * @ORM\Column(name="month", type="integer")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $month;

    /**
     * @var integer
     *
     * @ORM\Column(name="year", type="integer")
     * @Gedmo\Mapping\Annotation\Versioned
     */
    private $year;

    /**
     * @var string
     *
     * @ORM\Column(name="kto_usunal", type="string", length=255, nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
     */
    private $ktoUsunal;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="kiedy_usuniety", type="datetime", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
     */
    private $kiedyUsuniety;

    /**
     * @var boolean
     *
     * @ORM\Column(name="czy_nowy", type="boolean", nullable=true)
     * @Gedmo\Mapping\Annotation\Versioned
     * @APY\DataGridBundle\Grid\Mapping\Column(visible=false)
     */
    private $czyNowy;

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
     * Set id
     *
     * @param integer $id
     *
     * @return UserEngagement
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set engagement
     *
     * @param Engagement $engagement
     * @return UserEngagement
     */
    public function setEngagement($engagement)
    {
        $this->engagement = $engagement;

        return $this;
    }

    /**
     * Get engagement
     *
     * @return integer
     */
    public function getEngagement()
    {
        return $this->engagement;
    }

    /**
     * Set samaccountname
     *
     * @param string $samaccountname
     * @return UserEngagement
     */
    public function setSamaccountname($samaccountname)
    {
        $this->samaccountname = $samaccountname;

        return $this;
    }

    /**
     * Get samaccountname
     *
     * @return string
     */
    public function getSamaccountname()
    {
        return $this->samaccountname;
    }

    /**
     * Set year
     *
     * @param integer $year
     * @return UserEngagement
     */
    public function setYear($year)
    {
        $this->year = $year;
        return $this;
    }

    /**
     * Get year
     *
     * @return integer
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set month
     *
     * @param integer $month
     * @return UserEngagement
     */
    public function setMonth($month)
    {
        $this->month = $month;
        return $this;
    }

    /**
     * Get year
     *
     * @return integer
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return UserEngagement
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
     * Set percent
     *
     * @param float $percent
     *
     * @return UserEngagement
     */
    public function setPercent($percent)
    {
        $this->percent = $percent;

        return $this;
    }

    /**
     * Get percent
     *
     * @return float
     */
    public function getPercent()
    {
        return $this->percent;
    }

    /**
     * Get ktoUsunal
     *
     * @return string
     */
    function getKtoUsunal()
    {
        return $this->ktoUsunal;
    }

    /**
     * Set kiedyUsuniety
     *
     * @param string $ktoUsunal
     *
     * return UserEngagement
     */
    function setKtoUsunal($ktoUsunal)
    {
        $this->ktoUsunal = $ktoUsunal;
    }

    /**
     * Get kiedyUsuniety
     *
     * @return \DateTime
     */
    function getKiedyUsuniety()
    {
        return $this->kiedyUsuniety;
    }

    /**
     * Set kiedyUsuniety
     *
     * @param \DateTime $kiedyUsuniety
     *
     * return UserEngagement
     */
    function setKiedyUsuniety(\DateTime $kiedyUsuniety = null)
    {
        $this->kiedyUsuniety = $kiedyUsuniety;

        return $this;
    }

    /**
     * Get czyNowy
     *
     * @return boolean
     */
    public function getCzyNowy()
    {
        return $this->czyNowy;
    }

    /**
     * Set czyNowy
     *
     * @param boolean $czyNowy
     *
     * @return UserEngagement
     */
    public function setCzyNowy($czyNowy)
    {
        $this->czyNowy = $czyNowy;
        return $this;
    }

    /**
     * Funckcja zwraca zformatowane procrnty
     *
     * @return float
     */
    public function getPercentFormat()
    {
        return $this->percent / 100;
    }

    /**
     * Zapisuje zformatowane procentydo bazy
     *
     * @param int percent
     *
     * @return UserEngagement
     */
    public function setPercentFormat($percent)
    {
        $this->setPercent($percent*100);

        return $this;
    }

}
