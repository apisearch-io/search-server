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
use Apisearch\Server\Domain\CommandConsumer\CommandConsumer;
use Apisearch\Server\Domain\Consumer\ConsumerManager;
use Apisearch\Server\Domain\ExclusiveCommand;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RabbitMQCommandsConsumer.
 */
class RabbitMQCommandsConsumer extends RabbitMQConsumer
{
    /**
     * @var CommandConsumer
     *
     * Command consumer
     */
    protected $commandConsumer;

    /**
     * ConsumerCommand constructor.
     *
     * @param RabbitMQChannel $channel
     * @param ConsumerManager $consumerManager
     * @param LoopInterface   $loop
     * @param int             $secondsToWaitOnBusy
     * @param CommandConsumer $commandConsumer
     */
    public function __construct(
        RabbitMQChannel        $channel,
        ConsumerManager $consumerManager,
        LoopInterface $loop,
        int $secondsToWaitOnBusy,
        CommandConsumer $commandConsumer
    ) {
        parent::__construct(
            $channel,
            $consumerManager,
            $loop,
            $secondsToWaitOnBusy
        );

        $this->commandConsumer = $commandConsumer;
    }

    /**
     * Get queue type.
     *
     * @return string
     */
    protected function getQueueType(): string
    {
        return ConsumerManager::COMMAND_CONSUMER_TYPE;
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
        $consumerManager = $this->consumerManager;
        $command = json_decode($message->body, true);
        $commandNamespace = 'Apisearch\Server\Domain\Command\\'.$command['class'];
        $reflectionCommand = new ReflectionClass($commandNamespace);
        $isExclusiveCommand = $reflectionCommand->implementsInterface(ExclusiveCommand::class);
        $promise = new FulfilledPromise();

        if ($isExclusiveCommand) {
            $promise = $promise->then(function () use ($consumerManager) {
                $consumerManager->pauseConsumers([ConsumerManager::COMMAND_CONSUMER_TYPE]);
            });
        }

        $promise = $promise->then(function () use ($output, $command) {
            return $this
                ->commandConsumer
                ->consumeCommand(
                    $output,
                    $command
                );
        });

        $channel->basic_ack($message->delivery_info['delivery_tag']);

        if ($isExclusiveCommand) {
            $promise = $promise->then(function () use ($consumerManager) {
                $consumerManager->resumeConsumers([ConsumerManager::COMMAND_CONSUMER_TYPE]);
            });
        }

        return $promise;
    }
}
