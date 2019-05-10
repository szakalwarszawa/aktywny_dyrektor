<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update\Chain\Operations;

use ParpV1\MainBundle\Services\UprawnieniaService;
use ParpV1\LdapBundle\Service\AdUser\Update\Chain\ChainOperationInterface;
use ParpV1\MainBundle\Entity\Entry;

/**
 * RemoveUserResources
 *
 * Odebranie zasobów użytkownika na podstawie Entry.
 */
class RemoveUserResources implements ChainOperationInterface
{
    /**
     * @var UprawnieniaService
     */
    private $uprawnieniaService;

    /**
     * @var Entry
     */
    private $entry;

    public function __construct(UprawnieniaService $uprawnieniaService, Entry $entry)
    {
        $this->uprawnieniaService = $uprawnieniaService;
        $this->entry = $entry;
    }

    /**
     * Odbiera zasoby użytkownika.
     */
    public function make(): void
    {
        $entry = $this->entry;
        $uprawnieniaService = $this->uprawnieniaService;

        if ($entry->getOdebranieZasobowEntry()) {
            $uprawnieniaService
                ->odbierzZasobyUzytkownikaZEntry($entry->getOdebranieZasobowEntry())
            ;
        }
    }
}
