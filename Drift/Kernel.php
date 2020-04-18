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

namespace Drift;

use Apisearch\Server\Domain\Plugin\PluginWithRoutes;
use Drift\HttpKernel\AsyncKernel;
use Mmoreram\SymfonyBundleDependencies\BundleDependenciesResolver;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Class Kernel.
 */
class Kernel extends AsyncKernel
{
    use MicroKernelTrait;
    use BundleDependenciesResolver;

    /**
     * @return iterable
     */
    public function registerBundles(): iterable
    {
        $bundles = $this->resolveBundles();

        foreach ($bundles as $bundle) {
            yield new $bundle($this);
        }
    }

    /**
     * @return string
     */
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * @return string
     */
    private function getApplicationLayerDir(): string
    {
        return $this->getProjectDir().'/Drift';
    }

    /**
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     *
     * @throws \Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = $this->getApplicationLayerDir().'/config';
        $container->setParameter('container.dumper.inline_class_loader', true);
        $loader->load($confDir.'/services.yml');
    }

    /**
     * @param RouteCollectionBuilder $routes
     *
     * @throws \Symfony\Component\Config\Exception\LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getApplicationLayerDir().'/config';
        $routes->import($confDir.'/routes.yml');

        /**
         * Import enabled bundles routes.
         */
        $bundles = $this->resolveBundles();
        foreach ($bundles as $bundle) {
            if (\is_a($bundle, PluginWithRoutes::class, true)) {
                $routes->import($bundle::getRoutesFile());
            }
        }
    }

    /**
     * Resolve bundles.
     *
     * @return string[]
     */
    public function resolveBundles(): array
    {
        $bundles = require $this->getApplicationLayerDir().'/config/bundles.php';
        $bundles = \array_filter($bundles, function ($envs, $class) {
            return $envs[$this->environment] ?? $envs['all'] ?? false;
        }, ARRAY_FILTER_USE_BOTH);
        $bundles = \array_keys($bundles);

        return $this->resolveAndReturnBundleDependencies($this, $bundles);
    }
}
