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

namespace Apisearch\Plugin\Tor\Tests\Functional;

use Apisearch\Plugin\Tor\Domain\FilesystemIpProvider;
use Apisearch\Plugin\Tor\Domain\IpProvider;
use Apisearch\Plugin\Tor\TorPluginBundle;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;
use React\Filesystem\Filesystem;

abstract class TorFunctionalTest extends HttpFunctionalTest
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
        $bundles[] = TorPluginBundle::class;

        return $bundles;
    }

    /**
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration['apisearch_plugin_tor']['sources'] = [
            __DIR__.'/source1.txt',
            __DIR__.'/source2.txt',
        ];

        $configuration['services'][FilesystemIpProvider::class] = [
            'arguments' => [
                '@'.Filesystem::class,
            ],
        ];
        $configuration['services'][IpProvider::class] = [
            'alias' => FilesystemIpProvider::class,
        ];

        return $configuration;
    }
}
