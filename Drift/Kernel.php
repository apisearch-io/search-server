<?php


namespace Drift;

use Drift\HttpKernel\AsyncKernel;
use Mmoreram\SymfonyBundleDependencies\BundleDependenciesResolver;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends AsyncKernel
{
    use MicroKernelTrait;
    use BundleDependenciesResolver;

    public function registerBundles(): iterable
    {
        return $this->getBundleInstances($this, [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Apisearch\Server\ApisearchServerBundle($this),
            new \Apisearch\Plugin\Elastica\ElasticaPluginBundle(),
            new \Apisearch\Server\ApisearchPluginsBundle(),
        ]);
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * @return string
     */
    private function getApplicationLayerDir(): string
    {
        return $this->getProjectDir() . '/Drift';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = $this->getApplicationLayerDir().'/config';
        $container->setParameter('container.dumper.inline_class_loader', true);
        $loader->load($confDir.'/services.yml');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getApplicationLayerDir().'/config';
        $routes->import($confDir.'/routes.yml');
    }
}
