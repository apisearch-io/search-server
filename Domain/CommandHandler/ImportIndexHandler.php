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
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\Format\FormatTransformer;
use Apisearch\Server\Domain\Format\FormatTransformers;
use Apisearch\Server\Domain\Repository\Repository\Repository;
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
abstract class ImportIndexHandler
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
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @param CommandBus         $commandBus
     * @param Repository         $repository
     * @param FormatTransformers $formatTransformers
     * @param LoopInterface      $loop
     */
    public function __construct(
        CommandBus $commandBus,
        Repository $repository,
        FormatTransformers $formatTransformers,
        LoopInterface $loop
    ) {
        $this->commandBus = $commandBus;
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

        $this
            ->commandBus
            ->execute(new IndexItems($repositoryReference, $token, []))
            ->then(function () use ($command) {
                return $this->getStreamByCommand($command);
            })
            ->otherwise(function (\Throwable $throwable) use ($deferred) {
                $deferred->reject($throwable);
            })
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
            });

        return $deferred->promise();
    }

    /**
     * @param CommandWithRepositoryReferenceAndToken $command
     *
     * @return PromiseInterface
     */
    abstract protected function getStreamByCommand(CommandWithRepositoryReferenceAndToken $command): PromiseInterface;

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
        $callsDeferred = [];
        $firstRow = true;
        $numberOfRows = null;
        $firstRowArray = [];
        $formatTransformer = null;
        $items = [];

        $stream->on('data', function ($data) use ($repositoryReference, $token, &$items, &$firstRow, &$firstRowArray, &$numberOfRows, &$formatTransformer, $deferred, $stream, &$callsDeferred) {
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
                    $callsDeferred[] = $newDeferred->promise();
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

        $stream->on('close', function () use ($repositoryReference, $token, $deferred, &$items, &$callsDeferred) {
            $this
                ->loop
                ->futureTick(function () use ($repositoryReference, $token, $items, $deferred, &$callsDeferred) {
                    return $this
                        ->commandBus
                        ->execute(new IndexItems($repositoryReference, $token, $items))
                        ->then(function () use (&$callsDeferred) {
                            return all($callsDeferred);
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
