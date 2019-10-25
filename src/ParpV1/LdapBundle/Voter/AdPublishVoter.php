<?php

declare(strict_types=1);

namespace ParpV1\LdapBundle\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\Wniosek;
use ParpV1\AuthBundle\Security\ParpUser;
use ParpV1\MainBundle\Entity\Zasoby;

/**
 * Voter AdPublishVoter
 */
class AdPublishVoter extends Voter
{
    /**
     * @var string
     */
    const PUBLISH_CHANGES = 'publish_changes';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Konstruktor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @see Voter
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::PUBLISH_CHANGES])) {
            return false;
        }

        return true;
    }

    /**
     * @see Voter
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($token->getUser()->hasRole('PARP_ADMIN_REJESTRU_ZASOBOW')) {
            return true;
        }


        if (null === $subject) {
            return false;
        }

        if (!$subject instanceof Wniosek) {
            return false;
        }

        if (!$subject->getStatus()->getFinished()) {
            return false;
        }

        $resourceName = $subject
            ->getWniosekNadanieOdebranieZasobow()
            ->getZasoby()
        ;

        if ($this->isResourceAdministrator($token->getUser(), $resourceName)) {
            return true;
        }

        return false;
    }

    /**
     * Sprawdzenie czy użytkownik jest administratorem podanego zasobu.
     *
     * @param ParpUser $user
     * @param string $resourceName - nazwa zasobu
     *
     * @return bool
     */
    private function isResourceAdministrator(ParpUser $user, string $resourceName): bool
    {
        $username = $user->getUsername();

        $resource = $this
            ->entityManager
            ->getRepository(Zasoby::class)
            ->findOneBy([
                'nazwa' => $resourceName
            ])
        ;

        if (null === $resource) {
            return false;
        }

        $resourceAdmins = array_unique(array_merge(
            explode(',', $resource->getAdministratorZasobu()),
            explode(',', $resource->getAdministratorTechnicznyZasobu())
        ));

        if (in_array($username, $resourceAdmins)) {
            return true;
        }

        return false;
    }
}
