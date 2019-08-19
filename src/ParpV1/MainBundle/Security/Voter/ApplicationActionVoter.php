<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Security\Voter;

use ParpV1\MainBundle\Constants\AkcjeWnioskuConstants;
use ParpV1\MainBundle\Entity\Wniosek;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * ApplicationActionVoter
 */
class ApplicationActionVoter extends Voter
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [AkcjeWnioskuConstants::APPLICATION_RESOURCE_REMOVE,])
            && $subject instanceof Wniosek;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case AkcjeWnioskuConstants::APPLICATION_RESOURCE_REMOVE:
                return $this->applicationResourceRemove($subject, $user);
        }

        return false;
    }

    /**
     * Sprawdzenie akcji usunięcia zasobu z wniosku.
     * Kryteria:
     *      - wniosek nie może być zakończony ani ostatecznie zablokowany
     *      - we wniosku musi być minimum 2 zasoby (1 musi zawsze zostać)
     *      - użytkownik musi być edytorem wniosku lub posiadać rolę `PARP_ADMIN_REJESTRU_ZASOBOW`
     *
     * @param Wniosek $application
     * @param UserInterface $user
     *
     * @return bool
     */
    private function applicationResourceRemove(Wniosek $application, UserInterface $user): bool
    {
        if ($application->getStatus()->getFinished()) {
            return false;
        }

        if ($application->getIsBlocked()) {
            return false;
        }

        if (!$application->getWniosekNadanieOdebranieZasobow()) {
            return false;
        }

        if (2 > $application->getWniosekNadanieOdebranieZasobow()->getUserZasoby()->count()) {
            return false;
        }

        if ($user->hasRole('PARP_ADMIN_REJESTRU_ZASOBOW')) {
            return true;
        }

        $applicationEditors = explode(',', $application->getEditornames());

        return in_array($user->getUsername(), $applicationEditors, true);
    }
}
