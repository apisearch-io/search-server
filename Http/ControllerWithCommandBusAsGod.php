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

/**
 * Class ControllerWithCommandBusAsGod.
 */
abstract class ControllerWithCommandBusAsGod extends ControllerWithCommandBus
{
    private string $godToken;

    /**
     * @param CommandBus $commandBus
     * @param string     $godToken
     */
    public function __construct(
        CommandBus $commandBus,
        string $godToken
    ) {
        parent::__construct($commandBus);
        $this->godToken = $godToken;
    }
}
