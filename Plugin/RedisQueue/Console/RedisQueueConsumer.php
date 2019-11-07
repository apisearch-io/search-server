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

use Apisearch\Command\ApisearchCommand;
use Apisearch\Plugin\RedisQueue\Domain\RedisQueueConsumerManager;
use Clue\React\Block;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RedisQueueConsumer.
 */
abstract class RedisQueueConsumer extends ApisearchCommand
{
    /**
     * @var RedisQueueConsumerManager
     *
     * Consumer Manager
     */
    protected $consumerManager;

    /**
     * @var LoopInterface
     *
     * Loop
     */
    protected $loop;

    /**
     * @var int
     *
     * Seconds to wait on busy
     */
    protected $secondsToWaitOnBusy;

    /**
     * @var bool
     *
     * Busy
     */
    protected $busy = false;

    /**
     * RedisQueueConsumer constructor.
     *
     * @param RedisQueueConsumerManager $consumerManager
     * @param LoopInterface             $loop
     * @param int                       $secondsToWaitOnBusy
     */
    public function __construct(
        RedisQueueConsumerManager $consumerManager,
        LoopInterface   $loop,
        int $secondsToWaitOnBusy
    ) {
        parent::__construct();

        $this->consumerManager = $consumerManager;
        $this->loop = $loop;
        $this->secondsToWaitOnBusy = $secondsToWaitOnBusy;
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int|null null or 0 if everything went fine, or an error code
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->startCommand($output);
        $consumerManager = $this->consumerManager;
        $consumerBusyQueueName = $consumerManager->getQueueName($this->getQueueType(), true);

        while (true) {
            $promise = $consumerManager
                ->consume($this->getQueueType())
                ->then(function (array $value) use ($consumerBusyQueueName, $output, $consumerManager) {
                    list($givenQueue, $payload) = $value;

                    /*
                     * Busy queue
                     */
                    if ($givenQueue === $consumerBusyQueueName) {
                        $this->busy = boolval($payload);

                        $this->printInfoMessage($output, 'Redis', ($this->busy ? 'Paused' : 'Resumed').' consumer');

                        return new FulfilledPromise();

                    /*
                     * Regular queue + busy
                     */
                    } elseif ($this->busy) {
                        $output->writeln('Busy channel. Rejecting and waiting '.$this->secondsToWaitOnBusy.' seconds');

                        return $consumerManager
                            ->reject($givenQueue, $payload)
                            ->then(function () {
                                sleep($this->secondsToWaitOnBusy);

                                return new FulfilledPromise();
                            });

                    /*
                     * Regular queue
                     */
                    } else {
                        return $this->consumeMessage(
                            $payload,
                            $output
                        );
                    }
                });

            Block\await($promise, $this->loop);
        }

        return 0;
    }

    /**
     * Get queue type.
     *
     * @return string
     */
    abstract protected function getQueueType(): string;

    /**
     * Consume message.
     *
     * @param array           $message
     * @param OutputInterface $output
     *
     * @return PromiseInterface
     */
    abstract protected function consumeMessage(
        array $message,
        OutputInterface $output
    ): PromiseInterface;
}
