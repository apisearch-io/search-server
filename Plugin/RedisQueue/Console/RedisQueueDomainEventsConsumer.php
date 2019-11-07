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

namespace Apisearch\Plugin\RedisQueue\Console;

use Apisearch\Plugin\RedisQueue\Domain\RedisQueueConsumerManager;
use Apisearch\Server\Domain\Consumer\ConsumerManager;
use Apisearch\Server\Domain\EventConsumer\EventConsumer;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RedisQueueDomainEventsConsumer.
 */
class RedisQueueDomainEventsConsumer extends RedisQueueConsumer
{
    /**
     * @var EventConsumer
     *
     * Event consumer
     */
    protected $eventConsumer;

    /**
     * RedisQueueConsumer constructor.
     *
     * @param RedisQueueConsumerManager $consumerManager
     * @param LoopInterface             $loop
     * @param int                       $secondsToWaitOnBusy
     * @param EventConsumer             $eventConsumer
     */
    public function __construct(
        RedisQueueConsumerManager $consumerManager,
        LoopInterface   $loop,
        int $secondsToWaitOnBusy,
        EventConsumer  $eventConsumer
    ) {
        parent::__construct(
            $consumerManager,
            $loop,
            $secondsToWaitOnBusy
        );

        $this->eventConsumer = $eventConsumer;
    }

    /**
     * Get queue type.
     *
     * @return string
     */
    protected function getQueueType(): string
    {
        return ConsumerManager::DOMAIN_EVENT_CONSUMER_TYPE;
    }

    /**
     * Consume message.
     *
     * @param array           $message
     * @param OutputInterface $output
     *
     * @return PromiseInterface
     */
    protected function consumeMessage(
        array $message,
        OutputInterface $output
    ): PromiseInterface {
        return $this
            ->eventConsumer
            ->consumeDomainEvent(
                $output,
                $message
            );
    }
}
