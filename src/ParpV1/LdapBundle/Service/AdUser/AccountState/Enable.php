<?php

namespace ParpV1\LdapBundle\Service\AdUser\AccountState;

use ParpV1\LdapBundle\Service\AdUser\AccountState\AccountStateManager;
use ParpV1\LdapBundle\AdUser\AdUser;
use Adldap\Models\Attributes\AccountControl;
use ParpV1\MainBundle\Constants\AdUserConstants;
use ParpV1\LdapBundle\DataCollection\Message\Messages;
use ParpV1\MainBundle\Tool\AdStringTool;
use Adldap\Models\Attributes\DistinguishedName;

/**
 * Klasa Enable
 * Włączenie konta w AD.
 */
final class Enable extends AccountStateManager
{
    /**
     * @see AccountStateManager
     */
    public function __construct(AdUser $adUser, array $baseParameters, bool $isSimulation)
    {
        parent::__construct($adUser, $baseParameters, $isSimulation);
    }

    /**
     * Uruchamia użytkownika na podstawie distinguishedName zawierającego nowe OU.
     *
     * @param string $distinguishedName
     *
     * @return void
     */
    public function saveByDistinguishedName(string $distinguishedName): void
    {
        if (!$this->isSimulation()) {
            $writableUserObject = $this
                ->adUser
                ->getUser(AdUser::FULL_USER_OBJECT)
            ;

            $departmentOu = current(AdStringTool::getValue($distinguishedName, AdStringTool::OU));

            $newDn = new DistinguishedName();
            $newDn
                ->addOu($departmentOu)
            ;

            foreach (explode(',', $this->baseParameters['base_ou']) as $value) {
                $newDn
                    ->addOu($value)
                ;
            }

            foreach (explode(',', $this->baseParameters['base_dn']) as $value) {
                $newDn
                    ->addDc($value)
                ;
            }

            if (!$writableUserObject->move($newDn)) {
                $message = new Messages\ErrorMessage(
                    'Nie udało się przenieść użytkownika do aktywnych w AD',
                    AdUserConstants::WYLACZONE,
                    $this
                        ->adUser
                        ->getUser()
                );

                $this
                    ->responseMessages
                    ->add($message)
                ;
            }


            $this->removeAccountFlag(AccountControl::ACCOUNTDISABLE);
            $writableUserObject = $this
                ->adUser
                ->getUser(AdUser::FULL_USER_OBJECT)
            ;

            $userAccountControlObject = $writableUserObject->getUserAccountControlObject();
            $userAccountControlObject->accountIsNormal();
            $writableUserObject
                ->setAttribute('description', '')
                ->setUserAccountControl($userAccountControlObject)
            ;

            $writableUserObject->save();
        }

        $message = new Messages\SuccessMessage(
            'Konto zostało włączone.',
            AdUserConstants::WYLACZONE,
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
