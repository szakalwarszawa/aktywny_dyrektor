<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use ParpV1\MainBundle\Services\RedmineConnectService;
use ReflectionClass;

/**
 * Nasłuch wyjątków z konsoli.
 */
class ConsoleExceptionListener
{
    /**
     * @var RedmineConnectService
     */
    private $redmineService;

    /**
     * @param RedmineConnectService $redmineService
     */
    public function __construct(RedmineConnectService $redmineService)
    {
        $this->redmineService = $redmineService;
    }

    /**
     * Łapie wyjątek z konsoli i wrzuca do redmine.
     *
     * @param ConsoleExceptionEvent $event
     *
     * @return void
     */
    public function onConsoleException(ConsoleExceptionEvent $event): void
    {
        $exception = $event->getException();
        $exceptionReflectionClass = new ReflectionClass($exception);

        $descriptionElements = [
            sprintf('exception_class: %s', $exceptionReflectionClass->getName()),
            sprintf('command_name: %s', $event->getCommand()->getName()),
            sprintf('line: %d', $exception->getLine()),
            sprintf('exception_file: %s', $exception->getFile()),
        ];

        $exceptionMessage = $exception
            ->getMessage()
        ;
        $this
            ->redmineService
            ->putZgloszenieBeneficjenta(
                'CRON',
                $exceptionMessage,
                implode(', ', $descriptionElements),
                RedmineConnectService::ZGLOSZONE_PRZEZ_SYSTEM_KATEGORIA,
                false
            );
    }
}
