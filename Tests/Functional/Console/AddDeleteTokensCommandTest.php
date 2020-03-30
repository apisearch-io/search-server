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

namespace Apisearch\Server\Tests\Functional\Console;

/**
 * Class AddDeleteTokensCommandTest.
 */
abstract class AddDeleteTokensCommandTest extends CommandTest
{
    /**
     * Test token creation.
     */
    public function testTokenCreation()
    {
        $this->assertTokenNotExists();

        static::runCommand([
            'command' => 'apisearch-server:add-token',
            'uuid' => $this->token,
            'app-id' => self::$appId,
            '--index' => [self::$index],
        ]);

        $this->assertTokenExists();

        static::runCommand([
            'command' => 'apisearch-server:delete-token',
            'uuid' => $this->token,
            'app-id' => self::$appId,
        ]);

        $this->assertTokenNotExists();
    }

    /**
     * Test token creation with generated UUID.
     */
    public function testGeneratedTokenCreation()
    {
        $output = static::runCommand([
            'command' => 'apisearch-server:add-token',
            'app-id' => self::$appId,
        ]);

        preg_match('~Token UUID: (.*?)~', $output, $match);
        $this->assertTokenNotExists($match[1]);
    }
}
