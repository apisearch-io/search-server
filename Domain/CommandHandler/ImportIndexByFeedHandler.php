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
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\Format\FormatTransformers;
use Apisearch\Server\Domain\Repository\Repository\Repository;
use Apisearch\Server\Domain\Resource\ResourceLoader;
use Drift\CommandBus\Bus\CommandBus;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class ImportIndexHandler.
 */
class ImportIndexByFeedHandler extends ImportIndexHandler
{
    /**
     * @var ResourceLoader
     */
    private $resourceLoader;

    /**
     * @param CommandBus         $commandBus
     * @param Repository         $repository
     * @param FormatTransformers $formatTransformers
     * @param LoopInterface      $loop
     * @param ResourceLoader     $resourceLoader
     */
    public function __construct(
        CommandBus $commandBus,
        Repository $repository,
        FormatTransformers $formatTransformers,
        LoopInterface $loop,
        ResourceLoader $resourceLoader
    ) {
        parent::__construct(
            $commandBus,
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
     * @return PromiseInterface
     */
    protected function getStreamByCommand(CommandWithRepositoryReferenceAndToken $command): PromiseInterface
    {
        return $this
            ->resourceLoader
            ->getByPath($command->getFeed());
    }
}
