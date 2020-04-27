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

namespace Apisearch\Plugin\StaticTokens\Tests\Functional;

use Apisearch\Model\ItemUUID;
use Apisearch\Plugin\StaticTokens\StaticTokensPluginBundle;
use Apisearch\Query\Query;
use Apisearch\Server\Domain\Endpoints;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class StaticTokensFunctionalTest.
 */
abstract class StaticTokensFunctionalTest extends HttpFunctionalTest
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
        $bundles[] = StaticTokensPluginBundle::class;

        return $bundles;
    }

    /**
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration['apisearch_plugin_static_tokens']['tokens'] = [
            'blablabla' => [
                'uuid' => 'blablabla',
                'app_uuid' => self::$appId,
            ],
            'onlyindex' => [
                'uuid' => 'onlyindex',
                'app_uuid' => self::$appId,
                'indices' => [
                    self::$index,
                ],
            ],
            'onlyaddtoken' => [
                'uuid' => 'onlyaddtoken',
                'app_uuid' => self::$appId,
                'endpoints' => Endpoints::queryOnly(),
            ],
            'base_filtered_token' => [
                'uuid' => 'base_filtered_token',
                'app_uuid' => self::$appId,
                'metadata' => [
                    'base_query' => Query::createByUUIDs([
                        ItemUUID::createByComposedUUID('2~product'),
                    ])->toArray(),
                ],
            ],
            'bla-bla-blah-another' => [
                'uuid' => 'bla-bla-blah',
                'app_uuid' => self::$appId,
            ],
        ];

        return $configuration;
    }
}
