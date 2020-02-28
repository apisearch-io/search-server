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

namespace Apisearch\Plugin\DBAL\Tests\Functional;

use Apisearch\Plugin\DBAL\DBALPluginBundle;
use Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * Trait DBALFunctionalTestTrait.
 */
trait DBALFunctionalTestTrait
{
    /**
     * Reset scenario.
     */
    public static function resetScenario()
    {
        $mainConnection = static::getStatic('dbal.main_connection');
        $promises = [
            $mainConnection
                ->dropTable('tokens')
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                }),
            $mainConnection->createTable('tokens', [
                'token_uuid' => 'string',
                'app_uuid' => 'string',
                'content' => 'string',
            ]),
        ];

        static::awaitAll($promises);

        parent::resetScenario();
    }

    /**
     * Decorate bundles.
     *
     * @param array $bundles
     *
     * @return array
     */
    protected static function decorateBundles(array $bundles): array
    {
        $bundles[] = DBALPluginBundle::class;

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
        $configuration['dbal'] = [
            'connections' => [
                'main' => [
                    'driver' => 'sqlite',
                    'user' => 'root',
                    'password' => 'root',
                    'dbname' => ':memory:',
                ],
            ],
        ];

        $configuration['apisearch_plugin_dbal'] = [
            'tokens_table' => 'tokens',
        ];

        return $configuration;
    }
}
