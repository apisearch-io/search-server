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

namespace Apisearch\Plugin\Elasticsearch\Domain\Middleware;

use Apisearch\Plugin\Elasticsearch\Domain\ElasticaWrapper;
use Apisearch\Server\Domain\Model\HealthCheckData;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\CheckHealth;
use React\Promise\PromiseInterface;

/**
 * Class CheckHealthMiddleware.
 */
class CheckHealthMiddleware implements PluginMiddleware
{
    /**
     * @var ElasticaWrapper
     *
     * Elastica wrapper
     */
    protected $elasticaWrapper;

    /**
     * QueryHandler constructor.
     *
     * @param ElasticaWrapper $elasticaWrapper
     */
    public function __construct(ElasticaWrapper $elasticaWrapper)
    {
        $this->elasticaWrapper = $elasticaWrapper;
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
        return $next($command)
            ->then(function (HealthCheckData $healthCheckData) {
                $from = \microtime(true);
                $healthCheckData->addPromise($this
                    ->elasticaWrapper
                    ->getClusterStatus()
                    ->then(function (array $elasticsearchData) use ($healthCheckData, $from) {
                        $healthCheckData->setPartialHealth(\in_array(\strtolower($elasticsearchData['status']), [
                            'yellow',
                            'green',
                        ]));
                        $to = \microtime(true);
                        $statusInMicroseconds = \intval(($to - $from) * 1000000);

                        $numberOfIndices = \count($elasticsearchData['indices']);
                        unset($elasticsearchData['indices']);
                        $elasticsearchData['number_of_indices'] = $numberOfIndices;
                        $elasticsearchData['ping_in_microseconds'] = $statusInMicroseconds;

                        $healthCheckData->mergeData([
                            'status' => [
                                'elasticsearch' => $elasticsearchData['status'],
                            ],
                            'info' => [
                                'elasticsearch' => $elasticsearchData,
                            ],
                        ]);
                    }));

                return $healthCheckData;
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
