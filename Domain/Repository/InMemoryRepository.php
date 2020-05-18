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
use Apisearch\Exception\ResourceExistsException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Changes;
use Apisearch\Model\Index;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Item;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use function Drift\React\wait_for_stream_listeners;
use function React\Promise\resolve;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Stream\DuplexStreamInterface;
use React\Stream\ThroughStream;

/**
 * Class InMemoryRepository.
 */
class InMemoryRepository implements FullRepository, ResetableRepository
{
    /**
     * @var Index[]
     */
    protected $indices = [];

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndices(RepositoryReference $repositoryReference): PromiseInterface
    {
        return resolve($this->getIndexBlocksByRepositoryReference($repositoryReference, 'index'));
    }

    /**
     * {@inheritdoc}
     */
    public function createIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID,
        Config $config
    ): PromiseInterface {
        return resolve()
            ->then(function () use ($repositoryReference, $indexUUID, $config) {
                $appUUID = $repositoryReference->getAppUUID();
                $appUUIDComposed = $appUUID->composeUUID();
                $indexUUIDComposed = $indexUUID->composeUUID();

                if (!\array_key_exists($appUUIDComposed, $this->indices)) {
                    $this->indices[$appUUIDComposed] = [];
                } elseif (\array_key_exists($indexUUIDComposed, $this->indices[$appUUIDComposed])) {
                    throw new ResourceExistsException();
                }

                $this->setIndex(
                    $appUUID,
                    $indexUUID,
                    $config,
                    []
                );
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID,
        Config $config
    ): PromiseInterface {
        return resolve()
            ->then(function () use ($repositoryReference, $indexUUID, $config) {
                $appUUID = $repositoryReference->getAppUUID();
                $appUUIDComposed = $appUUID->composeUUID();
                $indexUUIDComposed = $indexUUID->composeUUID();

                $this->throwExceptionIfNonExistingIndex($appUUIDComposed, $indexUUIDComposed);
                $this->setIndex(
                    $appUUID,
                    $indexUUID,
                    $config,
                    $this->indices[$appUUIDComposed][$indexUUIDComposed]['items']
                );
            });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID
    ): PromiseInterface {
        return resolve()
            ->then(function () use ($repositoryReference, $indexUUID) {
                $appUUID = $repositoryReference->getAppUUID();
                $appUUIDComposed = $appUUID->composeUUID();
                $indexUUIDComposed = $indexUUID->composeUUID();
                $this->throwExceptionIfNonExistingIndex($appUUIDComposed, $indexUUIDComposed);

                unset($this->indices[$appUUIDComposed][$indexUUIDComposed]);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID
    ): PromiseInterface {
        return resolve()
            ->then(function () use ($repositoryReference, $indexUUID) {
                $appUUID = $repositoryReference->getAppUUID();
                $this->throwExceptionIfNonExistingIndex(
                    $appUUID->composeUUID(),
                    $indexUUID->composeUUID()
                );
            });
    }

    /**
     * {@inheritdoc}
     */
    public function query(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        return resolve($this->queryMatchAll(
            $repositoryReference,
            $query
        ));
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
        $stream = new ThroughStream();

        wait_for_stream_listeners($stream, $this->loop, 1, 1)
            ->then(function (ThroughStream $stream) use ($repositoryReference) {
                $itemsAsArray = $this->getIndexBlocksByRepositoryReference(RepositoryReference::create(
                    $repositoryReference->getAppUUID(),
                    $repositoryReference->getIndexUUID()
                ), 'items');

                foreach ($itemsAsArray as $indexItems) {
                    foreach ($indexItems as $item) {
                        if (!$stream->isWritable()) {
                            continue 2;
                        }

                        $stream->write($item);
                    }
                }

                $stream->end();
            });

        return resolve($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function addItems(
        RepositoryReference $repositoryReference,
        array $items
    ): PromiseInterface {
        return resolve()
            ->then(function () use ($repositoryReference, $items) {
                $appUUID = $repositoryReference->getAppUUID();
                $appUUIDComposed = $appUUID->composeUUID();
                $indexUUID = $repositoryReference->getIndexUUID();
                $indexUUIDComposed = $indexUUID->composeUUID();
                $this->throwExceptionIfNonExistingIndex($appUUIDComposed, $indexUUIDComposed);

                foreach ($items as $item) {
                    $this->indices[$appUUIDComposed][$indexUUIDComposed]['items'][$item->composeUUID()] = $item;
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(
        RepositoryReference $repositoryReference,
        array $itemUUIDs
    ): PromiseInterface {
        return resolve()
            ->then(function () use ($repositoryReference, $itemUUIDs) {
                $appUUID = $repositoryReference->getAppUUID();
                $appUUIDComposed = $appUUID->composeUUID();
                $indexUUID = $repositoryReference->getIndexUUID();
                $indexUUIDComposed = $indexUUID->composeUUID();
                $this->throwExceptionIfNonExistingIndex($appUUIDComposed, $indexUUIDComposed);
                $items = &$this->indices[$appUUIDComposed][$indexUUIDComposed]['items'];

                foreach ($itemUUIDs as $itemUUID) {
                    $itemUUIDComposed = $itemUUID->composeUUID();
                    if (\array_key_exists($itemUUIDComposed, $items)) {
                        unset($items[$itemUUIDComposed]);
                    }
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function updateItems(
        RepositoryReference $repositoryReference,
        Query $query,
        Changes $changes
    ): PromiseInterface {
        // Action not available in this adapter

        return resolve()
            ->then(function () use ($repositoryReference, $query, $changes) {
                $appUUID = $repositoryReference->getAppUUID();
                $indexUUID = $repositoryReference->getIndexUUID();
                $this->throwExceptionIfNonExistingIndex(
                    $appUUID->composeUUID(),
                    $indexUUID->composeUUID()
                );
            });
    }

    /**
     * Throw exception if index not exists.
     *
     * @param string $appUUIDComposed
     * @param string $indexUUIDComposed
     *
     * @throws ResourceNotAvailableException
     */
    private function throwExceptionIfNonExistingIndex(
        string $appUUIDComposed,
        string $indexUUIDComposed
    ) {
        if (
            !\array_key_exists($appUUIDComposed, $this->indices) ||
            !\is_array($this->indices[$appUUIDComposed]) ||
            !\array_key_exists($indexUUIDComposed, $this->indices[$appUUIDComposed])
        ) {
            throw new ResourceNotAvailableException();
        }
    }

    /**
     * Set index.
     *
     * @param AppUUID   $appUUID
     * @param IndexUUID $indexUUID
     * @param Config    $config
     * @param array     $items
     */
    private function setIndex(
        AppUUID $appUUID,
        IndexUUID $indexUUID,
        Config $config,
        array $items
    ) {
        $appUUIDComposed = $appUUID->composeUUID();
        $indexUUIDComposed = $indexUUID->composeUUID();
        $this->indices[$appUUIDComposed][$indexUUIDComposed] = [
            'items' => $items,
            'index' => (new Index(
                $indexUUID,
                $appUUID,
                true,
                \count($items),
                '',
                $config->getReplicas(),
                $config->getShards()
            ))->toArray(),
        ];
    }

    /**
     * Query match all.
     *
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return Result
     */
    public function queryMatchAll(
        RepositoryReference $repositoryReference,
        Query $query
    ): Result {
        $subqueries = $query->getSubqueries();
        if (!empty($subqueries)) {
            $results = \array_map(function (Query $query) use ($repositoryReference) {
                return $this->queryMatchAll(
                    $repositoryReference,
                    $query
                );
            }, $subqueries);

            return Result::createMultiResult($results);
        }

        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $query->getIndexUUID() instanceof IndexUUID
            ? $query->getIndexUUID()
            : $repositoryReference->getIndexUUID();

        $items = [];
        $itemsAsArray = $this->getIndexBlocksByRepositoryReference(RepositoryReference::create(
            $appUUID,
            $indexUUID
        ), 'items');

        foreach ($itemsAsArray as $itemsPosition) {
            $items += $itemsPosition;
        }

        if (!\is_null($query->getFilter('_id'))) {
            $filteredItems = $query->getFilter('_id')->getValues();
            $items = \array_filter($items, function ($item) use ($filteredItems) {
                return \in_array($item->composeUUID(), $filteredItems);
            });
        }

        $itemCopies = [];
        foreach ($items as $item) {
            $itemCopies[] = Item::createFromArray($item->toArray());
        }
        $items = $itemCopies;

        $fields = $query->getFields();
        if ($fields) {
            if (\in_array('metadata.field', $fields)) {
                foreach ($items as $key => $item) {
                    $field = $item->getMetadata()['field'] ?? null;
                    \is_null($field)
                        ? $items[$key]->setMetadata([])
                        : $items[$key]->setMetadata(['field' => $field]);
                }
            }

            if (\in_array('!metadata.field', $fields)) {
                foreach ($items as $key => $item) {
                    $metadata = $item->getMetadata();
                    unset($metadata['field']);
                    $items[$key]->setMetadata($metadata);
                }
            }
        }

        $from = $query->getFrom();
        $n = $query->getSize();
        $slicedItems = \array_slice($items, $from, $n);

        return Result::create(
            null,
            \count($items),
            \count($items),
            null,
            [],
            \array_values($slicedItems)
        );
    }

    /**
     * Given a RepositoryReference, return all index blocks.
     *
     * @param RepositoryReference $repositoryReference
     * @param string              $field
     *
     * @return array
     */
    private function getIndexBlocksByRepositoryReference(
        RepositoryReference $repositoryReference,
        string $field
    ): array {
        $elements = [];
        $appUUID = $repositoryReference->getAppUUID();
        $appUUIDsComposed = ['*'];
        if ($appUUID instanceof AppUUID) {
            $appUUIDsComposed = \explode(',', $appUUID->composeUUID());
        }

        $indexUUID = $repositoryReference->getIndexUUID();
        $indexUUIDsComposed = ['*'];
        if ($indexUUID instanceof IndexUUID) {
            $indexUUIDsComposed = \explode(',', $indexUUID->composeUUID());
        }

        foreach ($appUUIDsComposed as $appUUIDComposed) {
            foreach ($this->indices as $currentAppUUIDComposed => $indices) {
                if (
                    !empty($appUUIDComposed) &&
                    '*' !== $appUUIDComposed &&
                    $appUUIDComposed !== $currentAppUUIDComposed
                ) {
                    continue;
                }

                foreach ($indices as $currentIndexUUIDComposed => $index) {
                    foreach ($indexUUIDsComposed as $indexUUIDComposed) {
                        if (
                            empty($indexUUIDComposed) ||
                            '*' == $indexUUIDComposed ||
                            $indexUUIDComposed == $currentIndexUUIDComposed
                        ) {
                            if (
                                !empty($appUUIDComposed) &&
                                '*' !== $appUUIDComposed &&
                                !empty($indexUUIDComposed) &&
                                '*' !== $indexUUIDComposed
                            ) {
                                $this->throwExceptionIfNonExistingIndex(
                                    $appUUIDComposed,
                                    $indexUUIDComposed
                                );
                            }

                            if ('index' === $field) {
                                $indexAsArray = $index['index'];
                                $indexAsArray['doc_count'] = \count($index['items']);
                                $indexAsArray['fields'] = $this->getFields($index['items']);
                                $elements[] = Index::createFromArray($indexAsArray);
                            } elseif ('items' === $field) {
                                $elements[] = $index[$field];
                            }
                        }
                    }
                }
            }
        }

        return $elements;
    }

    /**
     * @return PromiseInterface
     */
    public function reset(): PromiseInterface
    {
        $this->indices = [];

        return resolve();
    }

    /**
     * Get fields.
     *
     * @param Item[] $items
     *
     * @return string[]
     */
    private function getFields(array $items)
    {
        $fields = [
            'uuid.id' => 'string',
            'uuid.type' => 'string',
        ];

        $items = \array_slice($items, 0, 100);
        foreach ($items as $item) {
            $this->addFieldsFromArray($item->getMetadata(), 'metadata', $fields);
            $this->addFieldsFromArray($item->getIndexedMetadata(), 'indexed_metadata', $fields);
            $this->addFieldsFromArray($item->getSearchableMetadata(), 'searchable_metadata', $fields);
        }

        return $fields;
    }

    /**
     * Add fields from array.
     *
     * @param array  $array
     * @param string $prefix
     * @param array  $fields
     */
    private function addFieldsFromArray(
        array $array,
        string $prefix,
        array &$fields
    ) {
        foreach ($array as $field => $value) {
            $type = 'string';
            if (\is_int($value) || \is_float($value)) {
                $type = 'long';
            } elseif (\is_array($value)) {
                $type = 'object';
            }

            $fields["$prefix.".$field] = $type;
        }
    }
}