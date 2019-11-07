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

namespace Apisearch\Plugin\RedisQueue\Tests\Functional;

use Apisearch\Plugin\RedisQueue\RedisQueuePluginBundle;
use Clue\React\Block;
use React\Promise;

/**
 * Class RedisQueueTestTrait.
 */
trait RedisQueueTestTrait
{
    /**
     * Decorate bundles.
     *
     * @param array $bundles
     *
     * @return array
     */
    protected static function decorateBundles(array $bundles): array
    {
        $bundles = parent::decorateBundles($bundles);
        $bundles[] = RedisQueuePluginBundle::class;

        return $bundles;
    }

    /**
     * Force all connections to be droped.
     */
    protected function dropConnections()
    {
        $redisClient = $this
            ->get('apisearch_plugin.redis_queue.redis_wrapper')
            ->getClient();

        $promise = $redisClient
            ->client('list')
            ->then(function ($openedClientsList) use ($redisClient) {
                $parts = explode(PHP_EOL, trim($openedClientsList));
                $parts = array_map(function (string $line) {
                    return array_map(function (string $value) {
                        return explode('=', $value);
                    }, explode(' ', $line));
                }, $parts);

                $promises = [];
                foreach ($parts as $part) {
                    $line = [];
                    foreach ($part as $item) {
                        $line[$item[0]] = $item[1];
                    }

                    if ('blpop' === strtolower($line['cmd'])) {
                        $promises[] = $redisClient->client('kill', $line['addr']);
                    }
                }

                return Promise\all($promises);
            });

        Block\await($promise, $this->get('reactphp.event_loop'));
    }
}
