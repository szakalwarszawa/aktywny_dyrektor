<?php

namespace ParpV1\LdapBundle\Service\AdUser\AccountState;

use ParpV1\LdapBundle\Service\AdUser\AccountState\AccountStateManager;
use ParpV1\LdapBundle\AdUser\AdUser;
use ParpV1\MainBundle\Constants\AdUserConstants;
use UnexpectedValueException;
use Adldap\Models\Attributes\AccountControl;
use Adldap\Models\Attributes\DistinguishedName;
use ParpV1\LdapBundle\DataCollection\Message\Messages;
use Symfony\Component\VarDumper\VarDumper;

final class Disable extends AccountStateManager
{
    /**
     * @see AccountStateManager
     */
    public function __construct(AdUser $adUser, array $baseParameters, bool $isSimulation)
    {
        parent::__construct($adUser, $baseParameters, $isSimulation);
    }

    /**
     * Wyłącza konto na podstawie powodu.
     * W zależności od niego użytkownik zostaje przeniesiony do określonego OU.
     *
     * @param string $disableReason
     */
    public function saveByReason(string $disableReason)
    {
        $baseParameters = $this->baseParameters;
        $newParentDn = null;
        if (AdUserConstants::WYLACZENIE_KONTA_NIEOBECNOSC === $disableReason) {
            $newParentDn = new DistinguishedName(
                implode(',', [
                    $baseParameters['ou_nieobecni'],
                    $baseParameters['base_dn']
                ])
            );

            $moveMessageText = 'Użytkownik został przeniesiony do nieobecnych.';
        }

        if (AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY === $disableReason) {

            $newParentDn = new DistinguishedName(
                implode(',', [
                    $baseParameters['ou_zablokowani'],
                    $baseParameters['base_dn']
                ])
            );

            $moveMessageText = 'Użytkownik został przeniesiony do zablokowanych.';
        }

        if (null === $newParentDn) {
            throw new UnexpectedValueException('Nieobsługiwany powód wyłączenia konta.');
        }

        $this->removeAccountFlag(AccountControl::NORMAL_ACCOUNT);
        if (!$this->isSimulation()) {
            $writableUserObject = $this
                ->adUser
                ->getUser(AdUser::FULL_USER_OBJECT)
            ;
            $beforeMoveDn = $writableUserObject->getDistinguishedName();
            $writableUserObject->move($newParentDn);

            $userAccountControlObject = $writableUserObject->getUserAccountControlObject();
            $userAccountControlObject->accountIsDisabled();
            $writableUserObject
                ->setDescription($disableReason)
                ->setAttribute(AdUserConstants::OPTIONAL_ATTRIBUTE, $beforeMoveDn)
                ->setUserAccountControl($userAccountControlObject)
            ;

            $writableUserObject->save();
        }

        $message = new Messages\SuccessMessage(
            $moveMessageText,
            AdUserConstants::POWOD_WYLACZENIA,
            $this
                ->adUser
                ->getUser()
        );

        $this
            ->responseMessages
            ->add($message)
        ;
    }
}
