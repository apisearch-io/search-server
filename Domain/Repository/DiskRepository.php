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

namespace Apisearch\Server\Domain\Repository;

use Apisearch\Config\Config;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\Changes;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use React\Stream\DuplexStreamInterface;

/**
 * Class DiskRepository.
 */
class DiskRepository extends InMemoryRepository implements FullRepository
{
    /**
     * @var string
     */
    private $file;

    /**
     * @param string        $file
     * @param LoopInterface $loop
     */
    public function __construct(
        string $file,
        LoopInterface $loop
    ) {
        parent::__construct($loop);
        $this->file = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndices(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference) {
                return parent::getIndices($repositoryReference)
                    ->then(function (array $indices) {
                        return $this->saveToDisk()->then(function () use ($indices) {
                            return $indices;
                        });
                    });
            });
    }

    /**
     * {@inheritdoc}
     */
    public function createIndex(RepositoryReference $repositoryReference, IndexUUID $indexUUID, Config $config): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $indexUUID, $config) {
                return parent::createIndex($repositoryReference, $indexUUID, $config)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureIndex(RepositoryReference $repositoryReference, IndexUUID $indexUUID, Config $config): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $indexUUID, $config) {
                return parent::configureIndex($repositoryReference, $indexUUID, $config)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteIndex(RepositoryReference $repositoryReference, IndexUUID $indexUUID): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $indexUUID) {
                return parent::deleteIndex($repositoryReference, $indexUUID)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex(RepositoryReference $repositoryReference, IndexUUID $indexUUID): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $indexUUID) {
                return parent::resetIndex($repositoryReference, $indexUUID)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
    }

    /**
     * {@inheritdoc}
     */
    public function addItems(RepositoryReference $repositoryReference, array $items): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $items) {
                return parent::addItems($repositoryReference, $items)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(RepositoryReference $repositoryReference, array $itemUUIDs): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $itemUUIDs) {
                return parent::deleteItems($repositoryReference, $itemUUIDs)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
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
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $query) {
                return parent::deleteItemsByQuery($repositoryReference, $query)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
    }

    /**
     * {@inheritdoc}
     */
    public function updateItems(RepositoryReference $repositoryReference, Query $query, Changes $changes): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $query, $changes) {
                return parent::updateItems($repositoryReference, $query, $changes)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
    }

    /**
     * {@inheritdoc}
     */
    public function query(RepositoryReference $repositoryReference, Query $query): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $query) {
                return parent::query($repositoryReference, $query);
            });
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
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $query, $itemsUUID) {
                return parent::querySimilar($repositoryReference, $query, $itemsUUID);
            });
    }

    /**
     * Export index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface<DuplexStreamInterface>
     */
    public function exportIndex(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference) {
                return parent::exportIndex($repositoryReference);
            });
    }

    /**
     * Save to disk.
     *
     * @return PromiseInterface
     */
    private function saveToDisk(): PromiseInterface
    {
        @\unlink($this->file);
        \touch($this->file);
        \file_put_contents($this->file, \serialize($this->indices));

        return resolve();
    }

    /**
     * Load from disk.
     *
     * @return PromiseInterface
     */
    private function loadFromDisk(): PromiseInterface
    {
        $content = @\file_get_contents($this->file);
        if (!\is_string($content)) {
            $this->indices = [];

            return resolve();
        }

        $content = \unserialize($content);
        $this->indices = \is_array($content)
            ? $content
            : [];

        return resolve();
    }
}
