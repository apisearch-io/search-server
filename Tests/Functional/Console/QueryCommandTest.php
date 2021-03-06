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
 * Trait QueryCommandTest.
 */
trait QueryCommandTest
{
    /**
     * Test query.
     *
     * @return void
     */
    public function testQuery(): void
    {
        $this->assertNotExistsIndex();

        static::runCommand([
            'command' => 'apisearch-server:create-index',
            'app-id' => self::$appId,
            'index' => self::$index,
        ]);

        static::runCommand([
            'command' => 'apisearch-server:import-index',
            'app-id' => self::$appId,
            'index' => self::$index,
            'source' => 'file://'.__DIR__.'/data.source.full.as',
        ]);

        $content = static::runCommand([
            'command' => 'apisearch-server:query',
            'app-id' => self::$appId,
            'index' => self::$index,
        ]);

        $this->assertStringContainsString('* / 1 / 10', $content);
        $this->assertStringContainsString('[Number of hits] 750', $content);

        $content2 = static::runCommand([
            'command' => 'apisearch-server:query',
            'app-id' => self::$appId,
            'index' => self::$index,
            'query' => 'Into',
        ]);

        $this->assertStringContainsString('Into / 1 / 10', $content2);
        $this->assertStringContainsString('[Number of hits] 7', $content2);
        $this->assertStringContainsString('Into a Real Thing', $content2);
        $this->assertStringContainsString('Riding the Light Into the Birds Eye', $content2);

        $content3 = static::runCommand([
            'command' => 'apisearch-server:query',
            'app-id' => self::$appId,
            'index' => self::$index,
            'query' => 'Into',
            '--page' => 1,
            '--size' => 2,
        ]);

        $this->assertStringContainsString('Into / 1 / 2', $content3);
        $this->assertStringContainsString('[Number of hits] 7', $content3);
        $this->assertStringContainsString('Into Your Ears', $content3);
        $this->assertStringNotContainsString('Into a Real Thing', $content3);

        $content3 = static::runCommand([
            'command' => 'apisearch-server:query',
            'app-id' => self::$appId,
            'index' => self::$index,
            'query' => 'Into',
            '--page' => 2,
            '--size' => 2,
            '--format' => 'lines',
        ]);

        $this->assertStringContainsString('Into / 2 / 2', $content3);
        $this->assertStringContainsString('[Number of hits] 7', $content3);
        $this->assertStringNotContainsString('Into Your Ears', $content3);
        $this->assertStringContainsString('Into a Real Thing', $content3);

        \ob_start();
        static::runCommand([
            'command' => 'apisearch-server:query',
            'app-id' => self::$appId,
            'index' => self::$index,
            'query' => 'Into',
            '--page' => 3,
            '--size' => 2,
            '--format' => 'json',
            '--quiet' => true,
        ]);
        $content4 = \ob_get_contents();
        \ob_end_clean();

        $result4 = \json_decode($content4, true);
        $this->assertEquals(7, $result4['total_hits']);
        $this->assertCount(2, $result4['items']);
    }
}
