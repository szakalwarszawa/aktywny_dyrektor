<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Cache;

use ParpV1\LdapBundle\Cache\CacheHitInterface;
use UnexpectedValueException;

/**
 * Klasa Cache
 * Nakładka na CacheHitInterface dodatkowo serializująca lub odserializująca.
 * Umożliwia wyczyszczenie cache.
 */
class Cache implements CacheHitInterface
{
    /**
     * @var CacheItemPoolInterface|null
     */
    protected $cache = null;

    /**
     * @see CacheHitInterface
     */
    public function getItem(string $key)
    {
        $this->checkCache();

        $cacheItem = $this
            ->cache
            ->getItem($key)
        ;

        if (!$cacheItem->isHit()) {
            return false;
        }

        return unserialize($cacheItem->get());
    }

    /**
     * @see CacheHitInterface
     */
    public function exists(string $key): bool
    {
        $this->checkCache();

        $cacheItem = $this
            ->cache
            ->getItem($key)
        ;

        if (!$cacheItem->isHit()) {
            return false;
        }

        return true;
    }

    /**
     * @see CacheHitInterface
     */
    public function removeItem(string $key): bool
    {
        $this->checkCache();

        $cacheItem = $this
            ->cache
            ->getItem($key)
        ;

        if (!$cacheItem->isHit()) {
            return false;
        }

        $cacheItem->deleteItem($key);

        return true;
    }

    /**
     * @see CacheHitInterface
     */
    public function saveItem(string $key, $value): bool
    {
        $this->checkCache();

        $cacheItem = $this
            ->cache
            ->getItem($key)
        ;

            if (!is_string($value)) {
                $value = serialize($value);
            }

            $cacheItem->set($value);
            $this
                ->cache
                ->save($cacheItem)
            ;

        return true;
    }

    /**
     * Wyczyszczenie całego cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this
            ->cache
            ->clear()
        ;
    }

    /**
     * Sprawdza czy cache został ustawiony w konsturktorze.
     *
     * @return void
     *
     * @throws UnexpectedValueException gdy cache nie jest ustawiony.
     */
    private function checkCache(): void
    {
        $cache = $this->cache;
        if (null === $cache) {
            throw new UnexpectedValueException('Nie ustawiono cache.');
        }
    }
}
