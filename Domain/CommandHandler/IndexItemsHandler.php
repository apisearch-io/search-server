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

use Apisearch\Model\Item;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\Event\ItemsWereIndexed;
use Apisearch\Server\Domain\WithRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class IndexItemsHandler.
 */
class IndexItemsHandler extends WithRepositoryAndEventPublisher
{
    /**
     * Index items.
     *
     * @param IndexItems $indexItems
     *
     * @return PromiseInterface
     */
    public function handle(IndexItems $indexItems): PromiseInterface
    {
        $repositoryReference = $indexItems->getRepositoryReference();
        $items = $indexItems->getItems();

        return $this
            ->repository
            ->addItems(
                $repositoryReference,
                $items
            )
            ->then(function () use ($repositoryReference, $items) {
                return $this
                    ->eventBus
                    ->dispatch(
                        (new ItemsWereIndexed(array_map(function (Item $item) {
                            return $item->getUUID();
                        }, $items)))
                            ->withRepositoryReference($repositoryReference)
                    );
            });
    }
}
