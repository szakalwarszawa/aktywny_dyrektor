<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update\Chain;

/**
 * ChainOperationInterface
 *
 * Z założenia jedna klasa operacji wykonuje tylko jedną operację.
 */
interface ChainOperationInterface
{
    /**
     * Wykonuje metodę klasy.
     *
     * @return void
     */
    public function make(): void;
}
