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
use Drift\HttpKernel\Event\DomainEventEnvelope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UsagePusher.
 */
class UsagePusher implements EventSubscriberInterface
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
     * Register event usage.
     *
     * @param DomainEventEnvelope $domainEventEnvelope
     */
    public function registerEventUsage(DomainEventEnvelope $domainEventEnvelope)
    {
        /**
         * @var DomainEvent
         */
        $event = $domainEventEnvelope->getDomainEvent();
        $this
            ->usageRepository
            ->registerEvent(
                $event->getRepositoryReference(),
                $this->getEventName($event),
                new \DateTime(),
                1
            );
    }

    /**
     * Register indexed items N.
     *
     * @param DomainEventEnvelope $domainEventEnvelope
     */
    public function registerIndexedItemsN(DomainEventEnvelope $domainEventEnvelope)
    {
        /**
         * @var Event\ItemsWereIndexed
         */
        $event = $domainEventEnvelope->getDomainEvent();
        $this
            ->usageRepository
            ->registerEvent(
                $event->getRepositoryReference(),
                'itemsN',
                new \DateTime(),
                \count($event->getItemsUUID())
            );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = [
            Event\QueryWasMade::class,

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

        $eventsStructure = [];
        foreach ($events as $event) {
            $eventsStructure[$event] = [
                ['registerEventUsage', 0],
            ];

            if (Event\ItemsWereIndexed::class === $event) {
                $eventsStructure[$event][] = ['registerIndexedItemsN', 0];
            }
        }

        return $eventsStructure;
    }

    /**
     * Get event name.
     *
     * @param object|string $event
     *
     * @return string
     */
    private function getEventName($event): string
    {
        $namespace = \is_object($event) ? \get_class($event) : $event;
        $parts = \explode('\\', $namespace);

        return \strtolower(\end($parts));
    }
}
