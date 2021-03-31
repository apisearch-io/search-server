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

        $configuration['services']['dbal.dbal_plugin_connection_test'] = [
            'alias' => 'dbal.dbal_plugin_connection',
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
     *
     * @return void
     */
    public static function resetScenario()
    {
        static::await(ScenarioReset::resetScenario(self::$container));
        parent::resetScenario();
    }
}
