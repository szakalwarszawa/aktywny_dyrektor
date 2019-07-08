<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\AdUser;

use Adldap\Models\User;
use ParpV1\LdapBundle\Constants\AllowedToFetchAttributes;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\MainBundle\Tool\AdStringTool;
use ParpV1\LdapBundle\Service\LdapFetch;

/**
 * Klasa AdUser - operacje na RAW obiekcie pobranym z AD.
 */
class AdUser
{
    /**
     * Użytkownik zwrócony jako pełny obiekt - WSZYSTKIE ATRYBUTY
     *
     * @var int
     */
    const FULL_USER_OBJECT = 1;

    /**
     * Użytkownik okrojony do atrybutów określonych w klasie AllowedToFetchAttributes.
     *
     * @var int
     */
    const DEFICIENT_USER_OBJECT = 2;

    /**
     * @var User
     */
    private $adUser = null;

    /**
     * @param User $user
     */
    public function __construct(User $adUser)
    {
        $this->adUser = $adUser;
    }

    /**
     * Alias do getUser z parameterem self::FULL_USER_OBJECT
     *
     * @return User
     */
    public function getWritableUser(): User
    {
        return $this->getUser(self::FULL_USER_OBJECT);
    }

    /**
     * User::SyncRaw alias.
     *
     * @return bool
     */
    public function sync(): bool
    {
        return $this
            ->adUser
            ->syncRaw()
        ;
    }

    /**
     * Zwraca użytkownika z konstruktora.
     * Użytkownik może być zwrócony jako pełny obiekt z AD - FULL_USER_OBJECT
     * lub okrojona tablica zawierająca określone dane (AllowedToFetchAttributes) - DEFICIENT_USER_OBJECT
     * Synchronizowane sa również atrybuty grup, statusu konta i daty wygaśnięcia.
     *
     * @param string $returnType
     *
     * @return User|array|null
     */
    public function getUser($returnType = self::DEFICIENT_USER_OBJECT)
    {
        $adUser = $this->adUser;
        if (null === $adUser) {
            return null;
        }

        if (self::FULL_USER_OBJECT === $returnType) {
            return $adUser;
        }

        $userAttributes = $adUser->getAttributes();
        $allowedToFetchAttributes = AllowedToFetchAttributes::getAll();
        $returnArray = [];
        foreach ($allowedToFetchAttributes as $attributeName) {
            $returnArray[$attributeName] = isset($userAttributes[$attributeName])? current($userAttributes[$attributeName]) : null;
        }

        $returnArray[AdUserConstants::GRUPY_AD] = $this->getUserAdGroups($adUser);
        $returnArray[AdUserConstants::WYLACZONE] = $adUser->isDisabled();
        $returnArray[AdUserConstants::WYGASA] = $adUser->getAccountExpiry();

        return $returnArray;
    }

    /**
     * Zwraca grupy użytkownika jako tablicę.
     *
     * @param User $adUser
     *
     * @return array
     */
    private function getUserAdGroups(User $adUser): array
    {
        $userGroups = $adUser->getMemberOf();

        $tempArray = [];
        foreach ($userGroups as $group) {
            $tempArray[] = AdStringTool::getValue($group, AdStringTool::CN);
        }

        return $tempArray;
    }
}
