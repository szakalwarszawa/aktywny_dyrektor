<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\DataCollection\Message\Messages;

use ParpV1\LdapBundle\DataCollection\Message\Message;
use ParpV1\LdapBundle\DataCollection\Message\Constants\Types;

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
