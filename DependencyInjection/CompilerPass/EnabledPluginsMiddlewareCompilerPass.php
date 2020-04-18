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

use Apisearch\Server\Domain\Plugin\Plugin;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class EnabledPluginsMiddlewareCompilerPass.
 */
class EnabledPluginsMiddlewareCompilerPass implements CompilerPassInterface
{
    /**
     * @var KernelInterface
     *
     * Kernel
     */
    private $kernel;

    /**
     * PluginsEnabledMiddlewareCompilerPass constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $plugins = \array_filter(
            $this
                ->kernel
                ->getBundles(),
            function (Bundle $bundle) {
                return $bundle instanceof Plugin;
            }
        );

        /**
         * @var Plugin
         */
        $relationalPlugins = [];
        foreach ($plugins as $plugin) {
            $class = \get_class($plugin);
            $parts = \explode('\\', $class);
            \array_pop($parts);
            $relationalPlugins[$plugin->getPluginName()] = [
                'namespace' => \get_class($plugin),
                'path' => \implode('\\', $parts),
            ];
        }

        $container->setParameter('apisearch_server.enabled_plugins', $relationalPlugins);
    }
}
