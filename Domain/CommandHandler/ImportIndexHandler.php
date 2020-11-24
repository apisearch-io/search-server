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
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\Token;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\ImportIndexByStream;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\Format\FormatTransformer;
use Apisearch\Server\Domain\Format\FormatTransformers;
use Apisearch\Server\Domain\Model\InternalVersionUUID;
use Apisearch\Server\Domain\Query\CheckIndex;
use Apisearch\Server\Domain\Repository\Repository\Repository;
use Clue\React\Csv\Decoder;
use Drift\CommandBus\Bus\CommandBus;
use Drift\CommandBus\Bus\QueryBus;
use React\EventLoop\LoopInterface;
use function React\Promise\all;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;

/**
 * Class ImportIndexHandler.
 */
abstract class ImportIndexHandler
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;
    private Repository $repository;
    private FormatTransformers $formatTransformers;
    protected LoopInterface $loop;

    /**
     * @param CommandBus         $commandBus
     * @param QueryBus           $queryBus
     * @param Repository         $repository
     * @param FormatTransformers $formatTransformers
     * @param LoopInterface      $loop
     */
    public function __construct(
        CommandBus $commandBus,
        QueryBus $queryBus,
        Repository $repository,
        FormatTransformers $formatTransformers,
        LoopInterface $loop
    ) {
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
        $this->repository = $repository;
        $this->formatTransformers = $formatTransformers;
        $this->loop = $loop;
    }

    /**
     * @param CommandWithRepositoryReferenceAndToken $command
     *
     * @return PromiseInterface
     */
    public function handleByCommand(CommandWithRepositoryReferenceAndToken $command): PromiseInterface
    {
        $repositoryReference = $command->getRepositoryReference();
        $token = $command->getToken();
        $deferred = new Deferred();
        $indexUUID = $repositoryReference->getIndexUUID();

        $this
            ->queryBus
            ->ask(new CheckIndex($repositoryReference, $token, $indexUUID))
            ->then(function (bool $indexAvailable) use ($deferred, $indexUUID) {
                if (!$indexAvailable) {
                    $exception = ResourceNotAvailableException::indexNotAvailable($indexUUID->composeUUID());
                    $deferred->reject($exception);

                    throw $exception;
                }
            })
            ->then(function () use ($command) {
                return $this->getImportIndexByStream($command);
            })
            ->otherwise(function (\Throwable $throwable) use ($deferred) {
                $deferred->reject($throwable);

                throw $throwable;
            })
            ->then(function (ImportIndexByStream $importIndexByStream) use ($repositoryReference, $token, $deferred) {
                $stream = $importIndexByStream->getStream();
                $stream = new Decoder(
                    $stream,
                    FormatTransformer::getLineSeparator()
                );

                $shouldDeleteOldVersions = $importIndexByStream->shouldDeleteOldVersions();
                $versionUUID = $importIndexByStream->getVersionUUID();
                $this
                    ->loop
                    ->futureTick(function () use ($repositoryReference, $token, $stream, $deferred, $shouldDeleteOldVersions, $versionUUID) {
                        return $this
                            ->importFromStream($repositoryReference, $token, $stream, $versionUUID)
                            ->then(function () use ($repositoryReference, $shouldDeleteOldVersions, $versionUUID) {
                                return $shouldDeleteOldVersions
                                    ? $this->deleteOldVersions($repositoryReference, $versionUUID)
                                    : null;
                            })
                            ->then(function () use ($deferred) {
                                $deferred->resolve();
                            })
                            ->otherwise(function (\Throwable $throwable) use ($deferred) {
                                $deferred->reject($throwable);
                            });
                    });
            });

        return $deferred->promise();
    }

    /**
     * @param CommandWithRepositoryReferenceAndToken $command
     *
     * @return PromiseInterface<ImportIndexByStream>
     */
    abstract protected function getImportIndexByStream(CommandWithRepositoryReferenceAndToken $command): PromiseInterface;

    /**
     * @param RepositoryReference     $repositoryReference
     * @param Token                   $token
     * @param ReadableStreamInterface $stream
     * @param string                  $versionUUID
     */
    private function importFromStream(
        RepositoryReference $repositoryReference,
        Token $token,
        ReadableStreamInterface $stream,
        string $versionUUID
    ): PromiseInterface {
        $deferred = new Deferred();
        $callsDeferred = [];
        $firstRow = true;
        $numberOfRows = null;
        $firstRowArray = [];
        $formatTransformer = null;
        $items = [];

        $stream->on('data', function ($data) use ($repositoryReference, $token, &$items, &$firstRow, &$firstRowArray, &$numberOfRows, &$formatTransformer, $deferred, $stream, &$callsDeferred, $versionUUID) {
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

                $item->addIndexedMetadata(InternalVersionUUID::INDEXED_METADATA_FIELD, $versionUUID);
                $items[] = $item;

                if (\count($items) >= 500) {
                    $newDeferred = new Deferred();
                    $callsDeferred[] = $newDeferred->promise();
                    $this
                        ->loop
                        ->futureTick(function () use ($repositoryReference, $token, $items, $newDeferred) {
                            return $this
                                ->commandBus
                                ->execute(new IndexItems($repositoryReference, $token, $items))
                                ->then(function () use ($newDeferred) {
                                    $newDeferred->resolve();
                                })
                                ->otherwise(function (\Throwable $throwable) use ($newDeferred) {
                                    $newDeferred->reject($throwable->getMessage());
                                });
                        });
                    $items = [];
                }
            } catch (\Throwable $throwable) {
                $deferred->reject(new InvalidFormatException('Error thrown when importing - '.$throwable->getMessage()));
            }
        });

        $stream->on('close', function () use ($repositoryReference, $token, $deferred, &$items, &$callsDeferred) {
            if (empty($items)) {
                return;
            }

            $this
                ->loop
                ->futureTick(function () use ($repositoryReference, $token, $items, $deferred, &$callsDeferred) {
                    return $this
                        ->commandBus
                        ->execute(new IndexItems($repositoryReference, $token, $items))
                        ->then(function () use (&$callsDeferred) {
                            return all($callsDeferred);
                        })
                        ->then(function () use ($deferred) {
                            $deferred->resolve();
                        })
                        ->otherwise(function (\Throwable $throwable) use ($deferred) {
                            $deferred->reject($throwable->getMessage());
                        });
                });
        });

        $stream->on('error', function (\Throwable $e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $versionUUID
     */
    private function deleteOldVersions(
        RepositoryReference $repositoryReference,
        string $versionUUID
    ): PromiseInterface {
        return $this
            ->repository
            ->deleteItemsByQuery(
                $repositoryReference,
                Query::createMatchAll()
                    ->filterUniverseBy(
                        InternalVersionUUID::INDEXED_METADATA_FIELD,
                        [$versionUUID],
                        Filter::EXCLUDE
                    )
                    ->disableAggregations()
                    ->disableSuggestions()
                    ->disableHighlights()
                    ->disableResults()
            );
    }
}
