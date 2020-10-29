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

namespace Apisearch\Plugin\JWT\Tests\Functional;

use Apisearch\Plugin\JWT\JWTPluginBundle;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class JWTFunctionalTest.
 */
abstract class JWTFunctionalTest extends CurlFunctionalTest
{
    const PRIVATE_KEY = '6F27583CEB7C75C68246784261456';
    const ALGORITHM = 'HS256';
    const TTL = 3600;

    /**
     * Decorate bundles.
     *
     * @param array $bundles
     *
     * @return array
     */
    protected static function decorateBundles(array $bundles): array
    {
        $bundles[] = JWTPluginBundle::class;

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

        $configuration['apisearch_plugin_jwt'] = static::getJWTConfiguration();

        return $configuration;
    }

    /**
     * Get JWT configuration.
     *
     * @return array
     */
    abstract protected static function getJWTConfiguration(): array;
}
