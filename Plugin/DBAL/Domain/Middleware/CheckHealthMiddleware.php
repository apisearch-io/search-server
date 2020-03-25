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

namespace Apisearch\Plugin\DBAL\Domain\Middleware;

use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\CheckHealth;
use Drift\DBAL\Connection;
use React\Promise\PromiseInterface;

/**
 * Class CheckHealthMiddleware.
 */
class CheckHealthMiddleware implements PluginMiddleware
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute middleware.
     *
     * @param mixed    $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute(
        $command,
        $next
    ): PromiseInterface {
        return
            $next($command)
                ->then(function ($data) {
                    return $this
                        ->getClientStatus()
                        ->then(function (bool $isHealth) use ($data) {
                            $data['status']['dbal'] = $isHealth;
                            $data['healthy'] = $data['healthy'] && $isHealth;

                            return $data;
                        });
                });
    }

    /**
     * Get client status.
     *
     * @return PromiseInterface<bool>
     */
    private function getClientStatus(): PromiseInterface
    {
        return $this
            ->connection
            ->queryBySQL('SELECT ?', ['.'])
            ->then(function () {
                return true;
            })
            ->otherwise(function (\Exception $e) {
                return false;
            });
    }

    /**
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [
            CheckHealth::class,
        ];
    }
}
