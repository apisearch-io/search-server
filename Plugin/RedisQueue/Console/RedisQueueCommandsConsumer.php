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
use Apisearch\Server\Domain\CommandConsumer\CommandConsumer;
use Apisearch\Server\Domain\Consumer\ConsumerManager;
use Apisearch\Server\Domain\ExclusiveCommand;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RedisQueueCommandsConsumer.
 */
class RedisQueueCommandsConsumer extends RedisQueueConsumer
{
    /**
     * @var CommandConsumer
     *
     * Command consumer
     */
    protected $commandConsumer;

    /**
     * RedisQueueConsumer constructor.
     *
     * @param RedisQueueConsumerManager $consumerManager
     * @param LoopInterface             $loop
     * @param int                       $secondsToWaitOnBusy
     * @param CommandConsumer           $commandConsumer
     */
    public function __construct(
        RedisQueueConsumerManager $consumerManager,
        LoopInterface   $loop,
        int $secondsToWaitOnBusy,
        CommandConsumer  $commandConsumer
    ) {
        parent::__construct(
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
     * @param array           $message
     * @param OutputInterface $output
     *
     * @return PromiseInterface
     */
    protected function consumeMessage(
        array $message,
        OutputInterface $output
    ): PromiseInterface {
        $commandNamespace = 'Apisearch\Server\Domain\Command\\'.$message['class'];
        $reflectionCommand = new ReflectionClass($commandNamespace);
        $consumerManager = $this->consumerManager;
        $isExclusiveCommand = $reflectionCommand->implementsInterface(ExclusiveCommand::class);
        $promise = new FulfilledPromise();

        if ($isExclusiveCommand) {
            $promise = $promise->then(function () use ($consumerManager) {
                return $consumerManager->pauseConsumers([ConsumerManager::COMMAND_CONSUMER_TYPE]);
            });
        }

        $promise = $promise->then(function () use ($output, $message) {
            return $this
                ->commandConsumer
                ->consumeCommand(
                    $output,
                    $message
                );
        });

        if ($isExclusiveCommand) {
            $promise = $promise->then(function () use ($consumerManager) {
                return $consumerManager->resumeConsumers([ConsumerManager::COMMAND_CONSUMER_TYPE]);
            });
        }

        return $promise;
    }
}
