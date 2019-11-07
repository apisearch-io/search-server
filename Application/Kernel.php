<?php


namespace Apisearch\Server\Application;

use Mmoreram\SymfonyBundleDependencies\BundleDependenciesResolver;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\AsyncKernel;
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

    private function getApplicationLayerDir(): string
    {
        return $this->getProjectDir() . '/Application';
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
