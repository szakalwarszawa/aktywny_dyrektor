<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use ParpV1\LdapBundle\Service\AdUser\ChangeCompareService;
use ParpV1\LdapBundle\Service\LdapFetch;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Services\ParpMailerService;
use Exception;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\Zasoby;

/**
 * EntryListener
 */
class EntryListener
{
    /**
     * @var null|Entry
     */
    private $entry = null;

    /**
     * @var LdapFetch
     */
    private $ldapFetch;

    /**
     * @var ParpMailerService
     */
    private $mailerService;

    /**
     * @var array
     */
    private $neededAttributes = [
        AdUserConstants::DEPARTAMENT_NAZWA,
        AdUserConstants::SEKCJA_NAZWA,
        AdUserConstants::STANOWISKO,
    ];

    /**
     * @param LdapFetch $ldapFetch
     * @param ParpMailerService $mailerService
     */
    public function __construct(LdapFetch $ldapFetch, ParpMailerService $mailerService)
    {
        $this->ldapFetch = $ldapFetch;
        $mailerService->disableFlush();
        $this->mailerService = $mailerService;
    }

    /**
     * Entry pre persist.
     *
     * @param LifecycleEventArgs $args
     *
     * @return void
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if (null !== $this->entry) {
            return;
        }

        if (!$entity instanceof Entry) {
            return;
        }

        if (!$entity->getOdebranieZasobowEntry()) {
            return;
        }

        $fromWhen = $entity->getFromWhen();
        $fromWhen->modify('+1 hour');
        $entity->setFromWhen($fromWhen);
        $this->entry = $entity;
    }

    /**
     * Entry post flush.
     *
     * @param PostFlushEventArgs $args
     *
     * @return void
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->entry) {
            return;
        }
        $changes = $this->getUserChanges($args->getEntityManager());

        if (!$changes->isEmpty()) {
            $this->sendMail($changes, $args->getEntityManager());
        }
    }

    /**
     * Zwraca zmiany jeżeli zaszły wymienione w $this->neededAttributes.
     *
     * @param EntityManagerInterface
     *
     * @return ArrayCollection
     */
    private function getUserChanges(EntityManagerInterface $entityManager): ArrayCollection
    {
        $changeCompareService = new ChangeCompareService($entityManager);

        $entry = $this->entry;
        try {
            $adUser = $this
                ->ldapFetch
                ->fetchAdUser($entry->getSamaccountname())
                ->getUser()
            ;
        } catch (Exception $exception) {
            return new ArrayCollection();
        }

        $changes = $changeCompareService
            ->setSpecifiedAttributes($this->neededAttributes)
            ->compareByEntry($entry, $adUser)
        ;

        return $changes;
    }

    /**
     * Wysyła mail do użytkownika oraz przełożonego z informacją
     * o resecie wszystkich uprawnień oraz ze zmianami na koncie (określonymi
     * w $this->neededAttributes - jeżeli zaszły) oraz o planowanych do
     * usunięcia zasobach użytkownika.
     *
     * @param ArrayCollection $changes
     * @param EntityManagerInterface $entityManager
     *
     * @return void
     */
    private function sendMail(ArrayCollection $changes, EntityManagerInterface $entityManager): void
    {
        $entry = $this->entry;

        $recipients = [
            $entry->getSamaccountname(),
        ];
        try {
            if ($entry->getDepartment() instanceof Departament) {
                $recipients[] = $entry
                    ->getDepartment()
                    ->getDyrektor()
                    ;
            } else {
                $department = $entityManager->getRepository(Departament::class)->findOneBy(['name' => $entry->getDepartment()]);
                $recipients[] = $department->getDyrektor();
            }
        } catch (Exception $exception) {
        }

        $userResources = $entityManager
            ->getRepository(UserZasoby::class)
            ->findZasobyUzytkownika($entry->getSamaccountname())
        ;

        $resourcesWithAdGroup = $entityManager
            ->getRepository(Zasoby::class)
            ->findZasobyIdZGrupaAd()
        ;

        $resourcesWithAdGroup = array_map(function ($element) {
            return $element->getId();
        }, $resourcesWithAdGroup);

        $userResourcesGrouped = [];
        if (isset($userResources['aktywne'])) {
            foreach ($userResources['aktywne'] as $resource) {
                if (in_array($resource['user_zasob']->getZasobId(), $resourcesWithAdGroup, true)) {
                    $userResourcesGrouped['with_ad'][] = $resource['nazwa_zasobu'];

                    continue;
                }

                $userResourcesGrouped['without_ad'][] = $resource['nazwa_zasobu'];
            }
        }

        $mailData = [
            'odbiorcy' => $recipients,
            'imie_nazwisko' => '',
            'login' => $entry->getSamaccountname(),
            'from_when' => $entry->getFromWhen(),
            'removed_groups_ad' => isset($userResourcesGrouped['with_ad']) ? array_unique($userResourcesGrouped['with_ad']) : null,
            'removed_groups' => isset($userResourcesGrouped['without_ad']) ? array_unique($userResourcesGrouped['without_ad']) : null,
            'changes' => $changes,
        ];

        $this->mailerService->sendEmailByType(ParpMailerService::ZMIANY_KADROWE_RESET_UPRAWNIEN, $mailData);
    }
}
