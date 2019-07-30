<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Service\AdUser\Update\Chain\Operations;

use ParpV1\LdapBundle\Service\AdUser\Update\Chain\ChainOperationInterface;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Services\ParpMailerService;

/**
 * GlpiMessage
 *
 * Wysyłka komunikatu do GLPI.
 */
class GlpiMessage implements ChainOperationInterface
{
    /**
     * @var Entry
     */
    private $entry;

    /**
     * @var ParpMailerService
     */
    private $parpMailer;

    /**
     * @param Entry $entry
     */
    public function __construct(Entry $entry, ParpMailerService $parpMailer)
    {
        $this->entry = $entry;
        $this->parpMailer = $parpMailer;
    }

    /**
     * Wysyła mail do GLPI
     *
     * @return void
     */
    public function make(): void
    {
        $entry = $this->entry;
        if ($entry->isRenaming()) {
            $nameParts = explode(' ', $entry->getCn());
            array_pop($nameParts);
            $mailData = [
                'imie_nazwisko' => $entry->getCn(),
                'nowe_nazwisko' => implode(' ', $nameParts),
                'login' => $entry->getSamaccountname(),
                'odbiorcy' => [ParpMailerService::EMAIL_DO_GLPI],
            ];

            $this
                ->parpMailer
                ->sendEmailByType(ParpMailerService::TEMPLATE_ZMIANA_NAZWISKA, $mailData);
        }
    }
}
