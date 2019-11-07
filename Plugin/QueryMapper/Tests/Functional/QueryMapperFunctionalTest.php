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

namespace Apisearch\Plugin\QueryMapper\Tests\Functional;

use Apisearch\Plugin\QueryMapper\QueryMapperPluginBundle;
use Apisearch\Plugin\QueryMapper\Tests\Functional\Mappers\SimpleQueryMapper;
use Apisearch\Plugin\QueryMapper\Tests\Functional\Mappers\SimpleResultMapper;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class QueryMapperFunctionalTest.
 */
abstract class QueryMapperFunctionalTest extends HttpFunctionalTest
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
        $bundles = parent::decorateBundles($bundles);
        $bundles[] = QueryMapperPluginBundle::class;

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
        $configuration['apisearch_plugin_query_mapper']['query_mappers'] = [
            SimpleQueryMapper::class,
        ];

        $configuration['apisearch_plugin_query_mapper']['result_mappers'] = [
            SimpleResultMapper::class,
        ];

        return $configuration;
    }
}
