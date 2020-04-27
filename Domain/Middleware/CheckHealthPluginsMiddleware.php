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

use Apisearch\Server\Domain\Query\CheckHealth;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use React\Promise\PromiseInterface;

/**
 * Class CheckHealthPluginsMiddleware.
 */
final class CheckHealthPluginsMiddleware implements DiscriminableMiddleware
{
    /**
     * @var string[]
     *
     * Enabled plugins
     */
    private $enabledPlugins;

    /**
     * PluginMiddlewareCollector constructor.
     *
     * @param string[] $enabledPlugins
     */
    public function __construct(array $enabledPlugins)
    {
        $this->enabledPlugins = $enabledPlugins;
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
                    $plugins = [];
                    foreach ($this->enabledPlugins as $enabledPluginName => $enabledPluginConfig) {
                        $plugins[$enabledPluginName] = $enabledPluginConfig['namespace'];
                    }

                    $data['info']['plugins'] = $plugins;

                    return $data;
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
