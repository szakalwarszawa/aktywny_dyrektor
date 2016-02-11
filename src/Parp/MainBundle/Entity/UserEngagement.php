<?php

namespace Parp\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserEngagement
 *
 * @ORM\Table(name="userengagement")
 * @ORM\Entity(repositoryClass="Parp\MainBundle\Entity\UserEngagementRepository")
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
     * @var string
     *
     * @ORM\Column(name="samaccountname", type="string", length=255)
     */
    private $samaccountname;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Engagement", inversedBy="users")
     * @ORM\JoinColumn(name="engagement_id", referencedColumnName="id")
     */
    private $engagement;

    /**
     * @var integer
     *
     * @ORM\Column(name="percent", type="integer",nullable=true)
     */
    private $percent;

    /**
     * @var integer
     *
     * @ORM\Column(name="month", type="integer")
     */
    private $month;

    /**
     * @var integer
     *
     * @ORM\Column(name="year", type="integer")
     */
    private $year;

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
     * Set engagement
     *
     * @param integer $engagement
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
     * Set percent
     *
     * @param integer $percent
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
     * @return integer 
     */
    public function getPercent()
    {
        return $this->percent;
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

}
