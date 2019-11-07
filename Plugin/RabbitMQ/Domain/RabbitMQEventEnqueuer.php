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

namespace Apisearch\Plugin\RabbitMQ\Domain;

use Apisearch\Server\Domain\Consumer\ConsumerManager;
use Apisearch\Server\Domain\EventEnqueuer\EventEnqueuer;
use React\Promise\PromiseInterface;

/**
 * Class RabbitMQEventEnqueuer.
 */
class RabbitMQEventEnqueuer implements EventEnqueuer
{
    /**
     * @var ConsumerManager
     *
     * Consumer manager
     */
    private $consumerManager;

    /**
     * RabbitMQEventEnqueuer constructor.
     *
     * @param ConsumerManager $consumerManager
     */
    public function __construct(ConsumerManager $consumerManager)
    {
        $this->consumerManager = $consumerManager;
    }

    /**
     * Enqueue a domain event.
     *
     * @param array $event
     *
     * @return PromiseInterface
     */
    public function enqueueEvent(array $event): PromiseInterface
    {
        return $this
            ->consumerManager
            ->enqueue(ConsumerManager::DOMAIN_EVENT_CONSUMER_TYPE, $event);
    }
}
