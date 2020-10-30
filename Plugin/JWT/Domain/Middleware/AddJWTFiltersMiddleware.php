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

namespace Apisearch\Plugin\JWT\Domain\Middleware;

use Apisearch\Plugin\JWT\Domain\JWTQueryFilter;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\Query;
use React\Promise\PromiseInterface;

/**
 * Class AddJWTFiltersMiddleware.
 */
final class AddJWTFiltersMiddleware implements PluginMiddleware
{
    private JWTQueryFilter $jwtQueryFilter;

    /**
     * @param JWTQueryFilter $jwtQueryFilter
     */
    public function __construct(JWTQueryFilter $jwtQueryFilter)
    {
        $this->jwtQueryFilter = $jwtQueryFilter;
    }

    /**
     * @param mixed    $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute($command, $next): PromiseInterface
    {
        /**
         * @var Query
         */
        $query = $command->getQuery();
        $jwtPayload = $command->getParameters()['jwt'] ?? null;

        if (\is_array($jwtPayload)) {
            $this
                ->jwtQueryFilter
                ->configureQueryByArrayAndJWTPayload(
                    $query,
                    $jwtPayload
                );
        }

        return $next($command);
    }

    /**
     * @return array
     */
    public function onlyHandle(): array
    {
        return [
            Query::class,
        ];
    }
}
