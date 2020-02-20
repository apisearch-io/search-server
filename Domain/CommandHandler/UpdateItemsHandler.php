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

use Apisearch\Server\Domain\Command\UpdateItems;
use Apisearch\Server\Domain\Event\ItemsWereUpdated;
use Apisearch\Server\Domain\WithRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class UpdateItemsHandler.
 */
class UpdateItemsHandler extends WithRepositoryAndEventPublisher
{
    /**
     * Update items.
     *
     * @param UpdateItems $updateItems
     *
     * @return PromiseInterface
     */
    public function handle(UpdateItems $updateItems): PromiseInterface
    {
        $repositoryReference = $updateItems->getRepositoryReference();
        $query = $updateItems->getQuery();
        $changes = $updateItems->getChanges();

        return $this
            ->repository
            ->updateItems(
                $repositoryReference,
                $query,
                $changes
            )
            ->then(function () use ($repositoryReference, $query, $changes) {
                return $this
                    ->eventBus
                    ->dispatch(
                        (new ItemsWereUpdated(
                            $query->getFilters(),
                            $changes
                        ))
                            ->withRepositoryReference($repositoryReference)
                    );
            });
    }
}
