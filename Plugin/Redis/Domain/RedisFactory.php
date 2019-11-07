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

namespace Apisearch\Plugin\Redis\Domain;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class RedisFactory.
 */
class RedisFactory
{
    /**
     * @var client[]
     *
     * Redis instances
     */
    private $redisInstances = [];

    /**
     * @var Factory
     *
     * Redis Async Factory
     */
    private $factory;

    /**
     * AsyncRedisFactory constructor.
     *
     * @param LoopInterface $eventLoop
     */
    public function __construct(LoopInterface $eventLoop)
    {
        $this->factory = new Factory($eventLoop);
    }

    /**
     * Generate new Predis instance.
     *
     * @param RedisConfig $redisConfig
     *
     * @return Client
     */
    public function create(RedisConfig $redisConfig): Client
    {
        $key = md5($redisConfig->serialize());
        if (isset($this->redisInstances[$key])) {
            return $this->redisInstances[$key];
        }

        $this->redisInstances[$key] = $this->createSimple($redisConfig);

        return $this->redisInstances[$key];
    }

    /**
     * Create simple.
     *
     * @param RedisConfig $redisConfig
     *
     * @return Client
     */
    private function createSimple(RedisConfig $redisConfig): Client
    {
        return $this
            ->factory
            ->createLazyClient(rtrim(sprintf(
                '%s:%s/%s',
                $redisConfig->getHost(),
                $redisConfig->getPort(),
                $redisConfig->getDatabase()
            ), '/'));
    }
}
