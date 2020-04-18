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
 * Class GetDeleteTokensCommandTest.
 */
abstract class GetDeleteTokensCommandTest extends CommandTest
{
    /**
     * Test token creation.
     */
    public function testPrintAndDeleteTokens()
    {
        static::runCommand([
            'command' => 'apisearch-server:create-index',
            'app-id' => self::$appId,
            'index' => self::$index,
        ]);

        static::runCommand([
            'command' => 'apisearch-server:add-token',
            'uuid' => $this->token,
            'app-id' => self::$appId,
            '--index' => [self::$index],
        ]);

        static::runCommand([
            'command' => 'apisearch-server:add-token',
            'uuid' => '67890',
            'app-id' => self::$appId,
            '--index' => [self::$index],
        ]);

        $output = static::runCommand([
            'command' => 'apisearch-server:print-tokens',
            'app-id' => self::$appId,
        ]);

        $this->assertTrue(\strpos($output, "{$this->token}") > 0);
        $this->assertTrue(\strpos($output, '67890') > 0);

        $output = static::runCommand([
            'command' => 'apisearch-server:delete-all-tokens',
            'app-id' => self::$appId,
        ]);

        $this->assertFalse(\strpos($output, "{$this->token}"));
        $this->assertFalse(\strpos($output, '67890'));
    }
}
