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

use Drift\CommandBus\Bus\QueryBus;

/**
 * Class ControllerWithQueryBus.
 */
abstract class ControllerWithQueryBus extends BaseController
{
    /**
     * @var QueryBus
     *
     * Query bus
     */
    protected $queryBus;

    /**
     * Controller constructor.
     *
     * @param QueryBus $queryBus
     */
    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }
}
