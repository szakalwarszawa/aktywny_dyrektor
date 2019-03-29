<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\DataCollection\Message;

use ParpV1\LdapBundle\DataCollection\CollectorInterface;

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
     * @param string $message
     */
    public function __construct(string $message = '', string $target = null)
    {
        $this->message = $message;
        $this->target = $target;
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
}
