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
 * Class GetTokensTest.
 */
class GetTokensTest extends AdminPluginFunctionalTest
{
    /**
     * Test controller result.
     */
    public function testController()
    {
        $this->putToken($this->createTokenByIdAndAppId('token1', static::$appId));
        $this->putToken($this->createTokenByIdAndAppId('token2', static::$appId));
        $this->putToken($this->createTokenByIdAndAppId('token3', static::$anotherAppId));
        $this->putToken($this->createTokenByIdAndAppId('token4', 'yet-another-app'));
        $response = $this->request('admin_get_tokens');
        $apps = $response['body'];

        $this->assertCount(3, $apps);
        $this->assertCount(2, $apps[static::$appId]);
        $this->assertCount(1, $apps[static::$anotherAppId]);
        $this->assertCount(1, $apps['yet-another-app']);
    }
}
