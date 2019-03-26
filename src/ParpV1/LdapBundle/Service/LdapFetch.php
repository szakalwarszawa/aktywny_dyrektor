<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service;

use ParpV1\LdapBundle\Constraints\SearchBy;
use Symfony\Component\VarDumper\VarDumper;
use Adldap\Models\User;
use ParpV1\LdapBundle\Constraints\AllowedToFetchAttributes;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\MainBundle\Tool\AdStringTool;


class LdapFetch
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
     * @var LdapConnection
     */
    private $ldapConnection;

    public function __construct(LdapConnection $ldapConnection)
    {
        $this->ldapConnection = $ldapConnection;
    }

    /**
     * Pobiera obiekt użytkownika z AD
     *
     * @param string $username
     * @param string $byAttribute - atrybut według którego jest szukany
     *
     * @return null|array|User
     */
    public function fetchAdUser(
        string $username,
        string $byAttribute = SearchBy::LOGIN,
        int $returnType = self::DEFICIENT_USER_OBJECT
    ) {
        $searchFactory = $this
            ->ldapConnection
            ->getSearchFactory()
        ;

        $searchResult = $searchFactory->findBy($byAttribute, $username);

        if (!$searchResult) {
            return null;
        }

        if (self::FULL_USER_OBJECT === $returnType) {
            return $searchResult;
        }

        $userAttributes = $searchResult->getAttributes();
        $allowedToFetchAttributes = AllowedToFetchAttributes::getAll();
        $returnArray = [];
        foreach ($allowedToFetchAttributes as $attributeName) {
            $returnArray[$attributeName] = isset($userAttributes[$attributeName])? current($userAttributes[$attributeName]) : null;
        }

        $returnArray[AdUserConstants::GRUPY_AD] = $this->getUserAdGroups($searchResult);
        $returnArray[AdUserConstants::WYLACZONE] = $searchResult->isDisabled();


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

    /**
     * A potrzebne jest zwrócenie wszystkich grup?
     */
    public function findAllAdGroups()
    {
        $searchFactory = $this
            ->ldapConnection
            ->getSearchFactory()
        ;
        $adGroups = $searchFactory->read()->where('objectClass', 'group')->get();


       VarDumper::dump($adGroups); die;
    }

}
