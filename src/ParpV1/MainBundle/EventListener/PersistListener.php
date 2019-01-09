<?php

namespace ParpV1\MainBundle\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use ParpV1\MainBundle\Entity\Komentarz;
use ParpV1\MainBundle\Entity\Wniosek;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use ParpV1\MainBundle\Entity\HistoriaWersji;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use ParpV1\MainBundle\Entity\WniosekNumer;
use Doctrine\ORM\EntityManager;

/**
 * Klasa PersistListener
 * Nasłuchuje event `prePersist`
 */
class PersistListener
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Metoda wywołana przed wrzuceniem do bazy danych.
     * Sprawdza czy przeprowadzany persist jest powiązany z klasą `Wniosek`.
     * Jeżeli tak, sprawdza czy dany obiekt nie jest ostatecznie zablokowany do edycji.
     *
     * @param LifecycleEventArgs $args
     *
     * @throws AccessDeniedException gdy wniosek jest zablokowany
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->entityManager = $args->getObjectManager();
        $workingObject = $args->getObject();
        $wniosek = null;

        $getWniosekMethodExists = method_exists($workingObject, 'getWniosek');

        if ($getWniosekMethodExists) {
            $wniosek = $workingObject->getWniosek();
        }

        if ($workingObject instanceOf Komentarz) {
            $wniosek = $this->extractWniosekFromKomentarz($workingObject);
        }

        if ($workingObject instanceOf HistoriaWersji) {
            if (false !== strpos($workingObject->getRoute(), 'wnioseknadanieodebraniezasobow')) {
                $wniosek = $this->extractWniosekFromHistoriaWersji($workingObject);
            }
        }

        if ($wniosek !== null) {
            if ($wniosek->getIsBlocked()) {
                throw new AccessDeniedException('Wniosek jest ostatecznie zablokowany.');
            }
        }

        return;
    }


    /**
     * W obiekcie klasy HistoriaWersji zapisany jest wniosek w postaci dwóch kolumn
     * określających klasę - w tym przypadku `WniosekNadanieOdebranieZasobow` oraz id.
     *
     * @param HistoriaWersji $historiaWersji
     *
     * @return Wniosek|null
     */
    private function extractWniosekFromHistoriaWersji(HistoriaWersji $historiaWersji)
    {
        $className = $this->extractClassName(WniosekNadanieOdebranieZasobow::class);

        $entryData = $historiaWersji->getData();
        if (isset($entryData['obiekt'])) {
            if ($className === $entryData['obiekt']) {
                $wniosekNadanieOdebranieZasobow = $this
                    ->entityManager
                    ->getRepository(WniosekNadanieOdebranieZasobow::class)
                    ->findOneById($entryData['obiektId']);

                if (null !== $wniosekNadanieOdebranieZasobow) {
                    return $wniosekNadanieOdebranieZasobow->getWniosek();
                }
            }
        }

        return null;
    }

    /**
     * W obiekcie klasy Komentarz zapisany jest wniosek w postaci dwóch kolumn
     * określających klasę - w tym przypadku `WniosekNadanieOdebranieZasobow` oraz id.
     *
     * @param Komentarz $komentarz
     *
     * @return Wniosek|null
     */
    private function extractWniosekFromKomentarz(Komentarz $komentarz)
    {
        $className = $this->extractClassName(WniosekNadanieOdebranieZasobow::class);

        if ($className === $komentarz->getObiekt()) {
            $wniosekNadanieOdebranieZasobow = $this
                ->entityManager
                ->getRepository(WniosekNadanieOdebranieZasobow::class)
                ->findOneById($komentarz->getObiektId());

            if (null !== $wniosekNadanieOdebranieZasobow) {
                return $wniosekNadanieOdebranieZasobow->getWniosek();
            }
        }

        return null;
    }

    /**
     * Wyciąga z pełnej scieżki do klasy samą jej nazwę.
     *
     * @param string $classPath
     *
     * @return string
     */
    private function extractClassName($classPath)
    {
        $classPathArray = explode('\\', $classPath);

        return end($classPathArray);
    }
}
