<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\DataCollection\Change\Changes;

use ParpV1\LdapBundle\DataCollection\Change\Constants\Types;
use ParpV1\LdapBundle\DataCollection\Change\Change;

/**
 * AdUserChange
 */
final class AdUserChange extends Change
{
    /**
     * @var string
     */
    protected $type = Types::AD_USER;
}
