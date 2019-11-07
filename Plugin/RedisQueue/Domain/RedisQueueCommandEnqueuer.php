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

use Apisearch\Server\Domain\CommandEnqueuer\CommandEnqueuer;
use Apisearch\Server\Domain\Consumer\ConsumerManager;
use React\Promise\PromiseInterface;

/**
 * Class RedisQueueCommandEnqueuer.
 */
class RedisQueueCommandEnqueuer implements CommandEnqueuer
{
    /**
     * @var RedisQueueConsumerManager
     *
     * Consumer Manager
     */
    protected $consumerManager;

    /**
     * RSQueueEventEnqueuer constructor.
     *
     * @param RedisQueueConsumerManager $consumerManager
     */
    public function __construct(RedisQueueConsumerManager $consumerManager)
    {
        $this->consumerManager = $consumerManager;
    }

    /**
     * Enqueue a command.
     *
     * @param object $command
     *
     * @return PromiseInterface
     */
    public function enqueueCommand($command): PromiseInterface
    {
        $commandAsArray = $command->toArray();
        $commandAsArray['class'] = str_replace('Apisearch\Server\Domain\Command\\', '', get_class($command));

        return $this
            ->consumerManager
            ->enqueue(
                ConsumerManager::COMMAND_CONSUMER_TYPE,
                $commandAsArray
            );
    }
}
