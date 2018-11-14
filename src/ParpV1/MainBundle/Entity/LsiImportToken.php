<?php

namespace ParpV1\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use ParpV1\MainBundle\Entity\Wniosek;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * LsiImportTokens
 *
 * @ORM\Table(name="lsi_import_tokens")
 * @ORM\Entity(repositoryClass="ParpV1\MainBundle\Entity\Repository\LsiImportTokenRepository")
 */
class LsiImportToken
{
    const NEW_TOKEN = 'new_token';
    const ACTIVE_TOKEN = 'active_token';
    const SUCCESSFULLY_USED_TOKEN = 'successfully_used_token';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Nazwa użytkownika wywołującego utworzenie nowego rekordu.
     *
     * @var string
     *
     * @ORM\Column(name="requested_by", type="string", length=255)
     */
    protected $requestedBy;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="last_used_at", type="datetime")
     */
    protected $lastUsedAt;

    /**
    * @var DateTime
    *
    * @ORM\Column(name="expire_at", type="datetime")
    */
    protected $expireAt;

    /**
     * Wniosek, do którego przypisany jest token.
     *
     * @var Wniosek
     *
     * @ORM\ManyToOne(
     *     targetEntity="ParpV1\MainBundle\Entity\Wniosek"
     * )
     * @ORM\JoinColumn(
     *      name="id_wniosku",
     *      referencedColumnName="id",
     *      nullable=false
     * ),
     */
    protected $wniosek;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255)
     */
    protected $token;

    /**
     * @var integer
     *
     * @ORM\Column(name="use_count", type="integer", nullable=true)
     */
    protected $useCount;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    protected $status;


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
     * Set requestedBy
     *
     * @param string $requestedBy
     *
     * @return LsiImportToken
     */
    public function setRequestedBy($requestedBy)
    {
        $this->requestedBy = $requestedBy;

        return $this;
    }

    /**
     * Get requestedBy
     *
     * @return string
     */
    public function getRequestedBy()
    {
        return $this->requestedBy;
    }

    /**
     * Set createdAt
     *
     * @param DateTime $createdAt
     *
     * @return LsiImportToken
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set expireAt
     *
     * @param DateTime $expireAt
     *
     * @return LsiImportToken
     */
    public function setExpireAt(DateTime $expireAt)
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * Get expireAt
     *
     * @return DateTime
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }

    /**
     * Set lastUsedAt
     *
     * @param DateTime $lastUsedAt
     *
     * @return LsiImportToken
     */
    public function setLastUsedAt(DateTime $lastUsedAt)
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }

    /**
     * Get lastUsedAt
     *
     * @return DateTime
     */
    public function getLastUsedAt()
    {
        return $this->lastUsedAt;
    }

    /**
     * Set wniosek
     *
     * @param Wniosek $wniosek
     *
     * @return LsiImportToken
     */
    public function setWniosek(Wniosek $wniosek)
    {
        $this->wniosek = $wniosek;

        return $this;
    }

    /**
     * Get wniosek
     *
     * @return Wniosek
     */
    public function getWniosek()
    {
        return $this->wniosek;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return LsiImportToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set useCount
     *
     * @param integer $useCount
     *
     * @return LsiImportToken
     */
    public function setUseCount($useCount = 0)
    {
        $this->useCount = $useCount;

        return $this;
    }

    /**
     * Get useCount
     *
     * @return integer
     */
    public function getUseCount()
    {
        return $this->useCount;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return LsiImportToken
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Dodaje do licznika użyć.
     *
     * @return LsiImportToken
     */
    public function incrementUseCount()
    {
        $this->useCount++;

        return $this;
    }

    /**
     * Czy Token Importu jest przeterminowany.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expireAt < new DateTime();
    }

    public function isValid()
    {
        $tokenExpired = $this->isExpired();
        $tokenUsed = $this->getStatus() === self::SUCCESSFULLY_USED_TOKEN;

        return $tokenExpired || $tokenUsed;
    }

    /**
     * Zmienia status na wykorzystany.
     *
     * @return void
     */
    public function markTokenAsUsed()
    {
        $this->status = self::SUCCESSFULLY_USED_TOKEN;
    }
}
