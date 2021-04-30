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
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\Event\ItemsWereIndexed;
use Apisearch\Server\Domain\Model\ItemMerger;
use Apisearch\Server\Domain\WithRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;
use Apisearch\Query\Query as ModelQuery;

/**
 * Class PatchItemsHandler.
 */
class PatchItemsHandler extends WithRepositoryAndEventPublisher
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
        $ownerToken = $indexItems->getToken();
        $items = $indexItems->getItems();
        $itemsIndexedByUUID = [];
        $itemsUUID = array_map(function(Item $item) use (&$itemsIndexedByUUID) {
            $itemsIndexedByUUID[$item->composeUUID()] = $item;

            return $item->getUUID();
        }, $items);

        return $this
            ->repository
            ->query($repositoryReference, ModelQuery::createByUUIDs($itemsUUID))
            ->then(function(Result $result) use ($itemsIndexedByUUID) {
                return array_map(function(Item $item) use ($itemsIndexedByUUID) {
                    $itemComposedUUID = $item->composeUUID();

                    return array_key_exists($itemComposedUUID, $itemsIndexedByUUID)
                        ? Item::createFromArray(
                            ItemMerger::mergeItems(
                                $item->toArray(),
                                $itemsIndexedByUUID[$itemComposedUUID]->toArray(),
                            )
                        )
                        : false;
                }, $result->getItems());
            })
            ->then(function(array $items) use ($repositoryReference) {
                return $this
                    ->repository
                    ->addItems($repositoryReference, array_filter($items));
            })
            ->then(function () use ($repositoryReference, $items, $ownerToken) {
                return $this
                    ->eventBus
                    ->dispatch(
                        (new ItemsWereIndexed(\array_map(function (Item $item) {
                            return $item->getUUID();
                        }, $items)))
                            ->withRepositoryReference($repositoryReference)
                            ->dispatchedBy($ownerToken)
                    );
            });
    }
}
