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

namespace Apisearch\Plugin\SearchesMachine;

use Apisearch\Plugin\DBAL\DBALPluginBundle;
use Apisearch\Plugin\SearchesMachine\DependencyInjection\CompilerPass\ClientCompilerPass;
use Apisearch\Plugin\SearchesMachine\DependencyInjection\SearchesMachinePluginExtension;
use Apisearch\Server\ApisearchServerBundle;
use Apisearch\Server\Domain\Plugin\Plugin;
use Drift\Redis\RedisBundle;
use Mmoreram\BaseBundle\BaseBundle;
use Mmoreram\SymfonyBundleDependencies\DependentBundleInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class SearchesMachinePluginBundle.
 */
class SearchesMachinePluginBundle extends BaseBundle implements Plugin, DependentBundleInterface
{
    /**
     * Return all bundle dependencies.
     *
     * Values can be a simple bundle namespace or its instance
     *
     * @param KernelInterface $kernel
     *
     * @return array
     */
    public static function getBundleDependencies(KernelInterface $kernel): array
    {
        return [
            ApisearchServerBundle::class,
            DBALPluginBundle::class,
            RedisBundle::class,
        ];
    }

    /**
     * Returns the bundle's container extension.
     *
     * @return ExtensionInterface|null The container extension
     *
     * @throws \LogicException
     */
    public function getContainerExtension()
    {
        return new SearchesMachinePluginExtension();
    }

    /**
     * Return a CompilerPass instance array.
     *
     * @return CompilerPassInterface[]
     */
    public function getCompilerPasses(): array
    {
        return [
            new ClientCompilerPass(),
        ];
    }

    /**
     * get config files.
     *
     * @return array
     */
    public function getConfigFiles(): array
    {
        return [
            'domain',
        ];
    }

    /**
     * Get plugin name.
     *
     * @return string
     */
    public function getPluginName(): string
    {
        return 'searches_machine';
    }
}
