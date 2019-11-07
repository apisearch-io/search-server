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

namespace Apisearch\Plugin\RedisStorage\Domain\Middleware;

use Apisearch\Plugin\Redis\Domain\RedisWrapper;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\CheckHealth;
use React\Promise\PromiseInterface;

/**
 * Class CheckHealthMiddleware.
 */
class CheckHealthMiddleware implements PluginMiddleware
{
    /**
     * @var RedisWrapper
     *
     * Redis wrapper
     */
    protected $redisWrapper;

    /**
     * QueryHandler constructor.
     *
     * @param RedisWrapper $redisWrapper
     */
    public function __construct(RedisWrapper $redisWrapper)
    {
        $this->redisWrapper = $redisWrapper;
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
                ->then(function (array $data) {
                    return $this
                        ->getRedisStatus()
                        ->then(function (bool $isHealth) use ($data) {
                            $data['status']['redis'] = $isHealth;
                            $data['healthy'] = $data['healthy'] && $isHealth;

                            return $data;
                        });
                });
    }

    /**
     * Get redis status.
     *
     * @return PromiseInterface<bool>
     */
    private function getRedisStatus(): PromiseInterface
    {
        return $this
            ->redisWrapper
            ->getClient()
            ->ping()
            ->then(function ($pong) {
                return in_array($pong, ['PONG', '+PONG']);
            }, function (\Exception $e) {
                return false;
            });
    }

    /**
     * Events subscribed namespace. Can refer to specific class namespace, any
     * parent class or any interface.
     *
     * By returning an empty array, means coupled to all.
     *
     * @return string[]
     */
    public function getSubscribedCommands(): array
    {
        return [
            CheckHealth::class,
        ];
    }
}
