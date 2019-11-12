<?php

namespace ParpV1\LdapBundle\Service\AdUser\AccountState;

use ParpV1\LdapBundle\Service\AdUser\Update\Simulation;
use ParpV1\LdapBundle\AdUser\AdUser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

/**
 * Klasa AccountStateManager
 * Operacje przy włączaniu/wyłączaniu konta.
 */
class AccountStateManager extends Simulation
{
    /**
     * @var AdUser
     */
    protected $adUser;

    /**
     * @var array
     */
    protected $values;

    /**
     * @var array
     */
    protected $baseParameters;

    /**
     * @var ArrayCollection
     */
    protected $responseMessages;

    /**
     * Jeżeli prawda to dodatkowo wyłączany użytkownik będzie miał wyzerowane
     * grupy w AD, jeżeli posiadał INT/EXT to administratorzy tych zasobów zostaną
     * powiadomieni mailem i kilka innch czynności.
     *
     * @var bool
     */
    private $eraseUserPrivileges = false;

    /**
     * Publiczny konstruktor
     *
     * @param AdUser $adUser
     * @param array $baseParameters - parametry podstawowe określone w klasie LdapConnection
     * @param bool $isSimulation
     * @param EntityManager $entityManager
     */
    public function __construct(
        AdUser $adUser,
        array $baseParameters = [],
        bool $isSimulation = false,
        EntityManager $entityManager
    ) {
        $this->adUser = $adUser;
        $this->responseMessages = new ArrayCollection();
        $this->baseParameters = $baseParameters;
        $this->setSimulateProcess($isSimulation);
        $this->entityManager = $entityManager;
    }

    /**
     * Get AdUser
     *
     * @return AdUser
     */
    public function getAdUser(): AdUser
    {
        return $this->adUser;
    }

    /**
     * Zwraca kolekcję odpowiedzi dla użytkownika.
     *
     * @return ArrayCollection
     */
    public function getResponseMessages(): ArrayCollection
    {
        return $this->responseMessages;
    }

    /**
     * Z obiektu AccountControl usuwa wybraną flagę.
     *
     * @param int $flagToRemove
     */
    public function removeAccountFlag(int $flagToRemove): void
    {
        $writableUserObject = $this
            ->adUser
            ->getUser(AdUser::FULL_USER_OBJECT)
        ;
        $writableUserObject->syncRaw();
        $userAccountControlObject = $writableUserObject->getUserAccountControlObject();

        $newFlags = [];
        foreach ($userAccountControlObject->getValues() as $value) {
            if ($value !== $flagToRemove && !in_array($value, $newFlags)) {
                $newFlags[] = $value;
            }
        }

        $userAccountControlObject->setValues($newFlags);
        $writableUserObject->setUserAccountControl($userAccountControlObject);
    }
}
