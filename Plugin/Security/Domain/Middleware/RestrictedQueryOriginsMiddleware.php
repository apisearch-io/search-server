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

namespace Apisearch\Plugin\Security\Domain\Middleware;

use Apisearch\Exception\ForbiddenException;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\Query;
use Closure;
use React\Promise\PromiseInterface;
use function React\Promise\reject;

/**
 * Class RestrictedQueryOriginsMiddleware.
 */
class RestrictedQueryOriginsMiddleware extends RestrictedOriginsMiddleware implements PluginMiddleware
{
    /**
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [
            Query::class,
        ];
    }

    /**
     * @param object  $command
     * @param Closure $next
     * @param bool    $isAllowed
     * @param string  $origin
     *
     * @return PromiseInterface
     */
    protected function executeIfIsAllowed(
        $command,
        $next,
        bool $isAllowed,
        string $origin
    ): PromiseInterface {
        return $isAllowed
            ? $next($command)
            : reject(new ForbiddenException());
    }
}
