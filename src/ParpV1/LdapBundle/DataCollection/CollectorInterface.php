<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\DataCollection;

use ParpV1\LdapBundle\DataCollector\Collector;

/**
 * Interfejs CollectorInterface
 */
interface CollectorInterface
{
    /**
     * Zwraca typ kolekcji.
     *
     * @return string
     */
    public function getRootType(): string;
}
