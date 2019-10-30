<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update\Chain\Operations;

use ParpV1\LdapBundle\Service\AdUser\Update\Chain\ChainOperationInterface;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Services\StatusWnioskuService;
use Doctrine\ORM\EntityManager;
use DateTime;

/**
 * ResourceApplicationFinish
 *
 * Kończy bieg nadawania lub odebrania uprawnienia z wniosku.
 * W przypadku odbierania uprawnień kiedy zmiany zostają wypychane musi
 * być wstawiona data odebrania użytkownikowi tego zasobu.
 */
class ResourceApplicationFinish implements ChainOperationInterface
{
    /**
     * @var StatusWnioskuService
     */
    private $statusWnioskuService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Entry
     */
    private $entry;

    /**
     * @param StatusWnioskuService $statusWnioskuService
     * @param EntityManager $entityManager
     * @param Entry $entry
     */
    public function __construct(StatusWnioskuService $statusWnioskuService, EntityManager $entityManager, Entry $entry)
    {
        $this->statusWnioskuService = $statusWnioskuService;
        $this->entityManager = $entityManager;
        $this->entry = $entry;
    }

    /**
     * Ustawia datę odebrania zasobu przy wniosku na odebranie lub datę aktywacji przy nadaniu.
     *
     * @return void
     */
    public function make(): void
    {
        $entry = $this->entry;
        $statusWnioskuService = $this->statusWnioskuService;
        $entityManager = $this->entityManager;

        if (null !== $entry->getWniosek()) {
            $wniosek = $entry->getWniosek()->getWniosekNadanieOdebranieZasobow();
            foreach ($wniosek->getUserZasoby() as $userZasob) {
                $userZasob->setCzyAktywne(!$wniosek->getOdebranie());
                if ($wniosek->getOdebranie()) {
                    $userZasob->setDataOdebrania(new DateTime());
                }

                $userZasob->setCzyNadane(true);

                $entityManager
                    ->persist($userZasob);
            }

            $statusWnioskuService
                ->setWniosekStatus($wniosek, '11_OPUBLIKOWANY', false);
        }
    }
}
