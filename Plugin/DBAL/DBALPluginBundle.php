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

namespace Apisearch\Plugin\DBAL;

use Apisearch\Plugin\DBAL\DependencyInjection\CompilerPass\ConnectionCompilerPass;
use Apisearch\Plugin\DBAL\DependencyInjection\CompilerPass\DeletedUnusedRepositoriesCompilerPass;
use Apisearch\Plugin\DBAL\DependencyInjection\DBALPluginExtension;
use Apisearch\Server\ApisearchServerBundle;
use Apisearch\Server\Domain\Plugin\Plugin;
use Apisearch\Server\Domain\Plugin\StoragePlugin;
use Mmoreram\BaseBundle\BaseBundle;
use Mmoreram\SymfonyBundleDependencies\DependentBundleInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class DBALPluginBundle.
 */
class DBALPluginBundle extends BaseBundle implements Plugin, StoragePlugin, DependentBundleInterface
{
    /**
     * Builds bundle.
     *
     * @param ContainerBuilder $container Container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DeletedUnusedRepositoriesCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 16);
    }

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
        return new DBALPluginExtension();
    }

    /**
     * Return a CompilerPass instance array.
     *
     * @return CompilerPassInterface[]
     */
    public function getCompilerPasses(): array
    {
        return [
            new ConnectionCompilerPass(),
            new DeletedUnusedRepositoriesCompilerPass(),
        ];
    }

    /**
     * Get plugin name.
     *
     * @return string
     */
    public function getPluginName(): string
    {
        return 'dbal';
    }
}
