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

namespace Apisearch\Tests\Server\DependencyInjection;

use Apisearch\Server\DependencyInjection\Env;
use PHPUnit\Framework\TestCase;

/**
 * Class EnvTest.
 */
class EnvTest extends TestCase
{
    /**
     * Test.
     *
     * @return void
     */
    public function testDefaultBehavior(): void
    {
        $_SERVER['A'] = 'A1';
        $this->assertEquals('A1', Env::get('A', 'A2'));
        $this->assertEquals('B2', Env::get('B', 'B2'));
    }
}
