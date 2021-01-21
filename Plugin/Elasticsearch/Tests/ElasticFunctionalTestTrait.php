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

namespace Apisearch\Plugin\Elasticsearch\Tests;

use Apisearch\Plugin\Elasticsearch\ElasticsearchPluginBundle;

/**
 * Trait ElasticFunctionalTestTrait.
 */
trait ElasticFunctionalTestTrait
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws \RuntimeException unable to start the application
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        $config = static::getElasticsearchConfig();
        if (\array_key_exists('host', $config)) {
            $_ENV['ELASTICSEARCH_HOST'] = $config['host'];
        }

        if (\array_key_exists('version', $config)) {
            $_ENV['ELASTICSEARCH_VERSION'] = $config['version'];
        }

        parent::setUpBeforeClass();
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
        $bundles[] = ElasticsearchPluginBundle::class;

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
        if (!empty(static::getElasticsearchConfig())) {
            $configuration['elasticsearch_plugin'] = static::getElasticsearchConfig();
        }

        return $configuration;
    }

    /**
     * Get elasticsearch config.
     *
     * @return array
     */
    protected static function getElasticsearchConfig(): array
    {
        return [];
    }
}
