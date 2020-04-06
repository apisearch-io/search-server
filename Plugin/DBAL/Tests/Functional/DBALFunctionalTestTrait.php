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
use function React\Promise\all;

/**
 * Trait DBALFunctionalTestTrait.
 */
trait DBALFunctionalTestTrait
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
        $bundles[] = DBALPluginBundle::class;

        return $bundles;
    }

    /**
     * Reset database.
     */
    public static function resetScenario()
    {
        $mainConnection = static::getStatic('dbal.main_connection');
        $tokensTableName = static::getParameterStatic('apisearch_plugin.dbal.tokens_table');
        $indexConfigTableName = static::getParameterStatic('apisearch_plugin.dbal.index_configs_table');

        $promise = all([
            $mainConnection
                ->dropTable($tokensTableName)
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                }),
            $mainConnection
                ->dropTable($indexConfigTableName)
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                })
        ])
            ->then(function() use ($mainConnection, $tokensTableName, $indexConfigTableName) {
                return all([
                    $mainConnection->createTable($tokensTableName, [
                        'token_uuid' => 'string',
                        'app_uuid' => 'string',
                        'content' => 'text',
                    ]),
                    $mainConnection->createTable($indexConfigTableName, [
                        'repository_reference_uuid' => 'string',
                        'content' => 'text',
                    ])
                ]);
            });

        static::await($promise);

        parent::resetScenario();
    }
}
