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

use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\Changes;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use React\Promise\PromiseInterface;
use React\Stream\DuplexStreamInterface;

/**
 * Class Repository.
 */
final class Repository
{
    private ItemsRepository $itemsRepository;
    private QueryRepository $queryRepository;

    /**
     * Repository constructor.
     *
     * @param ItemsRepository $itemsRepository
     * @param QueryRepository $queryRepository
     */
    public function __construct(
        ItemsRepository $itemsRepository,
        QueryRepository $queryRepository
    ) {
        $this->itemsRepository = $itemsRepository;
        $this->queryRepository = $queryRepository;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Item[]              $items
     *
     * @return PromiseInterface
     */
    public function addItems(
        RepositoryReference $repositoryReference,
        array $items
    ): PromiseInterface {
        return $this
            ->itemsRepository
            ->addItems(
                $repositoryReference,
                $items
            );
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param ItemUUID[]          $itemsUUID
     *
     * @return PromiseInterface
     */
    public function deleteItems(
        RepositoryReference $repositoryReference,
        array $itemsUUID
    ): PromiseInterface {
        return $this
            ->itemsRepository
            ->deleteItems(
                $repositoryReference,
                $itemsUUID
            );
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface
     */
    public function deleteItemsByQuery(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        return $this
            ->itemsRepository
            ->deleteItemsByQuery(
                $repositoryReference,
                $query
            );
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface<Result>
     *
     * @throws ResourceNotAvailableException
     */
    public function query(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        return $this
            ->queryRepository
            ->query(
                $repositoryReference,
                $query
            );
    }

    /**
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
    ): PromiseInterface {
        return $this
            ->itemsRepository
            ->updateItems(
                $repositoryReference,
                $query,
                $changes
            );
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     * @param ItemUUID[]          $itemsUUID
     *
     * @return PromiseInterface<Result>
     *
     * @throws ResourceNotAvailableException
     */
    public function querySimilar(
        RepositoryReference $repositoryReference,
        Query $query,
        array $itemsUUID
    ): PromiseInterface {
        return $this
            ->queryRepository
            ->querySimilar(
                $repositoryReference,
                $query,
                $itemsUUID
            );
    }

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface<DuplexStreamInterface>
     */
    public function exportIndex(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->queryRepository
            ->exportIndex($repositoryReference);
    }
}
