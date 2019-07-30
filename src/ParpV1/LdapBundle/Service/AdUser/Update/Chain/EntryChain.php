<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update\Chain;

use ParpV1\LdapBundle\Service\AdUser\Update\Simulation;
use ParpV1\LdapBundle\Service\AdUser\Update\Chain\Operations\RemoveUserResources;
use ParpV1\MainBundle\Services\UprawnieniaService;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Services\StatusWnioskuService;
use ParpV1\LdapBundle\Service\AdUser\Update\Chain\Operations\ResourceApplicationFinish;
use Doctrine\ORM\EntityManager;
use ParpV1\LdapBundle\Service\AdUser\Update\Chain\Operations\GlpiMessage;
use ParpV1\MainBundle\Services\ParpMailerService;

/**
 * Łańcuch zdarzeń wywoływanych w trakcie wypychania entry.
 * Każde nowe zdarzenie musi zostać dodane w metodzie build.
 * Pierwotnie obsługuje odbieranie wszystkich uprawnień użytkownika
 * i wysyłanie wiadomości do AZ o odebraniu uprawnienia.
 */
class EntryChain extends Simulation
{
    /**
     * Klasy łańcucha wywoływane kolejno.
     *
     * @var array
     */
    private $chainClasses = [];

    /**
     * Czy łańcuch jest gotowy.
     *
     * @var bool
     */
    private $chainReady = false;

    /**
     * @var UprawnieniaService
     */
    private $uprawnieniaService;

    /**
     * @var StatusWnioskuService
     */
    private $statusWnioskuService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ParpMailerService
     */
    private $parpMailer;

    /**
     * Do konstruktora przekazywane są wszystkie parametry potrzebne do poszczególnych operacji.
     *
     * @param UprawnieniaService|null $uprawnieniaService
     * @param StatusWnioskuService|null $statusWnioskuService
     * @param EntityManager|null $entityManager
     * @param ParpMailerService $parpMailer
     */
    public function __construct(
        ?UprawnieniaService $uprawnieniaService = null,
        ?StatusWnioskuService $statusWnioskuService = null,
        ?EntityManager $entityManager = null,
        ?ParpMailerService $parpMailer = null
    ) {
        $this->uprawnieniaService = $uprawnieniaService;
        $this->statusWnioskuService = $statusWnioskuService;
        $this->entityManager = $entityManager;
        $this->parpMailer = $parpMailer;
    }

    /**
     * Zbudowanie łańcucha na podstawie obiektu Entry.
     *
     * @param Entry $entry
     *
     * @return self
     */
    public function build(Entry $entry): self
    {
        $this->chainClasses[] = new RemoveUserResources($this->uprawnieniaService, $entry);
        $this->chainClasses[] = new ResourceApplicationFinish(
            $this->statusWnioskuService,
            $this->entityManager,
            $entry
        );
        $this->chainClasses[] = new GlpiMessage($entry, $this->parpMailer);

        $this->chainReady = true;

        return $this;
    }

    /**
     * Wykonanie wszystkich opracji każdej z klas określonych w metodzie build()
     * Operacje nie zostaną wykonane jeżeli stan symulacji jest TRUE.
     *
     * @return bool
     */
    public function initializeChain(): bool
    {
        if ($this->isChainReady() && !$this->isSimulation()) {
            foreach ($this->chainClasses as $chainClass) {
                if (is_object($chainClass)) {
                    $chainClass->make();
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Czy łańcuch jest gotowy do inicjalizacji.
     *
     * @return bool
     */
    public function isChainReady(): bool
    {
        return $this->chainReady;
    }
}
