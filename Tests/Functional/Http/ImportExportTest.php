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
use Apisearch\Server\Domain\Model\InternalVersionUUID;
use Apisearch\Server\Domain\Repository\LogRepository\InMemoryLogRepository;
use Apisearch\Server\Domain\Repository\LogRepository\LogMapper;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
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
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration = parent::decorateConfiguration($configuration);
        $configuration['services'][UsageRepository::class] = [
            'alias' => InMemoryUsageRepository::class,
        ];

        $configuration['services'][LogRepository::class] = [
            'alias' => InMemoryLogRepository::class,
        ];

        return $configuration;
    }

    /**
     * Test requests counting and log.
     *
     * @return void
     */
    public function testRequestsCountingAndLog(): void
    {
        $this->assertEquals(3, $this->getUsage()['admin']);
        $this->loadMassiveIndexItems(2500); // 50 iterations of 50
        $this->assertEquals(53, $this->getUsage()['admin']);
        $source = $this->exportIndex('source');
        \usleep(100000);
        $this->assertEquals(54, $this->getUsage()['admin']);

        if (\file_exists('/tmp/dump.apisearch')) {
            \unlink('/tmp/dump.apisearch');
        }

        $this->resetIndex();
        $this->assertEquals(55, $this->getUsage()['admin']);
        \file_put_contents('/tmp/dump.apisearch', \implode("\n", $source)."\n");
        $this->importIndexByFeed('file:///tmp/dump.apisearch', false, false, '9911');
        $this->assertEquals(60, $this->getUsage()['admin']);
        $this->exportIndex('source');
        $this->exportIndex('source');
        $this->assertEquals(62, $this->getUsage()['admin']);
        $this->importIndexByFeed('file:///tmp/dump.apisearch', false, false);
        $this->assertEquals(67, $this->getUsage()['admin']);

        $logs = $this->getLogs(null, null, null, null, null, [
            LogMapper::INDEX_WAS_IMPORTED,
            LogMapper::INDEX_WAS_EXPORTED,
        ]);

        $this->assertCount(5, $logs);
        $this->assertStringContainsString('exported', $logs[0]['text']);
        $this->assertStringContainsString(self::$index, $logs[0]['text']);
        $this->assertStringContainsString('imported', $logs[1]['text']);
        $this->assertStringContainsString('9911', $logs[1]['text']);
        $this->assertStringContainsString(self::$index, $logs[1]['text']);
        $this->assertStringContainsString('exported', $logs[2]['text']);
        $this->assertStringContainsString(self::$index, $logs[2]['text']);
        $this->assertStringContainsString('exported', $logs[3]['text']);
        $this->assertStringContainsString(self::$index, $logs[3]['text']);
        $this->assertStringContainsString('imported', $logs[4]['text']);
        $this->assertStringNotContainsString('9911', $logs[4]['text']);
        $this->assertStringContainsString(self::$index, $logs[4]['text']);
    }

    /**
     * Test import export combination.
     *
     * @return void
     */
    public function testImportExportSource(): void
    {
        $this->resetScenario();
        $this->indexTestingItems();
        $source = $this->exportIndex('source');

        if (\file_exists('/tmp/dump.apisearch')) {
            \unlink('/tmp/dump.apisearch');
        }

        $this->resetIndex();
        \file_put_contents('/tmp/dump.apisearch', \implode("\n", $source)."\n");
        $this->importIndexByFeed('file:///tmp/dump.apisearch', false, false, '1334');
        $source2 = $this->exportIndex('source');
        $this->assertEquals($source, $source2);
    }

    /**
     * Test import export combination.
     *
     * @return void
     */
    public function testImportExportSourceStandard(): void
    {
        $this->indexTestingItems();
        $standard = $this->exportIndex('standard');
        $source = $this->exportIndex('source');
        $this->assertNotEquals($standard, $source);

        if (\file_exists('/tmp/dump.apisearch')) {
            \unlink('/tmp/dump.apisearch');
        }

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
     *
     * @return void
     */
    public function testImportFileDoesntExist(): void
    {
        if (\file_exists('/tmp/dump.notfound')) {
            \unlink('/tmp/dump.notfound');
        }

        $this->expectException(InvalidFormatException::class);
        $this->importIndexByFeed('file:///tmp/dump.notfound');
    }

    /**
     * Test import wrong file.
     *
     * @return void
     */
    public function testImportFileWrongType(): void
    {
        if (\file_exists('/tmp/dump.notfound')) {
            \unlink('/tmp/dump.notfound');
        }

        $this->expectException(InvalidFormatException::class);
        $this->expectExceptionMessageMatches('~Only .+? is allowed~');
        $this->importIndexByFeed('another:///tmp/dump.notfound');
    }

    /**
     * Test import wrong file.
     *
     * @return void
     */
    public function testImportFileWrongRows(): void
    {
        if (\file_exists('/tmp/dump.notfound')) {
            \unlink('/tmp/dump.notfound');
        }

        $this->expectException(InvalidFormatException::class);
        $this->expectExceptionMessageMatches('~.*Rows should have exactly.*~');
        $this->importIndexByFeed('file://'.__DIR__.'/dump.wrong.as');
    }

    /**
     * Test import wrong file.
     *
     * @return void
     */
    public function testImportNonExistingIndex(): void
    {
        $this->expectException(ResourceNotAvailableException::class);
        $this->importIndexByFeed('file://'.__DIR__.'/dump.source.as', false, false, null, static::$anotherInexistentAppId, static::$appId);
    }

    /**
     * Test import by stream.
     *
     * @return void
     */
    public function testImportByStream(): void
    {
        $this->resetIndex();
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

    /**
     * Test import by version.
     *
     * @return void
     */
    public function testImportByVersion(): void
    {
        $this->indexTestingItems(self::$appId, self::$index, self::getItemsFilePath());
        $source = $this->exportIndex('source');

        if (\file_exists('/tmp/dump.apisearch')) {
            \unlink('/tmp/dump.apisearch');
        }

        \file_put_contents('/tmp/dump.apisearch', \implode("\n", $source)."\n");
        $this->resetIndex();
        $this->indexTestingItems(self::$appId, self::$index, self::getItemsReducedFilePath());
        $sourceReduced = $this->exportIndex('source');

        if (\file_exists('/tmp/dump.reduced.apisearch')) {
            \unlink('/tmp/dump.reduced.apisearch');
        }

        \file_put_contents('/tmp/dump.reduced.apisearch', \implode("\n", $sourceReduced)."\n");
        $this->resetIndex();

        $this->importIndexByFeed('file:///tmp/dump.apisearch', false, false);
        $totalItems = $this->query(Query::createMatchAll())->getTotalItems();
        $this->assertEquals(5, $totalItems);

        $this->importIndexByFeed('file:///tmp/dump.reduced.apisearch', false, true);
        $totalItems = $this->query(Query::createMatchAll())->getTotalItems();
        $this->assertEquals(2, $totalItems);
    }

    /**
     * Test import by version.
     *
     * @return void
     */
    public function testImportByCustomVersion(): void
    {
        $this->indexTestingItems(self::$appId, self::$index, self::getItemsFilePath());
        $source = $this->exportIndex('source');

        if (\file_exists('/tmp/dump.apisearch')) {
            \unlink('/tmp/dump.apisearch');
        }

        \file_put_contents('/tmp/dump.apisearch', \implode("\n", $source)."\n");
        $this->resetIndex();
        $this->indexTestingItems(self::$appId, self::$index, self::getItemsReducedFilePath());
        $sourceReduced = $this->exportIndex('source');

        if (\file_exists('/tmp/dump.reduced.apisearch')) {
            \unlink('/tmp/dump.reduced.apisearch');
        }

        \file_put_contents('/tmp/dump.reduced.apisearch', \implode("\n", $sourceReduced)."\n");
        $this->resetIndex();

        $this->importIndexByFeed('file:///tmp/dump.apisearch', false, false);
        $totalItems = $this->query(Query::createMatchAll())->getTotalItems();
        $this->assertEquals(5, $totalItems);

        $this->importIndexByFeed('file:///tmp/dump.reduced.apisearch', false, true, 'custom_UUID_123');
        $totalItems = $this->query(Query::createMatchAll())->getTotalItems();
        $this->assertEquals(2, $totalItems);

        $totalItems = $this->query(Query::createMatchAll()->filterUniverseBy(InternalVersionUUID::INDEXED_METADATA_FIELD, ['custom_UUID_123']))->getTotalItems();
        $this->assertEquals(2, $totalItems);
    }
}
