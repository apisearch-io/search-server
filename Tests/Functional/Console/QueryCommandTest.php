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
     * @group lele
     */
    public function testQuery()
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
            'source' => 'file://'.__DIR__.'/data.source.as',
        ]);

        $content = static::runCommand([
            'command' => 'apisearch-server:query',
            'app-id' => self::$appId,
            'index' => self::$index,
        ]);

        $this->assertContains('* / 1 / 10', $content);
        $this->assertContains('[Number of hits] 750', $content);
        $this->assertContains('mw0002400465~album - 15th Anniversary Collection', $content);
        $this->assertContains('mw0002138578~album - Kelopuu: Pohjolan Molli', $content);

        $content2 = static::runCommand([
            'command' => 'apisearch-server:query',
            'app-id' => self::$appId,
            'index' => self::$index,
            'query' => 'Into',
        ]);

        $this->assertContains('Into / 1 / 10', $content2);
        $this->assertContains('[Number of hits] 7', $content2);
        $this->assertContains('Into a Real Thing', $content2);
        $this->assertContains('Riding the Light Into the Birds Eye', $content2);

        $content3 = static::runCommand([
            'command' => 'apisearch-server:query',
            'app-id' => self::$appId,
            'index' => self::$index,
            'query' => 'Into',
            '--page' => 1,
            '--size' => 2,
        ]);

        $this->assertContains('Into / 1 / 2', $content3);
        $this->assertContains('[Number of hits] 7', $content3);
        $this->assertContains('Into Your Ears', $content3);
        $this->assertNotContains('Into a Real Thing', $content3);

        $content3 = static::runCommand([
            'command' => 'apisearch-server:query',
            'app-id' => self::$appId,
            'index' => self::$index,
            'query' => 'Into',
            '--page' => 2,
            '--size' => 2,
            '--format' => 'lines',
        ]);

        $this->assertContains('Into / 2 / 2', $content3);
        $this->assertContains('[Number of hits] 7', $content3);
        $this->assertNotContains('Into Your Ears', $content3);
        $this->assertContains('Into a Real Thing', $content3);

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
