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

use Apisearch\Server\Domain\Model\HealthCheckData;
use Apisearch\Server\Domain\Query\CheckHealth;
use Bunny\Channel;
use Bunny\Exception\ClientException;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;

/**
 * Class CheckHealthEventsQueueMiddleware.
 */
class CheckHealthEventsQueueMiddleware implements DiscriminableMiddleware
{
    private ?Channel $channel;
    private string $exchangeName;

    /**
     * @param Channel|null $channel
     * @param string       $exchangeName
     */
    public function __construct(
        ?Channel $channel,
        string $exchangeName
    ) {
        $this->channel = $channel;
        $this->exchangeName = $exchangeName;
    }

    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        return $next($command)
            ->then(function (HealthCheckData $healthCheckData) {
                if ($this->channel instanceof Channel) {
                    $from = \microtime(true);
                    $healthCheckData->addPromise($this
                        ->channel
                        ->exchangeDeclare($this->exchangeName, 'fanout', false)
                        ->then(function ($_) use ($healthCheckData) {
                            $healthCheckData->setPartialHealth(true);
                            $healthCheckData->mergeData([
                                'status' => [
                                    'amqp' => true,
                                ],
                            ]);
                        })
                        ->otherwise(function (ClientException $exception) use ($healthCheckData) {
                            $healthCheckData->setPartialHealth(false);
                            $healthCheckData->mergeData([
                                'status' => [
                                    'amqp' => $exception->getMessage(),
                                ],
                                'info' => [
                                    'amqp' => [
                                        'error' => $exception->getMessage(),
                                    ],
                                ],
                            ]);
                        })
                        ->always(function () use ($healthCheckData, $from) {
                            $to = \microtime(true);
                            $statusInMicroseconds = \intval(($to - $from) * 1000000);

                            $healthCheckData->mergeData([
                                'info' => [
                                    'amqp' => [
                                        'ping_in_microseconds' => $statusInMicroseconds,
                                    ],
                                ],
                            ]);
                        })
                    );
                }

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
