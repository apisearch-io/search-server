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

namespace Apisearch\Plugin\Campaign;

use Apisearch\Plugin\Elasticsearch\ElasticsearchPluginBundle;
use Apisearch\Server\ApisearchServerBundle;
use Apisearch\Server\Domain\Plugin\PluginWithRoutes;
use Mmoreram\BaseBundle\SimpleBaseBundle;
use Mmoreram\SymfonyBundleDependencies\DependentBundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CampaignPluginBundle.
 */
class CampaignPluginBundle extends SimpleBaseBundle implements DependentBundleInterface, PluginWithRoutes
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
            ElasticsearchPluginBundle::class,
        ];
    }

    /**
     * Get plugin name.
     *
     * @return string
     */
    public function getPluginName(): string
    {
        return 'campaigns';
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
