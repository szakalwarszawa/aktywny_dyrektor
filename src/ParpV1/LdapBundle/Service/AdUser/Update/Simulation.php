<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update;

/**
 * Określa tryb wypchnięcia zmian do AD.
 * Domyślnie jest to tryb wypchnięcia (WPROWADZENIA) zmiany. (NIE SYMULACJI)
 * Uruchomienie trybu symulacji wymaga przestawienia parametru na PRAWDĘ.
 */
class Simulation
{
    /**
     * Czy to będzie tylko symulacja wprowdzenia zmian.
     *
     * @param bool
     */
    protected $simulateProcess = false;

    /**
     * Przełączenie flagi odpowiadającej za wykonanie tylko symulacji wypchnięcia.
     * Zmiany nie będą wprowadzone do AD.
     * Musi być wywołane przed akcją `update` bo zmiany grup są od razu wypychane
     * bez konieczności użycia `->save()` na użytkowniku!!
     *
     * @return self
     */
    public function doSimulateProcess(): self
    {
        $this->simulateProcess = true;

        return $this;
    }

    /**
     * Czy uruchomiony jest tryb symulacji.
     *
     * @return bool
     */
    public function isSimulation(): bool
    {
        return $this->simulateProcess;
    }

    /**
     * Przywraca parametr do domyślnego FALSE.
     *
     * @return self
     */
    public function reset(): self
    {
        $this->simulateProcess = false;

        return $this;
    }

    /**
     * Set simulateProcess
     *
     * @param bool $simulateProcess
     *
     * @return self
     */
    public function setSimulateProcess(bool $simulateProcess): self
    {
        $this->simulateProcess = $simulateProcess;

        return $this;
    }
}
