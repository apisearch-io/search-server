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

use Apisearch\Server\Domain\Command\CreateIndex;
use Apisearch\Server\Domain\Event\IndexWasCreated;
use Apisearch\Server\Domain\WithConfigRepositoryAppRepositoryAndEventPublisher;
use function React\Promise\all;
use React\Promise\PromiseInterface;

/**
 * Class CreateIndexHandler.
 */
class CreateIndexHandler extends WithConfigRepositoryAppRepositoryAndEventPublisher
{
    /**
     * Create the index.
     *
     * @param CreateIndex $createIndex
     *
     * @return PromiseInterface
     */
    public function handle(CreateIndex $createIndex): PromiseInterface
    {
        $repositoryReference = $createIndex->getRepositoryReference();
        $ownerToken = $createIndex->getToken();
        $indexUUID = $createIndex->getIndexUUID();
        $config = $createIndex->getConfig();

        return all([
                $this
                    ->appRepository
                    ->createIndex(
                        $repositoryReference,
                        $indexUUID,
                        $config
                    ),
                $this
                    ->configRepository
                    ->putConfig($repositoryReference, $config),
            ])
            ->then(function () use ($repositoryReference, $indexUUID, $config, $ownerToken) {
                return $this
                    ->eventBus
                    ->dispatch(
                        (new IndexWasCreated(
                            $indexUUID,
                            $config
                        ))
                            ->withRepositoryReference($repositoryReference)
                            ->dispatchedBy($ownerToken)
                    );
            });
    }
}
