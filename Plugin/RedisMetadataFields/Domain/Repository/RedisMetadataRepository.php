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

namespace Apisearch\Plugin\RedisMetadataFields\Domain\Repository;

use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Plugin\Redis\Domain\RedisWrapper;
use Apisearch\Repository\RepositoryReference;
use React\Promise;
use React\Promise\PromiseInterface;

/**
 * Class RedisMetadataRepository.
 */
class RedisMetadataRepository
{
    /**
     * @var RedisWrapper
     *
     * RedisWrapper
     */
    private $redisWrapper;

    /**
     * @var string
     *
     * Key
     */
    private $key;

    /**
     * RedisMetadataRepository constructor.
     *
     * @param RedisWrapper $redisWrapper
     * @param string       $key
     */
    public function __construct(
        RedisWrapper $redisWrapper,
        string $key
    ) {
        $this->redisWrapper = $redisWrapper;
        $this->key = $key;
    }

    /**
     * Save Item metadata to storage.
     *
     * @param RepositoryReference $repositoryReference
     * @param Item[]              $items
     *
     * @return PromiseInterface
     */
    public function saveItemsMetadata(
        RepositoryReference $repositoryReference,
        array $items
    ): PromiseInterface {
        $promises = [];

        array_walk($items, function (Item $item) use ($repositoryReference) {
            $promises[] = $this
                ->redisWrapper
                ->getClient()
                ->hSet(
                    $this->key,
                    $this->composeKey($repositoryReference, $item->getUUID()),
                    json_encode($item->getMetadata())
                )
                ->then(function () use ($item) {
                    $item->setMetadata([]);
                });
        });

        return Promise\all($promises);
    }

    /**
     * Load Items metadata with locally saved data.
     *
     * @param RepositoryReference $repositoryReference
     * @param Item[]              $items
     *
     * @return PromiseInterface
     */
    public function loadItemsMetadata(
        RepositoryReference $repositoryReference,
        array $items
    ): PromiseInterface {
        $promises = [];

        array_walk($items, function (Item $item) use ($repositoryReference) {
            $promises[] = $this
                ->redisWrapper
                ->getClient()
                ->hGet(
                    $this->key,
                    $this->composeKey($repositoryReference, $item->getUUID())
                )
                ->then(function ($metadata) use ($item) {
                    $item->setMetadata(
                        (false === $metadata)
                            ? []
                            : json_decode($metadata, true)
                    );
                });
        });

        return Promise\all($promises);
    }

    /**
     * Delete Items metadata.
     *
     * @param RepositoryReference $repositoryReference
     * @param ItemUUID[]          $itemsUUID
     *
     * @return PromiseInterface
     */
    public function deleteItemsMetadata(
        RepositoryReference $repositoryReference,
        array $itemsUUID
    ): PromiseInterface {
        $promises = [];

        array_walk($itemsUUID, function (ItemUUID $itemUUID) use ($repositoryReference) {
            $promises[] = $this
                ->redisWrapper
                ->getClient()
                ->hDel(
                    $this->key,
                    $this->composeKey($repositoryReference, $itemUUID)
                );
        });

        return Promise\all($promises);
    }

    /**
     * Compose item key.
     *
     * @param RepositoryReference $repositoryReference
     * @param ItemUUID            $itemUUID
     *
     * @return string
     */
    public function composeKey(
        RepositoryReference $repositoryReference,
        ItemUUID $itemUUID
    ): string {
        return sprintf('%s~~%s',
            $repositoryReference->compose(),
            $itemUUID->composeUUID()
        );
    }
}
