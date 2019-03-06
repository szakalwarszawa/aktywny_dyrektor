<?php

/**
 * Description of RightsServices
 *
 * @author tomasz_bonczak
 */

namespace ParpV1\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use ParpV1\MainBundle\Entity\UserUprawnienia;
use ParpV1\MainBundle\Entity\UserGrupa;
use ParpV1\MainBundle\Services\RedmineConnectService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use ParpV1\MainBundle\Entity\WniosekUtworzenieZasobu;
use ParpV1\MainBundle\Entity\AclAction;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use ParpV1\AuthBundle\Security\ParpUser;
use ReflectionClass;
use ParpV1\MainBundle\Constants\AkcjeWnioskuConstants;
use InvalidArgumentException;
use ParpV1\MainBundle\Entity\Zasoby;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class AccessCheckerService
{

    protected $entityManager;
    protected $container;

    /**
     * @var ParpUser
     */
    private $currentUser;

    public function __construct(EntityManager $OrmEntity, Container $container = null, TokenStorage $tokenStorage)
    {
        $this->currentUser = $tokenStorage->getToken()->getUser();
        $this->entityManager = $OrmEntity;
        $this->container = $container;
        if (PHP_SAPI == 'cli') {
            $this->container->set('request', new Request(), 'request');
        }
    }

    public function checkAccess($actionName)
    {
        $user = $this->currentUser;
        $action = $this
            ->entityManager
            ->getRepository(AclAction::class)
            ->findOneBySkrot($actionName);
        $ret = true;
        if ($action) {
            $ret = false;
            foreach ($action->getRoles() as $r) {
                $ret = $ret || in_array($r->getName(), $user->getRoles());
            }
        }
        if (!$ret) {
            throw new AccessDeniedException("Brak uprawnień.");
        }
    }

    /**
     * Sprawdza czy można przeprowadzić operację
     * na danym wniosku lub powiązanym elemencie.
     *
     * @param mixed $object
     *
     * @return bool
     */
    public function checkWniosekIsBlocked($object, $id = null, $throwException = false)
    {
        $isBlocked = false;
        if (WniosekNadanieOdebranieZasobow::class === $object) {
            $wniosek = $this
                ->entityManager
                ->getRepository(WniosekNadanieOdebranieZasobow::class)
                ->findOneById($id)
            ;
        }

        if ($object instanceof WniosekNadanieOdebranieZasobow) {
            $wniosek = $object;
        }

        if (null !== $wniosek) {
            $isBlocked = $wniosek->getWniosek()->getIsBlocked();
        }

        if ($isBlocked && $throwException) {
            throw new AccessDeniedException('Wniosek jest ostatecznie zablokowany.');
        }

        return $isBlocked;
    }

    /**
     * Sprawdza czy użytkownik może wykonać daną akcję na wniosku lub zasobie.
     * Przekierowuje na podstawie klasy do odpowiedniej metody.
     *
     * @param object $object
     * @param string $action
     *
     * @throws InvalidArgumentException gdy argumentem $object jest nieobsługiwana klasa.
     *
     * @return bool
     */
    public function checkActionWniosek(object $object, string $action): bool
    {
        $this->availableAction($action);
        if ($object instanceof WniosekNadanieOdebranieZasobow) {
            return $this->checkActionWniosekNadanieOdebranieZasobow($object, $action);
        }

        if ($object instanceof WniosekUtworzenieZasobu) {
            return $this->checkActionWniosekUtworzenieZasobu($object, $action);
        }

        if ($object instanceof Zasoby) {
            return $this->checkActionZasoby($object, $action);
        }

        throw new InvalidArgumentException('Obiekt klasy ' . get_class($object) . ' nie jest obsługiwany.');
    }

    /**
     * Sprawdza czy dana akcja jest dozwolona na obiekcie Zasoby.
     *
     * @param Zasoby $zasob
     * @param string $action
     *
     * @return bool
     */
    private function checkActionZasoby(Zasoby $zasob, string $action): bool
    {
        if (AkcjeWnioskuConstants::EDYTUJ === $action) {
            return $this->currentUser->hasRole('PARP_ADMIN_REJESTRU_ZASOBOW');
        }

        if (AkcjeWnioskuConstants::USUN === $action) {
            return $this->currentUser->hasRole('PARP_ADMIN_REJESTRU_ZASOBOW');
        }

        if (AkcjeWnioskuConstants::POKAZ === $action) {
            $userRoles = $this->currentUser->getRoles();
            $allowedRoles = [
                'PARP_ADMIN_REJESTRU_ZASOBOW',
                'PARP_IBI'
            ];

            if (!empty(array_intersect($userRoles, $allowedRoles))) {
                return true;
            }

            $allowedUsers[] = $zasob->getWlascicielZasobu();
            $allowedUsers[] = explode(',', str_replace(' ', '', $zasob->getAdministratorZasobu()));
            $allowedUsers[] = explode(',', str_replace(' ', '', $zasob->getAdministratorTechnicznyZasobu()));
            $allowedUsers[] = explode(',', str_replace(' ', '', $zasob->getPowiernicyWlascicielaZasobu()));

            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($allowedUsers));
            $allowedUsers = iterator_to_array($iterator, false);

            $currentUserName = $this
                ->currentUser
                ->getUsername()
            ;

            return in_array($currentUserName, array_unique($allowedUsers));
        }

        if (AkcjeWnioskuConstants::DODAJ_PLIK) {
            return $this->currentUser->hasRole('PARP_ADMIN_REJESTRU_ZASOBOW');
        }
    }

    /**
     * Sprawdza czy dana akcja jest dozwolona na WniosekUtworzenieZasobu
     *
     * @param WniosekUtworzenieZasobu $wniosekUtworzenieZasobu
     * @param string $action
     *
     * @return bool
     */
    private function checkActionWniosekUtworzenieZasobu(
        WniosekUtworzenieZasobu $wniosekUtworzenieZasobu,
        string $action
    ): bool {
        $wniosek = $wniosekUtworzenieZasobu->getWniosek();
        $status = $wniosek->getStatus()->getNazwaSystemowa();

        if (AkcjeWnioskuConstants::ODBLOKUJ === $action) {
            $lockedByCurrentUser = ($wniosek->getLockedBy() === $this->currentUser->getUsername());
            $notAllowedStatus = [
                '00_TWORZONY_O_ZASOB'
            ];

            return ($lockedByCurrentUser && !in_array($status, $notAllowedStatus));
        }

        if (AkcjeWnioskuConstants::ODRZUC === $action) {
            $notAllowedStatus = [
                '00_TWORZONY_O_ZASOB'
            ];

            return !in_array($status, $notAllowedStatus);
        }

        if (AkcjeWnioskuConstants::ZWROC_DO_POPRAWY === $action) {
            $notAllowedStatus = [
                '00_TWORZONY_O_ZASOB',
                '01_EDYCJA_WNIOSKODAWCA_O_ZASOB'
            ];

            return (!in_array($status, $notAllowedStatus));
        }

        if (AkcjeWnioskuConstants::EDYTUJ === $action) {
            if ($wniosekUtworzenieZasobu->getTypWnioskuWycofanie()) {
                $allowedStatus = [
                    '00_TWORZONY_O_ZASOB',
                    '01_EDYCJA_WNIOSKODAWCA_O_ZASOB'
                ];

                return (in_array($status, $allowedStatus));
            }

            return true;
        }
    }

    /**
     * Metoda do zrobienia w przyszłości. Na razie obsługujemy tylko klasę WniosekUtworzenieZasobu.
     *
     * @todo
     *
     * @param WniosekNadanieOdebranieZasobow $wniosek
     * @param string $action
     *
     * @return bool
     */
    private function checkActionWniosekNadanieOdebranieZasobow(
        WniosekNadanieOdebranieZasobow $wniosek,
        string $action
    ): bool {
        return true;
    }

    /**
     * Sprwadza czy podana akcja na wniosku jest określona w stałych klasy AkcjeWnioskuConstants.
     *
     * @param string $action
     *
     * @throws InvalidArgumentException gdy akcja nie jest zdefiniowana.
     */
    private function availableAction(string $action): void
    {
        $akcjeWnioskuConstants = new ReflectionClass(AkcjeWnioskuConstants::class);
        $wniosekActions = $akcjeWnioskuConstants->getConstants();

        if (!in_array($action, $wniosekActions)) {
            $exceptionMessage = 'Niepoprawna akcja na wniosku. Zdefiniowane: ' .
                implode(', ', $wniosekActions) . '. (Podano: ' . $action . ')';

            throw new InvalidArgumentException($exceptionMessage);
        }
    }
}
