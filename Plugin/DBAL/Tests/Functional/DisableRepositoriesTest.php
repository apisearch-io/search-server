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

use Apisearch\Model\User;
use Apisearch\Plugin\DBAL\DBALPluginBundle;
use Apisearch\Query\Query;
use Apisearch\Server\Domain\ImperativeEvent\FlushInteractions;
use Apisearch\Server\Domain\ImperativeEvent\FlushSearches;
use Apisearch\Server\Domain\ImperativeEvent\FlushUsageLines;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\AppRepository\EmptyTokenRepository;
use Apisearch\Server\Domain\Repository\AppRepository\InMemoryTokenRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\EmptyInteractionRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InMemoryInteractionRepository;
use Apisearch\Server\Domain\Repository\LogRepository\EmptyLogRepository;
use Apisearch\Server\Domain\Repository\LogRepository\InMemoryLogRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\EmptySearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\InMemorySearchesRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\EmptyUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Schema;
use function React\Promise\all;

/**
 * Class DisableRepositoriesTest.
 */
class DisableRepositoriesTest extends ServiceFunctionalTest
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
        $configuration['parameters']['apisearch_server.tokens_repository_enabled'] = false;
        $configuration['parameters']['apisearch_server.interactions_repository_enabled'] = false;
        $configuration['parameters']['apisearch_server.searches_repository_enabled'] = false;
        $configuration['parameters']['apisearch_server.usage_lines_repository_enabled'] = false;
        $configuration['parameters']['apisearch_server.logs_repository_enabled'] = false;

        $configuration['services']['dbal.dbal_plugin_connection_test'] = [
            'alias' => 'dbal.dbal_plugin_connection',
            'public' => true,
        ];

        return $configuration;
    }

    /**
     * Test repositories.
     *
     * @return void
     */
    public function testRepositories(): void
    {
        $this->assertInstanceOf(EmptyTokenRepository::class, $this->get('apisearch_server.tokens_repository_test'));
        $this->assertInstanceOf(EmptyInteractionRepository::class, $this->get('apisearch_server.interactions_repository_test'));
        $this->assertInstanceOf(EmptySearchesRepository::class, $this->get('apisearch_server.searches_repository_test'));
        $this->assertInstanceOf(EmptyUsageRepository::class, $this->get('apisearch_server.usage_lines_repository_test'));
        $this->assertInstanceOf(EmptyLogRepository::class, $this->get('apisearch_server.logs_repository_test'));

        $this->assertFalse($this->has(InMemoryTokenRepository::class));
        $this->assertFalse($this->has(InMemoryUsageRepository::class));
        $this->assertFalse($this->has(InMemoryInteractionRepository::class));
        $this->assertFalse($this->has(InMemorySearchesRepository::class));
        $this->assertFalse($this->has(InMemoryLogRepository::class));
    }

    /**
     * Test if health check has redis.
     *
     * @return void
     */
    public function testCheckHealth(): void
    {
        $this->click('123', 'product~1', 1, null, Origin::createEmpty());
        $this->click('123', 'product~1', 1, null, Origin::createEmpty());
        $this->click('456', 'product~1', 1, null, Origin::createEmpty());
        $this->query(Query::create('hola')->byUser(new User('1')));
        $this->query(Query::createMatchAll()->byUser(new User('1')));
        $this->query(Query::createMatchAll()->byUser(new User('1')));
        $this->putToken($this->createTokenByIdAndAppId('lala'));
        $this->dispatchImperative(new FlushInteractions());
        $this->dispatchImperative(new FlushUsageLines());
        $this->dispatchImperative(new FlushSearches());

        $response = $this->checkHealth();
        $this->assertTrue($response['status']['dbal']);
        $this->assertGreaterThan(0, $response['info']['dbal']['ping_in_microseconds']);
        unset($response['info']['dbal']['ping_in_microseconds']);
        $this->assertEquals([
            'interactions' => 0,
            'usage_lines' => 0,
            'search_lines' => 0,
            'tokens' => 0,
            'logs' => 0,
        ], $response['info']['dbal']);
    }

    /**
     * Reset database.
     *
     * @return void
     */
    public static function resetScenario()
    {
        $mainConnection = static::getStatic('dbal.dbal_plugin_connection_test');
        $indexConfigTableName = static::getParameterStatic('apisearch_plugin.dbal.index_configs_table');
        $metadataTableName = static::getParameterStatic('apisearch_plugin.dbal.metadata_table');

        $promise = all([
            $mainConnection
                ->dropTable($indexConfigTableName)
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                }),
            $mainConnection
                ->dropTable($metadataTableName)
                ->otherwise(function (TableNotFoundException $_) {
                    // Silent pass
                }),
        ])
            ->then(function () use ($mainConnection, $indexConfigTableName, $metadataTableName) {
                return all([
                    $mainConnection->createTable($indexConfigTableName, [
                        'repository_reference_uuid' => 'string',
                        'content' => 'text',
                    ]),

                    (function ($connection, $metadataTableName) {
                        $schema = new Schema();
                        $table = $schema->createTable($metadataTableName);
                        $table->addColumn('repository_reference_uuid', 'string', ['length' => 255]);
                        $table->addColumn('`key`', 'string', ['length' => 15]);
                        $table->addColumn('val', 'text');
                        $table->addColumn('factory', 'string', ['length' => 128, 'default' => null, 'notnull' => false]);

                        return $connection->executeSchema($schema);
                    })($mainConnection, $metadataTableName),
                ]);
            });

        static::await($promise);

        parent::resetScenario();
    }
}
