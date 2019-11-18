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

use League\Tactician\Middleware;

/**
 * Class EmptyMiddleware.
 */
final class EmptyMiddleware implements Middleware
{
    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        return $next($command);
    }
}
