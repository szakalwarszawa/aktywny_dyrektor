<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Services;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use ParpV1\AuthBundle\Security\ParpUser;

/**
 * Klasa UserService
 */
class UserService
{
    /**
     * @var ParpUser
     */
    private $currentUser;

    /**
     * Publiczny konstruktor
     *
     * @param TokenStorage
     */
    public function __construct(TokenStorage $tokenStorage)
    {
        $this->currentUser = $tokenStorage
            ->getToken()
            ->getUser()
        ;
    }

    /**
     * Zwraca aktualnie zalogowanego użytkownika.
     *
     * @return ParpUser
     */
    public function getCurrentUser(): ParpUser
    {
        return $this->currentUser;
    }
}
