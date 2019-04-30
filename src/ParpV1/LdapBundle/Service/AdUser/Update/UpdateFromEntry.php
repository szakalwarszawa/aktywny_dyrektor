<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update;

use ParpV1\MainBundle\Entity\Entry;
use ParpV1\LdapBundle\Helper\AttributeGetterSetterHelper;
use ParpV1\MainBundle\Constants\AdUserConstants;
use Doctrine\ORM\EntityManager;
use Symfony\Component\VarDumper\VarDumper;
use Adldap\AdldapException;
use ParpV1\LdapBundle\AdUser\AdUser;

/**
 * Klasa wprowadzająca zmiany w AD na podstawie obiektu Entry.
 * Wprowadzenie zmian lub utworzenie nowego użytkownika.
 */
final class UpdateFromEntry extends LdapUpdate
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Wypycha lub symuluje wszystkie oczekujące zmiany.
     *
     * @param bool $isSimulation
     * @param bool $flushChanges
     *
     * @return UpdateFromEntry
     */
    public function publishAllPendingChanges(bool $isSimulation = false, bool $flushChanges = false): UpdateFromEntry
    {
        if ($isSimulation) {
            $this->doSimulateProcess();
        }
        $entityManager = $this->entityManager;

        $pendingEntries = $entityManager
            ->getRepository(Entry::class)
            ->findChangesToImplement()
        ;

        foreach ($pendingEntries as $entry) {
            $this->update($entry, true);
        }

        if (!$this->hasError() && $flushChanges && !$isSimulation) {
            $this
                ->entityManager
                ->flush()
            ;
        }

        return $this;
    }

    /**
     * Aktualizuje użytkownika w AD na podstawie obiektu klasy Entry.
     *
     * @param Entry $entry
     * @param bool $createIfNotExists - tworzy użytkownika jeżeli nie istnieje
     *
     * setDistinguishedName - jest null ponieważ jest generowany dalej
     *      automatycznie na podstawie zmiany departamentu, nie dotyczy nowych użytkowników
     *
     * @return UpdateFromEntry
     */
    public function update(Entry $entry, bool $createIfNotExists = false): UpdateFromEntry
    {
        $userLoginGetter = AttributeGetterSetterHelper::get(AdUserConstants::LOGIN);
        $userLogin = $entry->$userLoginGetter();
        $adUser = $this
            ->ldapFetch
            ->fetchAdUser($userLogin, $this->searchBy, false)
        ;

        if (null === $adUser) {
            if ($createIfNotExists) {
                $this->createNewByEntry($entry);

                return $this;
            }

            throw new AdldapException('Użytkownik nie istnieje w AD.');
        }

        if ($entry->getOdblokowanieKonta()) {
            $this->unblockAccount();
        } else {
            $entry->setDistinguishedName(null);
            $this->keepAccountBlockedUnblocked();
        }

        $changes = $this
            ->changeCompareService
            ->compareByEntry($entry, $adUser->getUser())
        ;

        /**
         * @todo
         * Na podstawie tego czy jest dodatkowy wpis o resecie uprawnień będą zerowane wszystkie uprawnienia w AD.
         * @todo powinna to być zwykła flaga
         */
        if ($entry->getOdebranieZasobowEntry()) {
            $this->doEraseUserGroups();
        } else {
            $this->keepUserGroups();
        }

        $this->pushChangesToAd($changes, $adUser, $entry);

        $entry->setIsImplemented(true);

        if ($entry->getOdebranieZasobowEntry() && !$this->isSimulation()) {
            $this
                ->uprawnieniaService
                ->odbierzZasobyUzytkownikaZEntry($entry->getOdebranieZasobowEntry())
            ;
        }

        return $this;
    }

    /**
     * Tworzy nowego użytkownika na podstawie wpisu klasy Entry.
     *
     * @param Entry $entry
     *
     * @return UpdateFromEntry
     */
    public function createNewByEntry(Entry $entry): UpdateFromEntry
    {
        $newUserModel = $this
            ->ldapCreate
            ->createAdUserModel();

        $adUser = new AdUser($newUserModel);
        $params = $this
            ->changeCompareService
            ->compareByEntry($entry, $adUser->getUser());

        $this->pushNewUserToAd($adUser, $params);

        return $this;
    }
}
