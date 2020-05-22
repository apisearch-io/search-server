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

use Apisearch\Server\Controller\ControllerWithQueryBus;
use Drift\CommandBus\Bus\QueryBus;

/**
 * Class ControllerWithQueryBusAsGod.
 */
abstract class ControllerWithQueryBusAsGod extends ControllerWithQueryBus
{
    /**
     * @var string
     */
    protected $godToken;

    /**
     * @param QueryBus $queryBus
     * @param string   $godToken
     */
    public function __construct(
        QueryBus $queryBus,
        string $godToken
    ) {
        parent::__construct($queryBus);
        $this->godToken = $godToken;
    }
}
