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

namespace Apisearch\Plugin\Admin\Controller;

use Apisearch\Server\Controller\ControllerWithCommandBus;
use Drift\CommandBus\Bus\CommandBus;

/**
 * Class ControllerWithCommandBusAsGod.
 */
abstract class ControllerWithCommandBusAsGod extends ControllerWithCommandBus
{
    /**
     * @var string
     */
    protected $godToken;

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
