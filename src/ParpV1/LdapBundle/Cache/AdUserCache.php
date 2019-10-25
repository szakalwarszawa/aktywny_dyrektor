<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\Cache;

use ParpV1\LdapBundle\Cache\Cache;
use Psr\Cache\CacheItemPoolInterface;

class AdUserCache extends Cache
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * Publiczny konsturktor
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }
}
