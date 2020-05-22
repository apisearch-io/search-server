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

namespace Apisearch\Plugin\Admin\Tests;

use Apisearch\Plugin\Admin\AdminPluginBundle;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class AdminPluginFunctionalTest.
 */
abstract class AdminPluginFunctionalTest extends CurlFunctionalTest
{
    /**
     * Decorate bundles.
     *
     * @param array $bundles
     *
     * @return array
     */
    protected static function decorateBundles(array $bundles): array
    {
        $bundles[] = AdminPluginBundle::class;

        return $bundles;
    }

    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration = parent::decorateConfiguration($configuration);
        $configuration['services'][UsageRepository::class] = [
            'alias' => InMemoryUsageRepository::class,
        ];

        return $configuration;
    }

    /**
     * Decorate routes.
     *
     * @param array $routes
     *
     * @return array
     */
    protected static function decorateRoutes(array $routes): array
    {
        $routes[] = '@AdminPluginBundle/Resources/config/routes.yml';

        return $routes;
    }
}
