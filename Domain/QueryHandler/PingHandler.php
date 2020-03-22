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

use Apisearch\Server\Domain\Query\Ping;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class PingHandler.
 */
class PingHandler
{
    /**
     * Ping.
     *
     * @param Ping $ping
     *
     * @return PromiseInterface<bool>
     */
    public function handle(Ping $ping): PromiseInterface
    {
        return resolve(true);
    }
}
