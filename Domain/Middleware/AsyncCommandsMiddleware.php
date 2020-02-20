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

namespace Apisearch\Server\Domain\Middleware;

use Apisearch\Server\Domain\AsynchronousableCommand;
use Apisearch\Server\Domain\CommandEnqueuer\CommandEnqueuer;

/**
 * Class AsyncCommandsMiddleware.
 */
final class AsyncCommandsMiddleware
{
    /**
     * @var CommandEnqueuer
     *
     * Command enqueuer
     */
    private $commandEnqueuer;

    /**
     * AsynchronousCommandIngestor constructor.
     *
     * @param CommandEnqueuer $commandEnqueuer
     */
    public function __construct(CommandEnqueuer $commandEnqueuer)
    {
        $this->commandEnqueuer = $commandEnqueuer;
    }

    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if ($command instanceof AsynchronousableCommand) {
            return $this
                ->commandEnqueuer
                ->enqueueCommand($command);
        }

        return $next($command);
    }
}
