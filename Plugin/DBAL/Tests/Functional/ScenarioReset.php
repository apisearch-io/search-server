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

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Schema;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class ScenarioReset.
 */
class ScenarioReset
{
    /**
     * @param Container $container
     *
     * @return PromiseInterface
     */
    public static function resetScenario(Container $container): PromiseInterface
    {
        $mainConnection = $container->get('dbal.dbal_plugin_connection_test');
        $tokensTableName = $container->getParameter('apisearch_plugin.dbal.tokens_table');
        $indexConfigTableName = $container->getParameter('apisearch_plugin.dbal.index_configs_table');
        $usageTableName = $container->getParameter('apisearch_plugin.dbal.usage_lines_table');
        $metadataTableName = $container->getParameter('apisearch_plugin.dbal.metadata_table');
        $interactionTableName = $container->getParameter('apisearch_plugin.dbal.interactions_table');
        $searchesTableName = $container->getParameter('apisearch_plugin.dbal.searches_table');
        $logTableName = $container->getParameter('apisearch_plugin.dbal.logs_table');

        if (\file_exists('/tmp/apisearch.repository')) {
            \unlink('/tmp/apisearch.repository');
        }

        return all([
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
            $mainConnection
                ->dropTable($logTableName)
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                }),
        ])
            ->then(function () use ($mainConnection, $tokensTableName, $indexConfigTableName, $usageTableName, $metadataTableName, $interactionTableName, $searchesTableName, $logTableName) {
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
                        $table->addColumn('context', 'string', ['length' => 25, 'notnull' => false]);
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

                    (function ($connection, $logsTableName) {
                        $schema = new Schema();
                        $table = $schema->createTable($logsTableName);
                        $table->addColumn('app_uuid', 'string', ['length' => 50]);
                        $table->addColumn('index_uuid', 'string', ['length' => 50]);
                        $table->addColumn('time', 'integer', ['length' => 8]);
                        $table->addColumn('n', 'integer', ['length' => 6]);
                        $table->addColumn('type', 'string', ['length' => 30]);
                        $table->addColumn('params', 'string', ['length' => 255]);

                        return $connection->executeSchema($schema);
                    })($mainConnection, $logTableName),
                ]);
            });
    }
}
