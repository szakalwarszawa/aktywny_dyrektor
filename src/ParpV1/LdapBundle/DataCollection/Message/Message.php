<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\DataCollection\Message;

use ParpV1\LdapBundle\DataCollection\CollectorInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Obiekt wiadomości do użytkownika.
 */
class Message implements CollectorInterface
{
    /**
     * Typ kolekcji.
     */
    protected $rootType = 'message';

        /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string|null
     */
    private $target;

    /**
     * @var ArrayCollection
     */
    public $children;

    /**
     * @param string $message
     * @param string $target
     * @param mixed $null
     */
    public function __construct(string $message = '', string $target = null, $vars = null)
    {
        $this->message = $message;
        $this->target = $target;
        $this->vars = $vars;
        $this->children = new ArrayCollection();
    }

    /**
     * @see MessageInterface
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @see MessageInterface
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set target
     *
     * @param string
     *
     * @return self
     */
    public function setTarget(string $target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @see CollectorInterface
     */
    public function getRootType(): string
    {
        return $this->rootType;
    }

    /**
     * Get $type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get vars
     *
     * @return mixed
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * Set vars
     *
     * @param string $vars
     *
     * @return self
     */
    public function setVars($vars): self
    {
        $this->vars = $vars;

        return $this;
    }
}
