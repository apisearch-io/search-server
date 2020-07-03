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

namespace Apisearch\Server\Domain\CommandHandler;

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Model\Token;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\ImportIndex;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\Format\FormatTransformer;
use Apisearch\Server\Domain\Format\FormatTransformers;
use Apisearch\Server\Domain\Repository\Repository\Repository;
use Apisearch\Server\Domain\Resource\ResourceLoader;
use Clue\React\Csv\Decoder;
use Drift\CommandBus\Bus\CommandBus;
use React\EventLoop\LoopInterface;
use function React\Promise\all;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;

/**
 * Class ImportIndexHandler.
 */
class ImportIndexHandler
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var FormatTransformers
     */
    private $formatTransformers;

    /**
     * @var ResourceLoader
     */
    private $resourceLoader;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @param CommandBus         $commandBus
     * @param Repository         $repository
     * @param FormatTransformers $formatTransformers
     * @param ResourceLoader     $resourceLoader
     * @param LoopInterface      $loop
     */
    public function __construct(
        CommandBus $commandBus,
        Repository $repository,
        FormatTransformers $formatTransformers,
        ResourceLoader $resourceLoader,
        LoopInterface $loop
    ) {
        $this->commandBus = $commandBus;
        $this->repository = $repository;
        $this->formatTransformers = $formatTransformers;
        $this->resourceLoader = $resourceLoader;
        $this->loop = $loop;
    }

    /**
     * @param ImportIndex $importIndex
     *
     * @return PromiseInterface
     */
    public function handle(ImportIndex $importIndex): PromiseInterface
    {
        $repositoryReference = $importIndex->getRepositoryReference();
        $token = $importIndex->getToken();
        $feed = $importIndex->getFeed();

        return $this
            ->commandBus
            ->execute(new IndexItems($repositoryReference, $token, []))
            ->then(function () use ($repositoryReference, $token, $feed) {
                $deferred = new Deferred();

                $this
                    ->loop
                    ->futureTick(function () use ($repositoryReference, $token, $feed, $deferred) {
                        return $this
                            ->resourceLoader
                            ->getByPath($feed)
                            ->then(function (ReadableStreamInterface $stream) use ($repositoryReference, $token, $deferred) {
                                $stream = new Decoder(
                                    $stream,
                                    FormatTransformer::getLineSeparator()
                                );

                                $this
                                    ->loop
                                    ->futureTick(function () use ($repositoryReference, $token, $stream, $deferred) {
                                        return $this
                                            ->importFromStream($repositoryReference, $token, $stream)
                                            ->then(function () use ($deferred) {
                                                $deferred->resolve();
                                            })
                                            ->otherwise(function (\Throwable $throwable) use ($deferred) {
                                                $deferred->reject($throwable);
                                            });
                                    });
                            })
                            ->otherwise(function (\Throwable $throwable) use ($deferred) {
                                $deferred->reject($throwable);
                            });
                    });

                return $deferred->promise();
            });
    }

    /**
     * @param RepositoryReference     $repositoryReference
     * @param Token                   $token
     * @param ReadableStreamInterface $stream
     */
    private function importFromStream(
        RepositoryReference $repositoryReference,
        Token $token,
        ReadableStreamInterface $stream
    ): PromiseInterface {
        $deferred = new Deferred();
        $callsDeferreds = [];
        $firstRow = true;
        $numberOfRows = null;
        $firstRowArray = [];
        $formatTransformer = null;
        $items = [];

        $stream->on('data', function ($data) use ($repositoryReference, $token, &$items, &$firstRow, &$firstRowArray, &$numberOfRows, &$formatTransformer, $deferred, $stream, &$callsDeferreds) {
            if (!\is_array($data)) {
                return;
            }

            try {
                if ($firstRow) {
                    $firstRowArray = $data;
                    $numberOfRows = \count($data);
                    $firstRow = false;
                    $formatTransformer = $this
                        ->formatTransformers
                        ->guessFormatTransformerByHeaders($firstRowArray);

                    if (\is_null($formatTransformer)) {
                        $stream->close();
                        $deferred->resolve();
                    }

                    return;
                }

                if (\count($data) !== $numberOfRows) {
                    $deferred->reject(new InvalidFormatException('Input file bad formatted. Rows should have exactly '.$numberOfRows.' rows. '.\count($data).' encountered.'));
                }

                $item = $formatTransformer->arrayToItem(
                    $firstRowArray,
                    $data
                );

                $items[] = $item;

                if (\count($items) >= 500) {
                    $newDeferred = new Deferred();
                    $callsDeferreds[] = $newDeferred->promise();
                    $this
                        ->loop
                        ->futureTick(function () use ($repositoryReference, $token, $items, $newDeferred) {
                            return $this
                                ->commandBus
                                ->execute(new IndexItems($repositoryReference, $token, $items))
                                ->always(function () use ($newDeferred) {
                                    $newDeferred->resolve();
                                });
                        });
                    $items = [];
                }
            } catch (\Throwable $throwable) {
                $deferred->reject(new InvalidFormatException('Error thrown when importing - '.$throwable->getMessage()));
            }
        });

        $stream->on('close', function () use ($repositoryReference, $token, $deferred, &$items, &$callsDeferreds) {
            $this
                ->loop
                ->futureTick(function () use ($repositoryReference, $token, $items, $deferred, &$callsDeferreds) {
                    return $this
                        ->commandBus
                        ->execute(new IndexItems($repositoryReference, $token, $items))
                        ->then(function () use (&$callsDeferreds) {
                            return all($callsDeferreds);
                        })
                        ->always(function () use ($deferred) {
                            $deferred->resolve();
                        });
                });
        });

        $stream->on('error', function (\Throwable $e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }
}
