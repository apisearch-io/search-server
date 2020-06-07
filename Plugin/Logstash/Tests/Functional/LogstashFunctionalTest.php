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

namespace Apisearch\Plugin\Logstash\Tests\Functional;

use Apisearch\Plugin\Logstash\LogstashPluginBundle;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class LogstashFunctionalTest.
 */
abstract class LogstashFunctionalTest extends CurlFunctionalTest
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
        $bundles[] = LogstashPluginBundle::class;

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

        $configuration['services']['redis.logstash_client_test'] = [
            'alias' => 'redis.logstash_client',
            'public' => true,
        ];

        return $configuration;
    }
}
