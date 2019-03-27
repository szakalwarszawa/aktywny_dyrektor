<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\MessageCollector\Message;

use ParpV1\LdapBundle\MessageCollector\Message\Message;
use ParpV1\LdapBundle\MessageCollector\Constants\Types;

/**
 * WarningMessage
 */
final class WarningMessage extends Message
{
    /**
     * @var string
     */
    protected $type = Types::WARNING;
}
