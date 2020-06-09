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
 * Class UsageRegister.
 */
class UsageRegister implements EventSubscriberInterface
{
    /**
     * @var UsageRepository
     */
    private $usageRepository;

    /**
     * @param UsageRepository $usageRepository
     */
    public function __construct(UsageRepository $usageRepository)
    {
        $this->usageRepository = $usageRepository;
    }

    /**
     * Register query event usage.
     *
     * @param DomainEventEnvelope $domainEventEnvelope
     */
    public function registerQueryEventUsage(DomainEventEnvelope $domainEventEnvelope)
    {
        $this->registerEventUsage($domainEventEnvelope, 'query');
    }

    /**
     * Register admin event usage.
     *
     * @param DomainEventEnvelope $domainEventEnvelope
     */
    public function registerAdminEventUsage(DomainEventEnvelope $domainEventEnvelope)
    {
        $this->registerEventUsage($domainEventEnvelope, 'admin');
    }

    /**
     * Register event by name.
     *
     * @param DomainEventEnvelope $domainEventEnvelope
     * @param string              $eventName
     */
    public function registerEventUsage(
        DomainEventEnvelope $domainEventEnvelope,
        string $eventName
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
                $today
            );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $queryEvents = [
            Event\QueryWasMade::class,
        ];

        $eventsStructure = [];
        foreach ($queryEvents as $event) {
            $eventsStructure[$event] = [
                ['registerQueryEventUsage', 0],
            ];
        }

        $adminEvents = [
            Event\ItemsWereIndexed::class,
            Event\ItemsWereUpdated::class,
            Event\ItemsWereDeleted::class,

            Event\IndexWasCreated::class,
            Event\IndexWasConfigured::class,
            Event\IndexWasDeleted::class,
            Event\IndexWasReset::class,

            Event\TokenWasPut::class,
            Event\TokenWasDeleted::class,
            Event\TokensWereDeleted::class,
        ];

        foreach ($adminEvents as $event) {
            $eventsStructure[$event] = [
                ['registerAdminEventUsage', 0],
            ];
        }

        return $eventsStructure;
    }

    /**
     * Get event name.
     *
     * @param object $event
     *
     * @return string
     */
    private function getEventName($event): string
    {
        $parts = \explode('\\', \get_class($event));

        return \strtolower(\end($parts));
    }
}
