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

namespace Apisearch\Server\Domain\CommandBus;

use Apisearch\Server\Domain\AsynchronousableCommand;
use Apisearch\Server\Domain\CommandEnqueuer\CommandEnqueuer;
use League\Tactician\CommandBus;

/**
 * Class AsynchronousCommandBus.
 */
class AsynchronousCommandBus extends CommandBus
{
    /**
     * @var CommandEnqueuer
     *
     * Command enqueuer
     */
    private $commandEnqueuer;

    /**
     * @var CommandBus
     *
     * Command bus
     */
    private $commandBus;

    /**
     * AsynchronousCommandIngestor constructor.
     *
     * @param CommandEnqueuer $commandEnqueuer
     * @param CommandBus      $commandBus
     */
    public function __construct(
        CommandEnqueuer $commandEnqueuer,
        CommandBus $commandBus
    ) {
        parent::__construct([]);
        $this->commandEnqueuer = $commandEnqueuer;
        $this->commandBus = $commandBus;
    }

    /**
     * Executes the given command and optionally returns a value.
     *
     * @param object $command
     *
     * @return mixed
     */
    public function handle($command)
    {
        return $command instanceof AsynchronousableCommand
            ? $this
                ->commandEnqueuer
                ->enqueueCommand($command)
            : $this
                ->commandBus
                ->handle($command);
    }
}
