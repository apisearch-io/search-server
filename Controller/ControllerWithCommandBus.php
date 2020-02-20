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

namespace Apisearch\Server\Controller;

use Drift\CommandBus\Bus\CommandBus;

/**
 * Class ControllerWithCommandBus.
 */
abstract class ControllerWithCommandBus extends BaseController
{
    /**
     * @var CommandBus
     *
     * Message bus
     */
    protected $commandBus;

    /**
     * @var bool
     *
     * Commands asynchronous
     */
    private $commandsAsynchronous;

    /**
     * Controller constructor.
     *
     * @param CommandBus $commandBus
     * @param bool       $commandsAsynchronous
     */
    public function __construct(
        CommandBus $commandBus,
        bool $commandsAsynchronous
    ) {
        $this->commandBus = $commandBus;
        $this->commandsAsynchronous = $commandsAsynchronous;
    }

    /**
     * Get proper OK return code.
     *
     * @return int
     */
    protected function ok(): int
    {
        return $this->commandsAsynchronous
            ? 202
            : 200;
    }

    /**
     * Get proper OK return code.
     *
     * @return int
     */
    protected function created(): int
    {
        return $this->commandsAsynchronous
            ? 202
            : 201;
    }
}
