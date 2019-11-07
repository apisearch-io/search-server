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

use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\CheckHealth;
use React\Promise\PromiseInterface;

/**
 * Class CheckHealthPluginsMiddleware.
 */
class CheckHealthPluginsMiddleware implements PluginMiddleware
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
