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

namespace Apisearch\Plugin\SearchesMachine\Tests\Functional;

use Apisearch\Plugin\DBAL\Domain\SearchesRepository\DBALSearchesRepository;
use Apisearch\Plugin\DBAL\Tests\Functional\ScenarioReset;
use Apisearch\Plugin\SearchesMachine\Domain\Repository\RedisSearchesRepository;
use Apisearch\Plugin\SearchesMachine\SearchesMachinePluginBundle;
use Apisearch\Repository\DiskRepository;
use Apisearch\Repository\InMemoryRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\PersistentSearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;
use Clue\React\Redis\Client;

/**
 * Class SearchesMachineFunctionalTest.
 */
abstract class SearchesMachineFunctionalTest extends ServiceFunctionalTest
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
        $bundles[] = SearchesMachinePluginBundle::class;

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

        $configuration['services']['dbal.dbal_plugin_connection_test'] = [
            'alias' => 'dbal.dbal_plugin_connection',
            'public' => true,
        ];

        /*
         * This block is already configured by the bundle, but the main test
         * sets manually the value because the default one is an empty
         * implementation. We only overwrite this value again.
         */

        $configuration['services'][PersistentSearchesRepository::class] = [
            'alias' => RedisSearchesRepository::class,
        ];

        $configuration['services']['redis.searches_machine_client.test'] = [
            'alias' => 'redis.searches_machine_client',
            'public' => 'true',
        ];
        $configuration['services']['dbal_searches_repository.test'] = [
            'alias' => DBALSearchesRepository::class,
            'public' => 'true',
        ];

        return $configuration;
    }

    /**
     * Reset database.
     *
     * @return void
     */
    public static function resetScenario()
    {
        static::await(ScenarioReset::resetScenario(self::$container));
        parent::resetScenario();
    }

    /**
     * @return Client
     */
    protected function getRedisClient(): Client
    {
        return self::get('redis.searches_machine_client.test');
    }

    /**
     * @throws \Exception
     */
    protected function flushRedis(): void
    {
        $this->await($this->getRedisClient()->del($this->getRedisKey()));
    }

    /**
     * @return SearchesRepository
     */
    protected function getDBALSearchesRepository(): SearchesRepository
    {
        return self::get('dbal_searches_repository.test');
    }

    /**
     * @return string
     */
    protected function getRedisKey(): string
    {
        return self::getParameter('apisearch_plugin.searches_machine.redis_key');
    }
}
