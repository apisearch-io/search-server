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
use Apisearch\Query\Query;
use Apisearch\Result\Result;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Factory;
use React\Filesystem\Filesystem;
use React\Http\Browser;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;

/**
 * Class ImportExportTest.
 */
class ImportExportTest extends CurlFunctionalTest
{
    /**
     * @return bool
     */
    protected static function needsInitialItemsIndexation(): bool
    {
        return false;
    }

    /**
     * Test import export combination.
     */
    public function testImportExportSource()
    {
        $this->indexTestingItems();
        $source = $this->exportIndex('source');
        @\unlink('/tmp/dump.apisearch');
        $this->resetIndex();
        \file_put_contents('/tmp/dump.apisearch', \implode("\n", $source)."\n");
        $this->importIndexByFeed('file:///tmp/dump.apisearch');
        $source2 = $this->exportIndex('source');
        $this->assertEquals($source, $source2);
    }

    /**
     * Test import export combination.
     */
    public function testImportExportSourceStandard()
    {
        $this->indexTestingItems();
        $standard = $this->exportIndex('standard');
        $source = $this->exportIndex('source');
        $this->assertNotEquals($standard, $source);
        @\unlink('/tmp/dump.apisearch');
        $this->resetIndex();
        \file_put_contents('/tmp/dump.apisearch', \implode("\n", $source)."\n");
        $this->importIndexByFeed('file:///tmp/dump.apisearch');
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
        $this->importIndexByFeed('file:///tmp/dump.notfound');
    }

    /**
     * Test import wrong file.
     */
    public function testImportFileWrongType()
    {
        @\unlink('/tmp/dump.notfound');
        $this->expectException(InvalidFormatException::class);
        $this->expectExceptionMessageRegExp('~Only .+? is allowed~');
        $this->importIndexByFeed('another:///tmp/dump.notfound');
    }

    /**
     * Test import wrong file.
     */
    public function testImportFileWrongRows()
    {
        @\unlink('/tmp/dump.notfound');
        $this->expectException(InvalidFormatException::class);
        $this->expectExceptionMessageRegExp('~.*Rows should have exactly.*~');
        $this->importIndexByFeed('file://'.__DIR__.'/dump.wrong.as');
    }

    /**
     * Test import wrong file.
     */
    public function testImportNonExistingIndex()
    {
        $this->expectException(ResourceNotAvailableException::class);
        $this->importIndexByFeed('file://'.__DIR__.'/dump.source.as', false, static::$anotherInexistentAppId, static::$appId);
    }

    /**
     * Test import by stream.
     */
    public function testImportByStream()
    {
        $loop = Factory::create();
        $browser = new Browser($loop);
        $filesystem = Filesystem::create($loop);
        $url = \sprintf('http://127.0.0.1:'.static::HTTP_TEST_SERVICE_PORT.'/v1/%s/indices/%s/import/by-stream?token=%s',
            self::$appId,
            self::$index,
            self::$godToken
        );

        $stream = new ThroughStream();
        $promise = $browser
            ->put($url, [], $stream)
            ->then(function (ResponseInterface $_) use (&$data) {
                return $this->query(Query::createMatchAll());
            })
            ->then(function (Result $result) {
                $this->assertEquals(5, $result->getTotalItems());
                $this->assertNull($result->getFirstItem()->get('mod'));
            });

        $loop->addTimer(0.5, function () use ($stream, $filesystem) {
            return $filesystem
                ->file(__DIR__.'/data.source.as')
                ->open('r')
                ->then(function (ReadableStreamInterface $fileStream) use ($stream) {
                    $fileStream->pipe($stream);
                    $fileStream->on('close', function () use ($stream) {
                        $stream->close();
                    });
                });
        });

        $this->await($promise, $loop);

        $stream = new ThroughStream();
        $promise = $browser
            ->put($url, [], $stream)
            ->then(function (ResponseInterface $_) use (&$data) {
                return $this->query(Query::createMatchAll());
            })
            ->then(function (Result $result) {
                $this->assertEquals(5, $result->getTotalItems());
                $this->assertEquals(1, $result->getFirstItem()->get('mod'));
                $this->assertEquals(5, $result->getItems()[4]->get('mod'));
            });

        $loop->addTimer(0.5, function () use ($stream, $filesystem) {
            return $filesystem
                ->file(__DIR__.'/data.source.modified.as')
                ->open('r')
                ->then(function (ReadableStreamInterface $fileStream) use ($stream) {
                    $fileStream->pipe($stream);
                    $fileStream->on('close', function () use ($stream) {
                        $stream->close();
                    });
                });
        });

        $this->await($promise, $loop);
    }
}
