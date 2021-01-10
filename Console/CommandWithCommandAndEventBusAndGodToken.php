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

namespace Apisearch\Server\Console;

use Clue\React\Block;
use Drift\CommandBus\Bus\InlineCommandBus;
use Drift\EventBus\Bus\EventBus;
use React\EventLoop\LoopInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CommandWithCommandAndEventBusAndGodToken.
 */
abstract class CommandWithCommandAndEventBusAndGodToken extends ApisearchServerCommand
{
    protected EventBus $eventBus;
    protected InlineCommandBus $commandBus;

    /**
     * @param EventBus         $eventBus
     * @param InlineCommandBus $commandBus
     * @param LoopInterface    $loop
     * @param KernelInterface  $kernel
     * @param string           $godToken
     */
    public function __construct(
        EventBus $eventBus,
        InlineCommandBus $commandBus,
        LoopInterface $loop,
        KernelInterface $kernel,
        string $godToken
    ) {
        parent::__construct($loop, $kernel, $godToken);

        $this->eventBus = $eventBus;
        $this->commandBus = $commandBus;
    }

    /**
     * @param object $event
     *
     * @return mixed
     */
    public function dispatchAndWait($event)
    {
        $promise = $this
            ->eventBus
            ->dispatch($event);

        return Block\await($promise, $this->loop);
    }

    /**
     * Execute command.
     *
     * @param object $command
     *
     * @return void
     */
    public function executeAndWait($command): void
    {
        $promise = $this
            ->commandBus
            ->execute($command);

        Block\await($promise, $this->loop);
    }
}
