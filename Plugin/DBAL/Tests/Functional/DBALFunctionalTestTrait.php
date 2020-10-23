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
use Apisearch\Plugin\DBAL\Domain\SearchesRepository\ChunkSearchesRepository;
use Apisearch\Plugin\DBAL\Domain\UsageRepository\ChunkUsageRepository;
use Apisearch\Server\Domain\Repository\DiskRepository;
use Apisearch\Server\Domain\Repository\InMemoryRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
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

        $configuration['services'][SearchesRepository::class] = [
            'alias' => ChunkSearchesRepository::class,
        ];

        return $configuration;
    }

    /**
     * Reset database.
     */
    public static function resetScenario()
    {
        $mainConnection = static::getStatic('dbal.dbal_plugin_connection');
        $tokensTableName = static::getParameterStatic('apisearch_plugin.dbal.tokens_table');
        $indexConfigTableName = static::getParameterStatic('apisearch_plugin.dbal.index_configs_table');
        $usageTableName = static::getParameterStatic('apisearch_plugin.dbal.usage_lines_table');
        $metadataTableName = static::getParameterStatic('apisearch_plugin.dbal.metadata_table');
        $interactionTableName = static::getParameterStatic('apisearch_plugin.dbal.interactions_table');
        $searchesTableName = static::getParameterStatic('apisearch_plugin.dbal.searches_table');
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
            $mainConnection
                ->dropTable($metadataTableName)
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                }),
            $mainConnection
                ->dropTable($interactionTableName)
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                }),
            $mainConnection
                ->dropTable($searchesTableName)
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                }),
        ])
            ->then(function () use ($mainConnection, $tokensTableName, $indexConfigTableName, $usageTableName, $metadataTableName, $interactionTableName, $searchesTableName) {
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

                    (function ($connection, $metadataTableName) {
                        $schema = new Schema();
                        $table = $schema->createTable($metadataTableName);
                        $table->addColumn('repository_reference_uuid', 'string', ['length' => 255]);
                        $table->addColumn('`key`', 'string', ['length' => 15]);
                        $table->addColumn('val', 'text');
                        $table->addColumn('factory', 'string', ['length' => 128, 'default' => null, 'notnull' => false]);

                        return $connection->executeSchema($schema);
                    })($mainConnection, $metadataTableName),

                    (function ($connection, $interactionTableName) {
                        $schema = new Schema();
                        $table = $schema->createTable($interactionTableName);
                        $table->addColumn('user_uuid', 'string', ['length' => 25]);
                        $table->addColumn('app_uuid', 'string', ['length' => 50]);
                        $table->addColumn('index_uuid', 'string', ['length' => 50]);
                        $table->addColumn('item_uuid', 'string', ['length' => 50]);
                        $table->addColumn('position', 'integer', ['length' => 4]);
                        $table->addColumn('ip', 'string', ['length' => 16]);
                        $table->addColumn('host', 'string', ['length' => 50]);
                        $table->addColumn('platform', 'string', ['length' => 25]);
                        $table->addColumn('type', 'string', ['length' => 3]);
                        $table->addColumn('time', 'integer', ['length' => 8]);

                        return $connection->executeSchema($schema);
                    })($mainConnection, $interactionTableName),

                    (function ($connection, $searchesTableName) {
                        $schema = new Schema();
                        $table = $schema->createTable($searchesTableName);
                        $table->addColumn('user_uuid', 'string', ['length' => 25]);
                        $table->addColumn('app_uuid', 'string', ['length' => 50]);
                        $table->addColumn('index_uuid', 'string', ['length' => 50]);
                        $table->addColumn('text', 'string', ['length' => 50]);
                        $table->addColumn('nb_of_results', 'integer', ['length' => 8]);
                        $table->addColumn('with_results', 'boolean');
                        $table->addColumn('ip', 'string', ['length' => 16]);
                        $table->addColumn('host', 'string', ['length' => 50]);
                        $table->addColumn('platform', 'string', ['length' => 25]);
                        $table->addColumn('time', 'integer', ['length' => 8]);

                        return $connection->executeSchema($schema);
                    })($mainConnection, $searchesTableName),
                ]);
            });

        static::await($promise);

        parent::resetScenario();
    }
}
