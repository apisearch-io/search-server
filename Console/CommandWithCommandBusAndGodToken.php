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
use React\EventLoop\LoopInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CommandWithCommandBusAndGodToken.
 */
abstract class CommandWithCommandBusAndGodToken extends ApisearchServerCommand
{
    /**
     * @var InlineCommandBus
     *
     * Command bus
     */
    protected $commandBus;

    /**
     * Controller constructor.
     *
     * @param InlineCommandBus $commandBus
     * @param LoopInterface    $loop
     * @param KernelInterface  $kernel
     * @param string           $godToken
     */
    public function __construct(
        InlineCommandBus $commandBus,
        LoopInterface $loop,
        KernelInterface $kernel,
        string $godToken
    ) {
        parent::__construct($loop, $kernel, $godToken);

        $this->commandBus = $commandBus;
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
