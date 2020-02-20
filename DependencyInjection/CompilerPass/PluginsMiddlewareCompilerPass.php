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

namespace Apisearch\Server\DependencyInjection\CompilerPass;

use Apisearch\DependencyInjection\CompilerPass\TagCompilerPass;
use Apisearch\Server\Domain\Plugin\PluginMiddlewareCollector;

/**
 * Class PluginsMiddlewareCompilerPass.
 */
class PluginsMiddlewareCompilerPass extends TagCompilerPass
{
    /**
     * Get collector service name.
     *
     * @return string Collector service name
     */
    public function getCollectorServiceName(): string
    {
        return PluginMiddlewareCollector::class;
    }

    /**
     * Get collector method name.
     *
     * @return string Collector method name
     */
    public function getCollectorMethodName(): string
    {
        return 'addPluginMiddleware';
    }

    /**
     * Get tag name.
     *
     * @return string Tag name
     */
    public function getTagName(): string
    {
        return 'apisearch_plugin.middleware';
    }
}
