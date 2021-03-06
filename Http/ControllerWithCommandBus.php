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

namespace Apisearch\Server\Http;

use Drift\CommandBus\Bus\CommandBus;
use React\Promise\PromiseInterface;

/**
 * Class ControllerWithCommandBus.
 */
abstract class ControllerWithCommandBus extends BaseController
{
    private CommandBus $commandBus;

    /**
     * Controller constructor.
     *
     * @param CommandBus $commandBus
     */
    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @param object $command
     *
     * @return PromiseInterface
     */
    protected function execute($command)
    {
        return $this
            ->commandBus
            ->execute($command);
    }
}
