<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\DataCollection\Message\Messages;

use ParpV1\LdapBundle\DataCollection\Message\Message;
use ParpV1\LdapBundle\DataCollection\Message\Constants\Types;

/**
 * ErrorMessage
 */
final class ErrorMessage extends Message
{
    /**
     * @var string
     */
    protected $type = Types::ERROR;
}
