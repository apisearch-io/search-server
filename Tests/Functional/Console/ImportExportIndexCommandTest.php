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

        \ob_start();
        static::runCommand([
            'command' => 'apisearch-server:export-index',
            'app-id' => self::$appId,
            'index' => self::$index,
            '--format' => 'source',
            '--quiet' => true,
        ]);
        $exportSourceOutput = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals(
            \file_get_contents(__DIR__.'/data.source.as'),
            $exportSourceOutput
        );

        \ob_start();
        static::runCommand([
            'command' => 'apisearch-server:export-index',
            'app-id' => self::$appId,
            'index' => self::$index,
            '--format' => 'standard',
            '--quiet' => true,
        ]);
        $exportStandardOutput = \ob_get_contents();
        \ob_end_clean();

        $this->assertNotEquals($exportSourceOutput, $exportStandardOutput);

        $this->assertEquals(
            \file_get_contents(__DIR__.'/data.standard.as'),
            $exportStandardOutput
        );

        static::resetScenario();
    }
}
