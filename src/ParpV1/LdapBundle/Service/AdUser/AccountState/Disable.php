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
use ParpV1\MainBundle\Tool\AdStringTool;
use ParpV1\MainBundle\Services\DictionaryService;

/**
 * Klasa Disable
 * Wyłączenie konta w AD.
 */
final class Disable extends AccountStateManager
{
    /**
     * @var string
     */
    const USER_ABSENT = 'absent';

    /**
     * @var string
     */
    const USER_REMOVED = 'removed';

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
     *
     * @return void
     */
    public function saveByReason(string $disableReason): void
    {
        $baseParameters = $this->baseParameters;
        $newParentDn = null;
        $writableUserObject = $this
            ->adUser
            ->getUser(AdUser::FULL_USER_OBJECT)
        ;

        if (AdUserConstants::WYLACZENIE_KONTA_NIEOBECNOSC === $disableReason) {
            $newParentDn = new DistinguishedName();

            foreach (explode(',', $baseParameters['ou_nieobecni']) as $value) {
                $newParentDn
                    ->addOu($value)
                ;
            }
            foreach (explode(',', $baseParameters['base_dn']) as $value) {
                $newParentDn
                    ->addDc($value)
                ;
            }

            $state = self::USER_ABSENT;
            $moveMessageText = 'Użytkownik został przeniesiony do nieobecnych.';
        }

        if (AdUserConstants::WYLACZENIE_KONTA_ROZWIAZANIE_UMOWY === $disableReason) {
            $newParentDn = new DistinguishedName();

            foreach (explode(',', $baseParameters['ou_zablokowani']) as $value) {
                $newParentDn
                    ->addOu($value)
                ;
            }
            foreach (explode(',', $baseParameters['base_dn']) as $value) {
                $newParentDn
                    ->addDc($value)
                ;
            }

            $state = self::USER_REMOVED;
            $moveMessageText = 'Użytkownik został przeniesiony do zablokowanych.';
        }

        if (null === $newParentDn) {
            throw new UnexpectedValueException('Nieobsługiwany powód wyłączenia konta.');
        }

        $this->removeAccountFlag(AccountControl::NORMAL_ACCOUNT);
        if (!$this->isSimulation()) {
            $beforeMoveDn = $writableUserObject->getDistinguishedName();
            $writableUserObject->move($newParentDn);

            $dictionary = new DictionaryService(__DIR__ . '//Dictionary//');
            $disableReason = $dictionary->get($disableReason);
            $userAccountControlObject = $writableUserObject->getUserAccountControlObject();
            $userAccountControlObject->accountIsDisabled();
            $writableUserObject
                ->setDescription($disableReason)
                ->setAttribute(AdUserConstants::OPTIONAL_ATTRIBUTE, $beforeMoveDn)
                ->setUserAccountControl($userAccountControlObject)
            ;

            if (self::USER_REMOVED === $state) {
                $writableUserObject->setManager(null);
                $writableUserObject->setAttribute(AdUserConstants::OUTLOOK_UKRYCIE_W_KSIAZCE, 'TRUE');
            }

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
