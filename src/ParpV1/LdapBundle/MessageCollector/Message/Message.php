<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\MessageCollector\Message;

use ParpV1\LdapBundle\MessageCollector\Message\MessageInterface;
use ParpV1\LdapBundle\MessageCollector\Constants\Types;

/**
 * Message
 */
class Message implements MessageInterface
{
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
    public function __construct(string $message, string $target = null)
    {
        $this->message = $message;
        $this->target = null;
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
     * @see MessageInterface
     */
    public function setTarget(string $target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @see MessageInterface
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @see MessageInterface
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }
}
