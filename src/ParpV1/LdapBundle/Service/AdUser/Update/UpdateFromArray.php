<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Update;

use Adldap\Models\User;
use ParpV1\LdapBundle\Service\LdapFetch;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\LdapBundle\Service\AdUser\ChangeCompareService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Adldap\Models\Group;
use ParpV1\LdapBundle\MessageCollector\Message\InfoMessage;
use ParpV1\LdapBundle\MessageCollector\MessageCollectorInterface;
use ParpV1\LdapBundle\MessageCollector\Collector;
use ParpV1\LdapBundle\MessageCollector\Message\WarningMessage;
use ParpV1\LdapBundle\Update\LdapUpdate;
use ParpV1\MainBundle\Constants\AdUserConstants;

/**
 * UpdateFromArray
 * @todo
 */
final class UpdateFromArray extends LdapUpdate
{
    public function update(array $updateArray): self
    {
        $userLogin = $updateArray[AdUserConstants::LOGIN];
        $adUser = $this
            ->ldapFetch
            ->fetchAdUser($userLogin, SearchBy::LOGIN, false)
        ;

        if (null === $adUser) {
            throw new \Exception('Nie ma takiego użytkownika w AD');
        }

        $changes = $this
            ->changeCompareService
            ->compareByArray($updateArray, $adUser->getUser())
        ;

        die('');

        return $this;
    }
}
