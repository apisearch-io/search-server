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

namespace Apisearch\Server\Domain\Consumer;

use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class ConsumerManager.
 */
class ConsumerManager
{
    /**
     * @var string
     *
     * Command consumer type
     */
    const COMMAND_CONSUMER_TYPE = 'command';

    /**
     * @var string
     *
     * Domain event consumer name
     */
    const DOMAIN_EVENT_CONSUMER_TYPE = 'domain-event';

    /**
     * @var array
     *
     * Queues
     */
    protected $queues;

    /**
     * ConsumerManager constructor.
     *
     * @param array $queues
     */
    public function __construct(array $queues = [])
    {
        $this->queues = $queues;
    }

    /**
     * Declare consumer and return if was ok.
     *
     * @param string $type
     *
     * @return PromiseInterface<bool>
     */
    public function declareConsumer(string $type): PromiseInterface
    {
        return new FulfilledPromise(false);
    }

    /**
     * Declare busy channel and return the queue name if was ok.
     *
     * @param string $type
     *
     * @return PromiseInterface<string|null>
     */
    public function declareBusyChannel(string $type): PromiseInterface
    {
        return new FulfilledPromise(null);
    }

    /**
     * Declare busy channel.
     *
     * @param string $type
     * @param mixed  $data
     *
     * @return PromiseInterface
     */
    public function enqueue(
        string $type,
        $data
    ): PromiseInterface {
        return new FulfilledPromise();
    }

    /**
     * Get queue size.
     *
     * @param string $type
     *
     * @return PromiseInterface<int|null>
     */
    public function getQueueSize(string $type): PromiseInterface
    {
        return new FulfilledPromise();
    }

    /**
     * Pause consumers.
     *
     * @param string[] $types
     *
     * @return PromiseInterface
     */
    public function pauseConsumers(array $types): PromiseInterface
    {
        return $this
            ->sendBooleanToQueues(
                $this->getQueuesByArrayOfTypes($types),
                true
            );
    }

    /**
     * Pause consumers.
     *
     * @param string[] $types
     *
     * @return PromiseInterface
     */
    public function resumeConsumers(array $types): PromiseInterface
    {
        return $this
            ->sendBooleanToQueues(
                $this->getQueuesByArrayOfTypes($types),
                false
            );
    }

    /**
     * Declare consumer.
     *
     * @param string $type
     * @param bool   $busy
     *
     * @return string|null
     */
    public function getQueueName(
        string $type,
        bool $busy
    ): ? string {
        return $this->queues[$busy ? 'busy_queues' : 'queues'][$type] ?? null;
    }

    /**
     * Get queues by array of types.
     *
     * @param array $types
     *
     * @return string[]
     */
    protected function getQueuesByArrayOfTypes(array $types): array
    {
        return array_values(
                array_intersect_key(
                    $this->queues['busy_queues'],
                    array_fill_keys($types, true)
                )
            );
    }

    /**
     * Send to queues a boolean value, given queues.
     *
     * @param string[] $queues
     * @param bool     $value
     *
     * @return PromiseInterface
     */
    protected function sendBooleanToQueues(
        array $queues,
        bool $value
    ) {
        // Implementable
    }
}
