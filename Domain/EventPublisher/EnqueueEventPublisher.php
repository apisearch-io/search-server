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

namespace Apisearch\Server\Domain\EventPublisher;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Server\Domain\Event\DomainEventWithRepositoryReference;
use Apisearch\Server\Domain\EventEnqueuer\EventEnqueuer;
use React\Promise\PromiseInterface;

/**
 * Class EnqueueEventPublisher.
 */
class EnqueueEventPublisher implements EventPublisher
{
    /**
     * @var EventEnqueuer
     *
     * Event enqueuer
     */
    private $eventEnqueuer;

    /**
     * DomainEventsMiddleware constructor.
     *
     * @param EventEnqueuer $eventEnqueuer
     */
    public function __construct(EventEnqueuer $eventEnqueuer)
    {
        $this->eventEnqueuer = $eventEnqueuer;
    }

    /**
     * Publish event.
     *
     * @param DomainEventWithRepositoryReference $domainEventWithRepositoryReference
     *
     * @return PromiseInterface
     */
    public function publish(DomainEventWithRepositoryReference $domainEventWithRepositoryReference): PromiseInterface
    {
        $repositoryReference = $domainEventWithRepositoryReference->getRepositoryReference();
        $domainEvent = $domainEventWithRepositoryReference->getDomainEvent();
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        return $this
            ->eventEnqueuer
            ->enqueueEvent(
                [
                    'app_uuid' => $appUUID instanceof AppUUID
                        ? $appUUID->toArray()
                        : null,
                    'index_uuid' => $indexUUID instanceof IndexUUID
                        ? $indexUUID->toArray()
                        : null,
                    'time_cost' => $domainEventWithRepositoryReference->getTimeCost(),
                    'event' => $domainEvent->toArray(),
                ]
            );
    }
}
