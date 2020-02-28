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

namespace Apisearch\Plugin\Security\Tests\Functional;

use Apisearch\Plugin\Security\SecurityPluginBundle;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * File header placeholder.
 */
abstract class SecurityFunctionalTest extends HttpFunctionalTest
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
        $bundles[] = SecurityPluginBundle::class;

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

        $configuration['apisearch_plugin_security'] = [
            'redis_client' => 'main',
        ];

        return $configuration;
    }
}
