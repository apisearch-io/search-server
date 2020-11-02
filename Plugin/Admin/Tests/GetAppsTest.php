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

namespace Apisearch\Plugin\Admin\Tests;

/**
 * Class GetAppsTest.
 */
class GetAppsTest extends AdminPluginFunctionalTest
{
    /**
     * Test controller result.
     */
    public function testController()
    {
        $response = $this->request('admin_get_apps');

        $this->assertEquals([
            static::$appId => [
                static::$index => [
                    'ok' => true,
                    'items' => 5,
                    'size' => '',
                ],
                static::$anotherIndex => [
                    'ok' => true,
                    'items' => 0,
                    'size' => '',
                ],
            ],
            static::$anotherAppId => [
                static::$index => [
                    'ok' => true,
                    'items' => 0,
                    'size' => '',
                ],
            ],
        ], $response['body']);
    }
}
