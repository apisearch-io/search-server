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

use Apisearch\Server\Domain\Command\DeleteItemsByQuery;
use Apisearch\Server\Domain\Event\ItemsWereDeletedByQuery;
use Apisearch\Server\Domain\WithRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class DeleteItemsByQueryHandler.
 */
class DeleteItemsByQueryHandler extends WithRepositoryAndEventPublisher
{
    /**
     * @param DeleteItemsByQuery $deleteItemsByQuery
     *
     * @return PromiseInterface
     */
    public function handle(DeleteItemsByQuery $deleteItemsByQuery): PromiseInterface
    {
        $repositoryReference = $deleteItemsByQuery->getRepositoryReference();
        $query = $deleteItemsByQuery->getQuery();

        return $this
            ->repository
            ->deleteItemsByQuery(
                $repositoryReference,
                $query
            )
            ->then(function () use ($repositoryReference, $query) {
                return $this
                    ->eventBus
                    ->dispatch(
                        (new ItemsWereDeletedByQuery($query))
                            ->withRepositoryReference($repositoryReference)
                    );
            });
    }
}
