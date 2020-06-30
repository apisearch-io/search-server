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

namespace Apisearch\Server\Tests\Functional\Http;

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class ImportExportTest.
 */
class ImportExportTest extends CurlFunctionalTest
{
    /**
     * Test import export combination.
     */
    public function testImportExportSource()
    {
        $source = $this->exportIndex('source');
        @\unlink('/tmp/dump.apisearch');
        $this->resetIndex();
        \file_put_contents('/tmp/dump.apisearch', \implode("\n", $source)."\n");
        $this->importIndex('file:///tmp/dump.apisearch');
        $source2 = $this->exportIndex('source');
        $this->assertEquals($source, $source2);
    }

    /**
     * Test import export combination.
     */
    public function testImportExportSourceStandard()
    {
        $standard = $this->exportIndex('standard');
        $source = $this->exportIndex('source');
        $this->assertNotEquals($standard, $source);
        @\unlink('/tmp/dump.apisearch');
        $this->resetIndex();
        \file_put_contents('/tmp/dump.apisearch', \implode("\n", $source)."\n");
        $this->importIndex('file:///tmp/dump.apisearch');
        \usleep(100000);
        $standard2 = $this->exportIndex('standard');
        $source2 = $this->exportIndex('source');
        $this->assertEquals($source, $source2);
        $this->assertNotEquals($standard2, $source2);
    }

    /**
     * Test import wrong file.
     */
    public function testImportFileDoesntExist()
    {
        @\unlink('/tmp/dump.notfound');
        $this->expectException(InvalidFormatException::class);
        $this->importIndex('file:///tmp/dump.notfound');
    }

    /**
     * Test import wrong file.
     */
    public function testImportFileWrongType()
    {
        @\unlink('/tmp/dump.notfound');
        $this->expectException(InvalidFormatException::class);
        $this->expectExceptionMessageRegExp('~Only .+? is allowed~');
        $this->importIndex('another:///tmp/dump.notfound');
    }

    /**
     * Test import wrong file.
     */
    public function testImportFileWrongRows()
    {
        @\unlink('/tmp/dump.notfound');
        $this->expectException(InvalidFormatException::class);
        $this->expectExceptionMessageRegExp('~.*Rows should have exactly.*~');
        $this->importIndex('file://'.__DIR__.'/dump.wrong.as');
    }

    /**
     * Test import wrong file.
     *
     * @group not
     */
    public function testImportNonExistingIndex()
    {
        $this->expectException(ResourceNotAvailableException::class);
        $this->importIndex('file://'.__DIR__.'/dump.source.as', false, static::$anotherInexistentAppId, static::$appId);
    }
}
