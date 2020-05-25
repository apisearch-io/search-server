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

namespace Apisearch\Server\Domain\Repository\Repository;

use Apisearch\Model\Changes;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use React\Promise\PromiseInterface;

/**
 * Interface ItemsRepository.
 */
interface ItemsRepository
{
    /**
     * Generate items documents.
     *
     * @param RepositoryReference $repositoryReference
     * @param Item[]              $items
     *
     * @return PromiseInterface
     */
    public function addItems(
        RepositoryReference $repositoryReference,
        array $items
    ): PromiseInterface;

    /**
     * Delete items.
     *
     * @param RepositoryReference $repositoryReference
     * @param ItemUUID[]          $itemUUIDs
     *
     * @return PromiseInterface
     */
    public function deleteItems(
        RepositoryReference $repositoryReference,
        array $itemUUIDs
    ): PromiseInterface;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface
     */
    public function deleteItemsByQuery(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface;

    /**
     * Update items.
     *
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     * @param Changes             $changes
     *
     * @return PromiseInterface
     */
    public function updateItems(
        RepositoryReference $repositoryReference,
        Query $query,
        Changes $changes
    ): PromiseInterface;
}
