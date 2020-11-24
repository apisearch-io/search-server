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

use Apisearch\Server\Domain\Command\ImportIndexByFeed;
use Apisearch\Server\Domain\Command\ImportIndexByStream;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\Format\FormatTransformers;
use Apisearch\Server\Domain\Repository\Repository\Repository;
use Apisearch\Server\Domain\Resource\ResourceLoader;
use Drift\CommandBus\Bus\CommandBus;
use Drift\CommandBus\Bus\QueryBus;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;

/**
 * Class ImportIndexHandler.
 */
class ImportIndexByFeedHandler extends ImportIndexHandler
{
    private ResourceLoader $resourceLoader;

    /**
     * @param CommandBus         $commandBus
     * @param QueryBus           $queryBus
     * @param Repository         $repository
     * @param FormatTransformers $formatTransformers
     * @param LoopInterface      $loop
     * @param ResourceLoader     $resourceLoader
     */
    public function __construct(
        CommandBus $commandBus,
        QueryBus $queryBus,
        Repository $repository,
        FormatTransformers $formatTransformers,
        LoopInterface $loop,
        ResourceLoader $resourceLoader
    ) {
        parent::__construct(
            $commandBus,
            $queryBus,
            $repository,
            $formatTransformers,
            $loop
        );

        $this->resourceLoader = $resourceLoader;
    }

    /**
     * @param ImportIndexByFeed $importIndex
     *
     * @return PromiseInterface
     */
    public function handle(ImportIndexByFeed $importIndex): PromiseInterface
    {
        return $this->handleByCommand($importIndex);
    }

    /**
     * @param CommandWithRepositoryReferenceAndToken $command
     *
     * @return PromiseInterface<ImportIndexByStream>
     */
    protected function getImportIndexByStream(CommandWithRepositoryReferenceAndToken $command): PromiseInterface
    {
        return $this
            ->resourceLoader
            ->getByPath($command->getFeed())
            ->then(function (ReadableStreamInterface $stream) use ($command) {
                return new ImportIndexByStream(
                    $command->getRepositoryReference(),
                    $command->getToken(),
                    $stream,
                    $command->shouldDeleteOldVersions(),
                    $command->getVersionUUID()
                );
            });
    }
}
