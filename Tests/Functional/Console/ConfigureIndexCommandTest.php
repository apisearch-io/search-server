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

use Apisearch\Query\Query;

/**
 * Trait ConfigureIndexCommandTest.
 */
trait ConfigureIndexCommandTest
{
    /**
     * Test create index command.
     */
    public function testConfigureWithStaticSynonymsCommand()
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
            'file' => __DIR__.'/data.as',
        ]);

        $this->assertEquals(
            0,
            $this->query(Query::create('efervescencio'))->getTotalHits()
        );

        static::runCommand([
            'command' => 'apisearch-server:configure-index',
            'app-id' => self::$appId,
            'index' => self::$index,
            '--synonym' => [
                'robert, efervescencio',
            ],
        ]);

        $this->assertEquals(
            2,
            $this->query(Query::create('efervescencio'))->getTotalHits()
        );

        static::runCommand([
            'command' => 'apisearch-server:import-index',
            'app-id' => self::$appId,
            'index' => self::$index,
            'file' => __DIR__.'/data.as',
        ]);

        $this->assertTrue(
            $this->query(Query::create('efervescencio'))->getTotalHits() > 0
        );

        static::runCommand([
            'command' => 'apisearch-server:delete-index',
            'app-id' => self::$appId,
            'index' => self::$index,
        ]);
    }

    /**
     * Test create index command.
     */
    public function testConfigureWithSynonymsFileCommand()
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
            'file' => __DIR__.'/data.as',
        ]);

        $this->assertEquals(
            0,
            $this->query(Query::create('efervescencio'))->getTotalHits()
        );

        static::runCommand([
            'command' => 'apisearch-server:configure-index',
            'app-id' => self::$appId,
            'index' => self::$index,
            '--synonyms-file' => __DIR__.'/synonyms.csv',
        ]);

        $this->assertEquals(
            2,
            $this->query(Query::create('efervescencio'))->getTotalHits()
        );

        static::runCommand([
            'command' => 'apisearch-server:import-index',
            'app-id' => self::$appId,
            'index' => self::$index,
            'file' => __DIR__.'/data.as',
        ]);

        $this->assertTrue(
            $this->query(Query::create('efervescencio'))->getTotalHits() > 0
        );

        static::runCommand([
            'command' => 'apisearch-server:delete-index',
            'app-id' => self::$appId,
            'index' => self::$index,
        ]);
    }
}
