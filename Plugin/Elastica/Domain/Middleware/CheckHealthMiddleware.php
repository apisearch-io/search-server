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

namespace Apisearch\Plugin\Elastica\Domain\Middleware;

use Apisearch\Plugin\Elastica\Domain\ElasticaWrapper;
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
        return
            $next($command)
                ->then(function ($data) {
                    return $this
                        ->elasticaWrapper
                        ->getClusterStatus()
                        ->then(function (string $elasticsearchStatus) use ($data) {
                            $data['status']['elasticsearch'] = $elasticsearchStatus;
                            $data['healthy'] = $data['healthy'] && in_array(strtolower($elasticsearchStatus), [
                                    'yellow',
                                    'green',
                                ]);

                            return $data;
                        });
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
