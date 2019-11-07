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

namespace Apisearch\Plugin\RedisQueue\Domain;

use Apisearch\Plugin\Redis\Domain\RedisWrapper;
use Apisearch\Server\Domain\Consumer\ConsumerManager;
use Clue\React\Redis\Client;
use React\Promise;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use RuntimeException;

/**
 * Class RedisQueueConsumerManager.
 */
class RedisQueueConsumerManager extends ConsumerManager
{
    /**
     * @var RedisWrapper
     *
     * Redis wrapper
     */
    private $redisWrapper;

    /**
     * RedisQueueClient constructor.
     *
     * @param array        $queues
     * @param RedisWrapper $redisWrapper
     */
    public function __construct(
        array $queues,
        RedisWrapper $redisWrapper
    ) {
        parent::__construct($queues);
        $this->redisWrapper = $redisWrapper;
    }

    /**
     * Get Client.
     *
     * @return Client
     */
    private function getClient()
    {
        return $this
            ->redisWrapper
            ->getClient();
    }

    /**
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
        return $this
            ->getClient()
            ->rPush(
                $this->queues['queues'][$type],
                json_encode($data)
            );
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
        $queueName = $this->queues['queues'][$type] ?? null;

        if (is_null($queueName)) {
            return new FulfilledPromise(false);
        }

        return $this
            ->getClient()
            ->lLen($queueName);
    }

    /**
     * Reject message. Enqueue it in the original position.
     *
     * @param string $queue
     * @param array  $payload
     *
     * @return PromiseInterface
     */
    public function reject(
        string $queue,
        array $payload
    ) {
        return $this
            ->getClient()
            ->lPush(
                $queue,
                json_encode($payload)
            );
    }

    /**
     * Consume message.
     *
     * @param string $queueName
     *
     * @return PromiseInterface<array>
     */
    public function consume(string $queueName): PromiseInterface
    {
        return $this
            ->getClient()
            ->blPop(
                $this->queues['busy_queues'][$queueName],
                $this->queues['queues'][$queueName],
                0
            )
            ->then(function (array $result) {
                return [$result[0], json_decode($result[1], true)];
            })
            ->then(null, function (RuntimeException $_) use ($queueName) {
                return $this->consume($queueName);
            });
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
    ): PromiseInterface {
        $promises = [];
        foreach ($queues as $queue) {
            $promises[] = $this
                ->getClient()
                ->rPush(
                    $queue,
                    json_encode($value)
                );
        }

        return Promise\all($promises);
    }
}
