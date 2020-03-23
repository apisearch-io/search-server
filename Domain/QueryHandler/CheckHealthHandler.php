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

namespace Apisearch\Server\Domain\QueryHandler;

use Apisearch\Server\Domain\Query\CheckHealth;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class CheckHealthHandler.
 */
class CheckHealthHandler
{
    /**
     * Check the cluster.
     *
     * @param CheckHealth $checkHealth
     *
     * @return PromiseInterface<array>
     */
    public function handle(CheckHealth $checkHealth): PromiseInterface
    {
        return resolve([
            'healthy' => true,
            'status' => [],
            'info' => [],
            'process' => [
                'memory_used' => memory_get_usage(false),
            ],
        ]);
    }
}
