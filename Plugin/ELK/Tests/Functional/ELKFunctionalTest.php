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

namespace Apisearch\Plugin\ELK\Tests\Functional;

use Apisearch\Plugin\ELK\ELKPluginBundle;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class ELKFunctionalTest.
 */
abstract class ELKFunctionalTest extends ServiceFunctionalTest
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
        $bundles[] = ELKPluginBundle::class;

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
        $configuration['redis'] = [
            'clients' => [
                'main' => [
                    'host' => $_ENV['REDIS_HOST'],
                ],
            ],
        ];

        $configuration['apisearch_plugin_elk'] = [
            'redis_client' => 'main',
        ];

        $configuration['services']['redis.main_client_test'] = [
            'alias' => 'redis.main_client',
            'public' => true,
        ];

        return $configuration;
    }
}
