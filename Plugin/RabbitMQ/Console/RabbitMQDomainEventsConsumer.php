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

namespace Apisearch\Plugin\RabbitMQ\Console;

use Apisearch\Plugin\RabbitMQ\Domain\RabbitMQChannel;
use Apisearch\Server\Domain\Consumer\ConsumerManager;
use Apisearch\Server\Domain\EventConsumer\EventConsumer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RabbitMQDomainEventsConsumer.
 */
class RabbitMQDomainEventsConsumer extends RabbitMQConsumer
{
    /**
     * @var EventConsumer
     *
     * Event consumer
     */
    private $eventConsumer;

    /**
     * ConsumerCommand constructor.
     *
     * @param RabbitMQChannel $channel
     * @param ConsumerManager $consumerManager
     * @param LoopInterface   $loop
     * @param int             $secondsToWaitOnBusy
     * @param EventConsumer   $eventConsumer
     */
    public function __construct(
        RabbitMQChannel        $channel,
        ConsumerManager $consumerManager,
        LoopInterface $loop,
        int $secondsToWaitOnBusy,
        EventConsumer $eventConsumer
    ) {
        parent::__construct(
            $channel,
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
     * @param AMQPMessage     $message
     * @param AMQPChannel     $channel
     * @param OutputInterface $output
     *
     * @return PromiseInterface
     */
    protected function consumeMessage(
        AMQPMessage $message,
        AMQPChannel $channel,
        OutputInterface $output
    ): PromiseInterface {
        return $this
            ->eventConsumer
            ->consumeDomainEvent(
                $output,
                json_decode($message->body, true)
            )
            ->then(function () use ($channel, $message) {
                $channel->basic_ack($message->delivery_info['delivery_tag']);
            });
    }
}
