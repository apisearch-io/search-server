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

namespace Apisearch\Plugin\Testing\Tests;

/**
 * Class ResetInMemoryRepositoriesControllerTest.
 */
class ResetInMemoryRepositoriesControllerTest extends TestingPluginFunctionalTest
{
    /**
     * test reset repositories.
     *
     * Before starting this case, we have 2 apps, 3 indices, X tokens
     *
     * @return void
     */
    public function testResetRepositories(): void
    {
        $this->assertCount(2, $this->getIndices());
        $this->putToken($this->createTokenByIdAndAppId('1234', static::$appId));
        $this->assertCount(4, $this->getTokens());
        $this->assertNotEmpty($this->getUsage());

        $this->request('testing_reset_inmemory_repositories');
        $this->assertCount(0, $this->getIndices());
        $this->assertCount(3, $this->getTokens());
        $this->assertEmpty($this->getUsage());
    }
}
