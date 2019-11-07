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

/**
 * Class RedisWrapper.
 */
class RedisWrapper
{
    /**
     * @var RedisFactory
     *
     * Redis Factory
     */
    private $redisFactory;

    /**
     * @var RedisConfig
     *
     * Redis config
     */
    private $redisConfig;

    /**
     * RedisWrapper constructor.
     *
     * @param RedisFactory $redisFactory
     * @param RedisConfig  $redisConfig
     */
    public function __construct(
        RedisFactory $redisFactory,
        RedisConfig $redisConfig
    ) {
        $this->redisFactory = $redisFactory;
        $this->redisConfig = $redisConfig;
    }

    /**
     * Get client.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this
            ->redisFactory
            ->create($this->redisConfig);
    }

    /**
     * Get RedisConfig.
     *
     * @return RedisConfig
     */
    public function getRedisConfig(): RedisConfig
    {
        return $this->redisConfig;
    }
}
