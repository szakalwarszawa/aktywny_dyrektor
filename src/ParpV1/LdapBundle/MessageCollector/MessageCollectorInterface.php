<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\MessageCollector;

use ParpV1\LdapBundle\MessageCollector\Collector;

/**
 * Interfejs MessageCollectorInterface
 */
interface MessageCollectorInterface
{
    /**
     * Set Collector
     * Setter jest potrzebny dla wspólnego kolektora.
     * Np. tworzymy jedną instancję kolektora którą ustawiamy do różnych metod.
     *
     * @param Collector $collector
     *
     * @return void
     */
    public function setCollector(Collector $collector): void;

    /**
     * Get Collector
     *
     * @return Collector
     */
    public function getCollector(): Collector;
}
