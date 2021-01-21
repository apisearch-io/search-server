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

namespace Apisearch\Server\Tests\Functional\Domain\Token;

use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class StaticTokensTest.
 */
class StaticTokensTest extends ServiceFunctionalTest
{
    /**
     * Test readonly flag.
     *
     * @return void
     */
    public function testReadOnlyFlag(): void
    {
        $tokens = $this->getTokensById();

        $this->assertTrue($tokens[static::$godToken]->getMetadataValue('read_only'));
        $this->assertTrue($tokens[static::$readonlyToken]->getMetadataValue('read_only'));
        $this->assertTrue($tokens[static::$pingToken]->getMetadataValue('read_only'));
    }
}
