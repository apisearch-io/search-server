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

namespace Apisearch\Plugin\Redis\Domain\Middleware;

use Apisearch\Server\Domain\Model\HealthCheckData;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\CheckHealth;
use Clue\React\Redis\Client;
use React\Promise\PromiseInterface;

/**
 * Class HealthCheckMiddleware.
 */
abstract class HealthCheckMiddleware implements PluginMiddleware
{
    private Client $redisClient;

    /**
     * @param Client $redisClient
     */
    public function __construct(Client $redisClient)
    {
        $this->redisClient = $redisClient;
    }

    /**
     * @return string
     */
    abstract protected function getSuffix(): string;

    /**
     * @param mixed    $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute($command, $next): PromiseInterface
    {
        return $next($command)
            ->then(function (HealthCheckData $healthCheckData) {
                $from = \microtime(true);
                $healthCheckData->addPromise($this
                    ->redisClient
                    ->ping()
                    ->then(function (bool $pong) use ($healthCheckData, $from) {
                        $index = "redis_{$this->getSuffix()}";
                        $healthCheckData->setPartialHealth(true);
                        $to = \microtime(true);
                        $statusInMicroseconds = \intval(($to - $from) * 1000000);

                        $healthCheckData->mergeData([
                            'status' => [
                                $index => true,
                            ],
                            'info' => [
                                $index => [
                                    'ping_in_microseconds' => $statusInMicroseconds,
                                ],
                            ],
                        ]);
                    }));

                return $healthCheckData;
            });
    }

    /**
     * @return array
     */
    public function onlyHandle(): array
    {
        return [
            CheckHealth::class,
        ];
    }
}
