<?php

/*
 * This file is part of the Apisearch Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\Server\Domain\Listener;

use Apisearch\Server\Domain\Event;
use Apisearch\Server\Domain\Event\DomainEvent;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use DateTime;
use DateTimeZone;
use Drift\HttpKernel\Event\DomainEventEnvelope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ImportExportUsageRegister.
 */
class ExportUsageRegister implements EventSubscriberInterface
{
    private UsageRepository $usageRepository;
    private int $numberOfBulkItemsInARequest;

    /**
     * @param UsageRepository $usageRepository
     * @param int             $numberOfBulkItemsInARequest
     */
    public function __construct(
        UsageRepository $usageRepository,
        int $numberOfBulkItemsInARequest
    ) {
        $this->usageRepository = $usageRepository;
        $this->numberOfBulkItemsInARequest = $numberOfBulkItemsInARequest;
    }

    /**
     * Register admin event usage.
     *
     * @param DomainEventEnvelope $domainEventEnvelope
     */
    public function registerImportExportEventUsage(DomainEventEnvelope $domainEventEnvelope)
    {
        $this->registerEventUsage(
            $domainEventEnvelope,
            'admin',
            1
        );
    }

    /**
     * Register event by name.
     *
     * @param DomainEventEnvelope $domainEventEnvelope
     * @param string              $eventName
     * @param int                 $n
     */
    private function registerEventUsage(
        DomainEventEnvelope $domainEventEnvelope,
        string $eventName,
        int $n
    ) {
        /**
         * @var DomainEvent
         */
        $event = $domainEventEnvelope->getDomainEvent();
        $today = new DateTime('now', new DateTimeZone('UTC'));
        $today->setTime(0, 0, 0);

        $this
            ->usageRepository
            ->registerEvent(
                $event->getRepositoryReference(),
                $eventName,
                $today,
                $n
            );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Event\IndexWasExported::class => 'registerImportExportEventUsage',
        ];
    }
}
