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

namespace Apisearch\Plugin\Fastly\Tests;

use Apisearch\Plugin\Fastly\FastlyPluginBundle;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class FastlyPluginFunctionalTest.
 */
abstract class FastlyPluginFunctionalTest extends HttpFunctionalTest
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
        $bundles[] = FastlyPluginBundle::class;

        return $bundles;
    }
}
