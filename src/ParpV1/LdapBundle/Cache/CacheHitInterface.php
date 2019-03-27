<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Cache;

interface CacheHitInterface
{
    /**
     * Zwraca element z cache.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getItem(string $key);

    /**
     * Sprawdza czy istnieje element w cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Usuwa element z cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function removeItem(string $key): bool;

    /**
     * Zapisuje element do cache.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function saveItem(string $key, $value): bool;
}
