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
 * Trait ImportExportIndexCommandTest.
 */
trait ImportExportIndexCommandTest
{
    /**
     * Test token creation.
     */
    public function testIndexImportAndExport()
    {
        $fileName = \tempnam('/tmp', 'test-apisearch');

        static::runCommand([
            'command' => 'apisearch-server:create-index',
            'app-id' => self::$appId,
            'index' => self::$index,
        ]);

        $importOutput = static::runCommand([
            'command' => 'apisearch-server:import-index',
            'app-id' => self::$appId,
            'index' => self::$index,
            'file' => __DIR__.'/data.as',
        ]);

        $this->assertTrue(
            \strpos($importOutput, 'Partial import of 28 items') >= 0
        );

        $exportOutput = static::runCommand([
            'command' => 'apisearch-server:export-index',
            'app-id' => self::$appId,
            'index' => self::$index,
            'file' => $fileName,
        ]);

        $this->assertEquals(
            \file_get_contents(__DIR__.'/data.as'),
            \file_get_contents($fileName)
        );

        $this->assertTrue(
            \strpos($exportOutput, 'Partial export of 28 items') >= 0
        );

        static::runCommand([
            'command' => 'apisearch-server:delete-index',
            'app-id' => self::$appId,
            'index' => self::$index,
        ]);

        static::runCommand([
            'command' => 'apisearch-server:delete-index',
            'app-id' => self::$appId,
            'index' => 'anotherindexforexport',
        ]);

        @\unlink($fileName);
    }
}
