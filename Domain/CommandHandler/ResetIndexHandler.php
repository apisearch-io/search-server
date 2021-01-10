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

use Apisearch\Server\Domain\Command\ResetIndex;
use Apisearch\Server\Domain\Event\IndexWasReset;
use Apisearch\Server\Domain\WithAppRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class ResetIndexHandler.
 */
class ResetIndexHandler extends WithAppRepositoryAndEventPublisher
{
    /**
     * Reset the index.
     *
     * @param ResetIndex $resetIndex
     *
     * @return PromiseInterface
     */
    public function handle(ResetIndex $resetIndex): PromiseInterface
    {
        $repositoryReference = $resetIndex->getRepositoryReference();
        $ownerToken = $resetIndex->getToken();
        $indexUUID = $resetIndex->getIndexUUID();

        return $this
            ->appRepository
            ->resetIndex(
                $repositoryReference,
                $indexUUID
            )
            ->then(function () use ($repositoryReference, $indexUUID, $ownerToken) {
                return $this
                    ->eventBus
                    ->dispatch(
                        (new IndexWasReset($indexUUID))
                            ->withRepositoryReference($repositoryReference)
                            ->dispatchedBy($ownerToken)
                    );
            });
    }
}
