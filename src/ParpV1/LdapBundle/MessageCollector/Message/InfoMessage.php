<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\MessageCollector\Message;

use ParpV1\LdapBundle\MessageCollector\Message\Message;
use ParpV1\LdapBundle\MessageCollector\Constants\Types;

/**
 * InfoMessage
 */
final class InfoMessage extends Message
{
    /**
     * @var string
     */
    protected $type = Types::INFO;
}
