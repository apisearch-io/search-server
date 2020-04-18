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

namespace Apisearch\Server\Domain\Plugin;

use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use React\Promise\PromiseInterface;

/**
 * Class PluginMiddleware.
 */
class PluginMiddlewareCollector
{
    /**
     * @var PluginMiddleware[]
     *
     * Plugin middleware
     */
    private $pluginMiddlewares = ['_all' => []];

    /**
     * Add plugin middleware.
     *
     * @param PluginMiddleware $pluginMiddleware
     */
    public function addPluginMiddleware(PluginMiddleware $pluginMiddleware)
    {
        if (!$pluginMiddleware instanceof DiscriminableMiddleware) {
            $this->pluginMiddlewares['_all'][] = $pluginMiddleware;

            return;
        }

        $objectsToHandle = $pluginMiddleware->onlyHandle();
        if (empty($objectsToHandle)) {
            $this->pluginMiddlewares['_all'][] = $pluginMiddleware;

            return;
        }

        foreach ($objectsToHandle as $objects) {
            if (!isset($this->pluginMiddlewares[$objects])) {
                $this->pluginMiddlewares[$objects] = [];
            }

            $this->pluginMiddlewares[$objects][] = $pluginMiddleware;
        }
    }

    /**
     * Get PluginMiddlewares.
     *
     * @return PluginMiddleware[]
     */
    public function getPluginMiddlewares(): array
    {
        return $this->pluginMiddlewares;
    }

    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        $lastCallable = $next;
        $middlewares = $this->pluginMiddlewares['_all'];
        foreach ($this->getNamespaceCollectionOfClass($command) as $namespace) {
            if (isset($this->pluginMiddlewares[$namespace])) {
                $middlewares = \array_merge(
                    $middlewares,
                    $this->pluginMiddlewares[$namespace]
                );
            }
        }

        /*
         * @var PluginMiddleware
         */
        foreach ($middlewares as $pluginMiddleware) {
            $lastCallable = function ($command) use ($pluginMiddleware, $lastCallable): PromiseInterface {
                return $pluginMiddleware->execute(
                    $command,
                    $lastCallable
                );
            };
        }

        return $lastCallable($command);
    }

    /**
     * Return class namespace, all parent namespaces and interfaces of a class.
     *
     * @param object $object
     *
     * @return string[]
     */
    private function getNamespaceCollectionOfClass($object): array
    {
        return \array_merge(
            [\get_class($object)],
            \class_parents($object, false),
            \class_implements($object, false)
        );
    }
}
