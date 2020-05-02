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
use Apisearch\Plugin\DBAL\Domain\UsageRepository\ChunkUsageRepository;
use Apisearch\Server\Domain\Repository\DiskRepository;
use Apisearch\Server\Domain\Repository\InMemoryRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Schema;
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
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration = parent::decorateConfiguration($configuration);
        $configuration['services'][InMemoryRepository::class] = [
            'class' => DiskRepository::class,
            'arguments' => [
                '/tmp/apisearch.repository',
                '@reactphp.event_loop',
            ],
        ];
        $configuration['services'][DiskRepository::class] = [
            'alias' => InMemoryRepository::class,
            'public' => true,
        ];

        /*
         * This block is already configured by the bundle, but the main test
         * sets manually the value because the default one is an empty
         * implementation. We only overwrite this value again.
         */
        $configuration['services'][UsageRepository::class] = [
            'alias' => ChunkUsageRepository::class,
        ];

        return $configuration;
    }

    /**
     * Reset database.
     */
    public static function resetScenario()
    {
        $mainConnection = static::getStatic('dbal.main_connection');
        $tokensTableName = static::getParameterStatic('apisearch_plugin.dbal.tokens_table');
        $indexConfigTableName = static::getParameterStatic('apisearch_plugin.dbal.index_configs_table');
        $usageTableName = static::getParameterStatic('apisearch_plugin.dbal.usage_lines_table');
        @\unlink('/tmp/apisearch.repository');

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
                }),
            $mainConnection
                ->dropTable($usageTableName)
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                }),
        ])
            ->then(function () use ($mainConnection, $tokensTableName, $indexConfigTableName, $usageTableName) {
                return all([
                    $mainConnection->createTable($tokensTableName, [
                        'token_uuid' => 'string',
                        'app_uuid' => 'string',
                        'content' => 'text',
                    ]),
                    $mainConnection->createTable($indexConfigTableName, [
                        'repository_reference_uuid' => 'string',
                        'content' => 'text',
                    ]),
                    (function ($connection, $usageTableName) {
                        $schema = new Schema();
                        $table = $schema->createTable($usageTableName);
                        $table->addColumn('event', 'string', ['length' => 15]);
                        $table->addColumn('app_uuid', 'string', ['length' => 50]);
                        $table->addColumn('index_uuid', 'string', ['length' => 50]);
                        $table->addColumn('time', 'integer', ['length' => 8]);
                        $table->addColumn('n', 'integer', ['length' => 7]);

                        return $connection->executeSchema($schema);
                    })($mainConnection, $usageTableName),
                ]);
            });

        static::await($promise);

        parent::resetScenario();
    }
}
