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

use Apisearch\Server\Domain\Event\DomainEventWithRepositoryReference;
use Apisearch\Server\Domain\Event\EventSubscriber;
use React\Promise;
use React\Promise\PromiseInterface;

/**
 * Class InlineEventPublisher.
 */
class InlineEventPublisher implements EventPublisher
{
    /**
     * @var EventSubscriber[]
     *
     * Subscribers
     */
    private $subscribers = [];

    /**
     * Add subscriber.
     *
     * @param EventSubscriber $subscriber
     */
    public function subscribe(EventSubscriber $subscriber)
    {
        $this->subscribers[] = $subscriber;
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
        $promises = [];
        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->shouldHandleEvent($domainEventWithRepositoryReference)) {
                $promises[] = $subscriber->handle($domainEventWithRepositoryReference);
            }
        }

        return Promise\all($promises);
    }
}
