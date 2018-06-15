<?php

namespace ParpV1\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use ParpV1\MainBundle\Entity\Zasoby;

class ZasobyService
{
    protected $entityManager;

    public function __construct(EntityManager $entityManager, Container $container)
    {
        $this->entityManager = $entityManager;
        $this->container = $container;
    }

    /**
     * Dostęp do zasobu specjalnego mają określone role/grupy/użytkownicy
     *
     * @param Zasob $zasob
     *
     * @return bool
     *
     */
    public function zasobSpecjalnyDostep($zasob, $uzytkownik)
    {
        $bialaLista = array_unique(
            array_merge(
                explode(',', $zasob['wlascicielZasobu']),
                explode(',', $zasob['administratorZasobu']),
                explode(',', $zasob['administratorTechnicznyZasobu']),
                explode(',', $zasob['powiernicyWlascicielaZasobu'])
            )
        );

        if (in_array($uzytkownik->getUsername(), $bialaLista)) {
            return true;
        }

        if (in_array('PARP_ZASOBY_SPECJALNE', $uzytkownik->getRoles())) {
            return true;
        }

        if (in_array('PARP_ADMIN_REJESTRU_ZASOBOW', $uzytkownik->getRoles())) {
            return true;
        }

        return false;
    }

    public function findZasobyDlaUsera($uzytkownik)
    {
        $zasoby = $this
                ->entityManager
                ->getRepository(Zasoby::class)
                ->findListaZasobow(true);

        $zasobyPrzefiltrowane = array();
        foreach ($zasoby as $zasob) {
            if (true === $zasob['zasobSpecjalny']) {
                if (true === $this->zasobSpecjalnyDostep($zasob, $uzytkownik)) {
                    $zasobyPrzefiltrowane[$zasob['id']] = $zasob['nazwa'];
                }
            } elseif(true !== $zasob['zasobSpecjalny']) {
                $zasobyPrzefiltrowane[$zasob['id']] = $zasob['nazwa'];
            }
        }

        return $zasobyPrzefiltrowane;
    }
}
