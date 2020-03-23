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

namespace Apisearch\Server\Domain\CommandConsumer;

use Apisearch\Server\Domain\AsynchronousableCommand;
use Apisearch\Server\Domain\Consumer;
use function React\Promise\resolve;
use League\Tactician\CommandBus;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class CommandConsumer.
 */
class CommandConsumer extends Consumer
{
    /**
     * @var CommandBus
     *
     * Command bus
     */
    private $commandBus;

    /**
     * ConsumerCommand constructor.
     *
     * @param CommandBus $commandBus
     */
    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * Persist domain event.
     *
     * @param OutputInterface $output
     * @param array           $data
     *
     * @return PromiseInterface
     */
    public function consumeCommand(
        OutputInterface $output,
        array $data
    ): PromiseInterface {
        $class = 'Apisearch\Server\Domain\Command\\'.$data['class'];
        if (
            !class_exists($class) ||
            !in_array(AsynchronousableCommand::class, class_implements($class))
        ) {
            return resolve();
        }

        $command = $data['class'];
        $from = microtime(true);

        return $this
            ->commandBus
            ->handle($class::fromArray($data))
            ->then(function () {
                return [true, ''];
            }, function (Throwable $throwable) {
                return [false, $throwable->getMessage()];
            })
            ->then(function (array $parts) use ($command, $from, $output) {
                list($success, $message) = $parts;
                $this->logOutput(
                    $output,
                    $command,
                    $success,
                    $message,
                    $from
                );
            });
    }
}
