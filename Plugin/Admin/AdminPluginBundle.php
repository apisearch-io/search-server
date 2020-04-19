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

namespace Apisearch\Plugin\Admin;

use Apisearch\Plugin\Admin\DependencyInjection\AdminPluginExtension;
use Apisearch\Server\ApisearchServerBundle;
use Apisearch\Server\Domain\Plugin\PluginWithRoutes;
use Mmoreram\BaseBundle\SimpleBaseBundle;
use Mmoreram\SymfonyBundleDependencies\DependentBundleInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class AdminPluginBundle.
 */
class AdminPluginBundle extends SimpleBaseBundle implements DependentBundleInterface, PluginWithRoutes
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
        return new AdminPluginExtension();
    }

    /**
     * Get plugin name.
     *
     * @return string
     */
    public function getPluginName(): string
    {
        return 'admin';
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
     * {@inheritdoc}
     */
    public static function getRoutesFile(): string
    {
        return __DIR__.'/Resources/config/routes.yml';
    }
}
