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

/**
 * Class GetTokensTest.
 */
class GetTokensTest extends StaticTokensFunctionalTest
{
    /**
     * Test count.
     *
     * @return void
     */
    public function testCount(): void
    {
        $this->assertCount(8, $this->getTokens());
    }
}
