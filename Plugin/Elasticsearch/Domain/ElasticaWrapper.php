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

namespace Apisearch\Plugin\Elasticsearch\Domain;

use Apisearch\Config\Config;
use Apisearch\Config\Synonym;
use Apisearch\Exception\ResourceExistsException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Exception\TransportableException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Index as ApisearchIndex;
use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\Elasticsearch\Adapter\AsyncClient;
use Apisearch\Plugin\Elasticsearch\Adapter\AsyncMultiSearch;
use Apisearch\Plugin\Elasticsearch\Adapter\AsyncScroll;
use Apisearch\Plugin\Elasticsearch\Adapter\AsyncSearch;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Exception\ParsedCreatingIndexException;
use Apisearch\Server\Domain\Exception\ParsedResourceNotAvailableException;
use Apisearch\Server\Domain\Exception\ResponseException;
use Elastica\Document;
use Elastica\Exception\Bulk\ResponseException as BulkResponseException;
use Elastica\Query;
use Elastica\Request;
use Elastica\Response;
use Elasticsearch\Endpoints\AbstractEndpoint;
use Elasticsearch\Endpoints\Bulk;
use Elasticsearch\Endpoints\Cat\Aliases;
use Elasticsearch\Endpoints\Cat\Indices;
use Elasticsearch\Endpoints\Cluster\Health;
use Elasticsearch\Endpoints\DeleteByQuery;
use Elasticsearch\Endpoints\Indices\Create as CreateIndex;
use Elasticsearch\Endpoints\Indices\Delete as DeleteIndex;
use Elasticsearch\Endpoints\Indices\DeleteAlias;
use Elasticsearch\Endpoints\Indices\GetMapping;
use Elasticsearch\Endpoints\Indices\PutMapping;
use Elasticsearch\Endpoints\Indices\Refresh;
use Elasticsearch\Endpoints\Indices\UpdateAliases;
use Elasticsearch\Endpoints\Reindex;
use Elasticsearch\Serializers\ArrayToJSONSerializer;
use React\EventLoop\LoopInterface;
use React\Promise;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;

/**
 * Class ElasticaWrapper.
 */
class ElasticaWrapper implements AsyncRequestAccessor
{
    private AsyncClient $client;
    private LoopInterface $loop;

    /**
     * Construct.
     *
     * @param AsyncClient   $client
     * @param LoopInterface $loop
     */
    public function __construct(
        AsyncClient $client,
        LoopInterface $loop
    ) {
        $this->client = $client;
        $this->loop = $loop;
    }

    /**
     * Get index prefix.
     *
     * @return string
     */
    public function getAliasPrefix(): string
    {
        return 'apisearch_item';
    }

    /**
     * Get index prefix.
     *
     * @return string
     */
    public function generateRandomIndexPrefix(): string
    {
        $randomID = \rand(100000000000, 1000000000000);

        return "apisearch_{$randomID}_item";
    }

    /**
     * Get random index name.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return string
     */
    public function getRandomIndexName(RepositoryReference $repositoryReference): string
    {
        return $this->buildIndexReference(
            $repositoryReference,
            $this->generateRandomIndexPrefix()
        );
    }

    /**
     * Get index alias name.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return string
     */
    public function getIndexAliasName(RepositoryReference $repositoryReference): string
    {
        return $this->buildIndexReference(
            $repositoryReference,
            $this->getAliasPrefix()
        );
    }

    /**
     * Get index not available exception.
     *
     * @param string $message
     *
     * @return TransportableException
     */
    public function getIndexNotAvailableException(string $message): TransportableException
    {
        return ParsedResourceNotAvailableException::parsedIndexNotAvailable($message);
    }

    /**
     * Get index configuration.
     *
     * @param Config $config
     *
     * @return array
     */
    public function getImmutableIndexConfiguration(Config $config): array
    {
        $language = $config->getLanguage();

        $defaultAnalyzerFilter = [
            5 => 'lowercase',
            20 => 'asciifolding',
            50 => 'edge_ngram_filter',
        ];

        $searchAnalyzerFilter = [
            5 => 'lowercase',
            50 => 'asciifolding',
        ];

        $exactSearchAnalyzerFilter = [
            5 => 'lowercase',
            50 => 'asciifolding',
        ];

        $indexConfiguration = [
            'number_of_shards' => $config->getShards(),
            'number_of_replicas' => $config->getReplicas(),
            'max_result_window' => 50000,
            'analysis' => [
                'analyzer' => [
                    'default' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [],
                    ],
                    'search_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [],
                    ],
                    'exact_search_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'keyword',
                        'filter' => [],
                    ],
                ],
                'filter' => [
                    'edge_ngram_filter' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 1,
                        'max_gram' => 20,
                    ],
                ],
            ],
        ];

        $stopWordsLanguage = ElasticaLanguages::getStopWordsLanguageByIso($language);
        if (!\is_null($stopWordsLanguage)) {
            $defaultAnalyzerFilter[30] = 'stop_words';
            $exactSearchAnalyzerFilter[30] = 'stop_words';
            $indexConfiguration['analysis']['filter']['stop_words'] = [
                'type' => 'stop',
                'stopwords' => $stopWordsLanguage,
            ];
        }

        $stemmer = ElasticaLanguages::getStemmerLanguageByIso($language);
        if (!\is_null($stemmer)) {
            $searchAnalyzerFilter[35] = 'stemmer';
            $indexConfiguration['analysis']['filter']['stemmer'] = [
                'type' => 'stemmer',
                'name' => $stemmer,
            ];
        }

        $synonyms = $config->getSynonyms();
        if (!empty($synonyms)) {
            $defaultAnalyzerFilter[40] = 'synonym';
            $exactSearchAnalyzerFilter[40] = 'synonym';
            $indexConfiguration['analysis']['filter']['synonym'] = [
                'type' => 'synonym',
                'synonyms' => \array_map(function (Synonym $synonym) {
                    return \strtolower($synonym->expand());
                }, $synonyms),
            ];
        }

        \ksort($defaultAnalyzerFilter, SORT_NUMERIC);
        \ksort($searchAnalyzerFilter, SORT_NUMERIC);
        $indexConfiguration['analysis']['analyzer']['default']['filter'] = \array_values($defaultAnalyzerFilter);
        $indexConfiguration['analysis']['analyzer']['search_analyzer']['filter'] = \array_values($searchAnalyzerFilter);
        $indexConfiguration['analysis']['analyzer']['exact_search_analyzer']['filter'] = \array_values($exactSearchAnalyzerFilter);

        return ['settings' => $indexConfiguration];
    }

    /**
     * Build index mapping.
     *
     * @param Config $config
     *
     * @return array
     */
    public function getIndexMapping(Config $config): array
    {
        $mapping = [];
        $mapping['date_detection'] = true;
        $mapping['dynamic_date_formats'] = [
            'strict_date_optional_time',
            'strict_date_hour',
            'strict_date_hour_minute',
            'strict_date_hour_minute_second',
        ];

        $mapping['dynamic_templates'] = [
            [
                'dynamic_metadata_as_keywords' => [
                    'path_match' => 'indexed_metadata.*',
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
            [
                'dynamic_searchable_metadata_as_text' => [
                    'path_match' => 'searchable_metadata.*',
                    'mapping' => [
                        'type' => 'text',
                        'analyzer' => 'default',
                        'search_analyzer' => 'search_analyzer',
                    ],
                ],
            ],
            [
                'dynamic_arrays_as_nested' => [
                    'path_match' => 'indexed_metadata.*',
                    'match_mapping_type' => 'object',
                    'mapping' => [
                        'type' => 'nested',
                    ],
                ],
            ],
            [
                'metadata_as_non_indexed' => [
                    'path_match' => 'metadata.*',
                    'mapping' => [
                        'index' => false,
                    ],
                ],
            ],
        ];

        $sourceExcludes = [];
        if (!$config->shouldSearchableMetadataBeStored()) {
            $sourceExcludes = [
                'searchable_metadata',
                'exact_matching_metadata',
            ];
        }

        $mapping['_source'] = ['excludes' => $sourceExcludes];
        $mapping['properties'] = [
            'uuid' => [
                'type' => 'object',
                'dynamic' => 'strict',
                'properties' => [
                    'id' => [
                        'type' => 'keyword',
                    ],
                    'type' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
            'coordinate' => ['type' => 'geo_point'],
            'metadata' => [
                'type' => 'object',
                'dynamic' => true,
            ],
            'indexed_metadata' => [
                'type' => 'object',
                'dynamic' => true,
            ],
            'searchable_metadata' => [
                'type' => 'object',
                'dynamic' => true,
            ],
            'exact_matching_metadata' => [
                'type' => 'text',
                'analyzer' => 'exact_search_analyzer',
            ],
            'suggest' => [
                'type' => 'completion',
                'analyzer' => 'search_analyzer',
            ],
        ];

        return $mapping;
    }

    /**
     * Get indices.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface<ApisearchIndex[]>
     */
    public function getIndices(RepositoryReference $repositoryReference): PromiseInterface
    {
        $appUUIDComposed = $repositoryReference->getAppUUID() instanceof AppUUID
            ? $repositoryReference
                ->getAppUUID()
                ->composeUUID()
            : null;

        $indexUUIDComposed = $repositoryReference->getIndexUUID() instanceof IndexUUID
            ? $repositoryReference
                ->getIndexUUID()
                ->composeUUID()
            : null;

        $indexPrefix = $this->getAliasPrefix();

        $indexSearchKeyword = $indexPrefix.'_'.(
            empty($appUUIDComposed)
                ? '*'
                : $appUUIDComposed.'_'.(
                empty($indexUUIDComposed)
                    ? '*'
                    : $indexUUIDComposed
                )
            );

        $indicesPromise = $this
            ->client
            ->requestAsyncEndpoint((new Indices()), $indexSearchKeyword);

        $mappingPromise = $this
            ->client
            ->requestAsyncEndpoint((new GetMapping()), $indexSearchKeyword);

        return
            Promise\all([
                $indicesPromise,
                $mappingPromise,
            ])
            ->then(function (array $responses) {
                list($elasticaResponse, $elasticaMappingResponse) = $responses;
                $mappingData = $this->getMappingMetadataByResponse($elasticaMappingResponse->getData());

                if (empty($elasticaResponse->getData())) {
                    return [];
                }

                $regexToParse = '/^'.
                    '(?P<color>[^\ ]+)\s+'.
                    '(?P<status>[^\ ]+)\s+'.
                    '(?P<fullname>apisearch_\d+_item_(?P<app_id>[^_]+)_(?P<id>[^\ ]+))\s+'.
                    '(?P<uuid>[^\ ]+)\s+'.
                    '(?P<primary_shards>[^\ ]+)\s+'.
                    '(?P<replica_shards>[^\ ]+)\s+'.
                    '(?P<doc_count>[^\ ]+)\s+'.
                    '(?P<doc_deleted>[^\ ]+)\s+'.
                    '(?P<index_size>[^\ ]+)\s+'.
                    '(?P<storage_size>[^\ ]+)'.
                    '$/im';

                $indices = [];
                \preg_match_all($regexToParse, $elasticaResponse->getData()['message'], $matches, PREG_SET_ORDER, 0);
                if ($matches) {
                    foreach ($matches as $metaData) {
                        $indices[] = new ApisearchIndex(
                            IndexUUID::createById($metaData['id']),
                            AppUUID::createById($metaData['app_id']),
                            (
                                'open' === $metaData['status'] &&
                                \in_array($metaData['color'], ['green', 'yellow'])
                            ),
                            (int) $metaData['doc_count'],
                            (string) $metaData['index_size'],
                            (int) $metaData['primary_shards'],
                            (int) $metaData['replica_shards'],
                            $mappingData[$metaData['fullname']] ?? [],
                            [
                                'allocated' => ('green' === $metaData['color']),
                                'doc_deleted' => (int) $metaData['doc_deleted'],
                                'remote_uuid' => $metaData['uuid'],
                                'storage_size' => $metaData['storage_size'],
                            ]
                        );
                    }
                }

                return $indices;
            })
            ->otherwise(function (\Exception $e) {
                return [];
            });
    }

    /**
     * Given a Mapping response, create metadata values per index.
     *
     * @param array $response
     *
     * @return array
     */
    private function getMappingMetadataByResponse(array $response): array
    {
        $metadataData = [];
        foreach ($response as $indexId => $metadataValues) {
            $mappings = $metadataValues['mappings'];
            if (isset($mappings['item'])) {
                $mappings = $mappings['item'];
            }

            $metadataBucket = [];
            $this->getMappingProperties(
                $metadataBucket,
                '',
                $mappings
            );
            $metadataData[$indexId] = $metadataBucket;
        }

        return $metadataData;
    }

    /**
     * Get properties.
     *
     * @param array  $metadataBucket
     * @param string $field
     * @param array  $data
     */
    private function getMappingProperties(
        array &$metadataBucket,
        string $field,
        array $data
    ): void {
        if (
            isset($data['type']) &&
            'nested' !== $data['type']
        ) {
            $metadataBucket[$field] = $data['type'];

            return;
        }

        foreach ($data['properties'] ?? [] as $property => $value) {
            $this->getMappingProperties(
                $metadataBucket,
                \trim("$field.$property", '.'),
                $value
            );
        }
    }

    /**
     * Create index.
     *
     * @param RepositoryReference $repositoryReference
     * @param Config              $config
     *
     * @return PromiseInterface
     *
     * @throws ResourceExistsException
     */
    public function createIndex(
        RepositoryReference $repositoryReference,
        Config $config
    ): PromiseInterface {
        return $this
            ->getOriginalIndexName($repositoryReference)
            ->then(function ($originalIndexName) use ($repositoryReference) {
                if (!\is_null($originalIndexName)) {
                    throw ResourceExistsException::indexExists();
                }
            })
            ->then(function () use ($repositoryReference, $config) {
                $indexAliasName = $this->getIndexAliasName($repositoryReference);
                $indexName = $this->getRandomIndexName($repositoryReference);

                return $this
                    ->createIndexByNameAndConfig(
                        $indexName,
                        $config
                    )
                    ->then(function () use ($indexName, $indexAliasName) {
                        return $this->addAlias(
                            $indexName,
                            $indexAliasName
                        );
                    }, function (ResponseException $exception) {
                        throw ParsedCreatingIndexException::parse($exception->getMessage());
                    });
            });
    }

    /**
     * Create index by name and config.
     *
     * @param string $indexName
     * @param Config $config
     *
     * @return PromiseInterface
     */
    private function createIndexByNameAndConfig(
        string $indexName,
        Config $config
    ): PromiseInterface {
        $endpoint = new CreateIndex();
        $endpoint->setBody($this->getImmutableIndexConfiguration($config));

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint, $indexName)
            ->then(function () use ($indexName, $config) {
                return $this->createIndexMappingByIndexName(
                    $indexName,
                    $config
                );
            });
    }

    /**
     * Delete index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     *
     * @throws TransportableException
     */
    public function deleteIndex(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->getOriginalIndexName($repositoryReference)
            ->then(function ($originalIndexName) use ($repositoryReference) {
                if (\is_null($originalIndexName)) {
                    throw ResourceNotAvailableException::indexNotAvailable(
                        $repositoryReference->compose()
                    );
                }

                return $originalIndexName;
            })
            ->then(function (string $originalIndexName) use ($repositoryReference) {
                $indexAliasName = $this->getIndexAliasName($repositoryReference);

                return $this
                    ->removeAlias($originalIndexName, $indexAliasName)
                    ->then(function () use ($originalIndexName) {
                        return $this->deleteIndexByName($originalIndexName);
                    }, function (ResponseException $exception) {
                        throw $this->getIndexNotAvailableException($exception->getMessage());
                    });
            });
    }

    /**
     * Delete index by name.
     *
     * @param string $indexName
     *
     * @return PromiseInterface
     *
     * @throws TransportableException
     */
    public function deleteIndexByName(string $indexName): PromiseInterface
    {
        $endpoint = new DeleteIndex();

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint, $indexName)
            ->otherwise(function (ResponseException $exception) {
                throw $this->getIndexNotAvailableException($exception->getMessage());
            });
    }

    /**
     * Remove alias.
     *
     * @param string $index
     * @param string $alias
     *
     * @return PromiseInterface
     */
    private function removeAlias(
        string $index,
        string $alias
    ): PromiseInterface {
        $endpoint = new DeleteAlias();
        $endpoint->setName($alias);

        return $this->requestAsyncEndpoint($endpoint, $index);
    }

    /**
     * Adds an alias to an index.
     *
     * @param string $index
     * @param string $name
     *
     * @return PromiseInterface
     */
    public function addAlias(
        string $index,
        string $name
    ) {
        $data = ['actions' => [
            ['add' => [
                'index' => $index,
                'alias' => $name,
            ],
        ], ]];
        $endpoint = new UpdateAliases();
        $endpoint->setBody($data);

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint);
    }

    /**
     * Remove index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function resetIndex(RepositoryReference $repositoryReference): PromiseInterface
    {
        $indexAliasName = $this->getIndexAliasName($repositoryReference);

        $query = new Query\MatchAll();
        $query = Query::create($query)->getQuery();
        $endpoint = new DeleteByQuery();
        $endpoint->setBody(['query' => \is_array($query) ? $query : $query->toArray()]);
        $endpoint->setParams([
            'refresh' => true,
        ]);

        return $this
            ->requestAsyncEndpoint($endpoint, $indexAliasName)
            ->otherwise(function (ResponseException $exception) {
                throw $this->getIndexNotAvailableException($exception->getMessage());
            });
    }

    /**
     * Configure index.
     *
     * @param RepositoryReference $repositoryReference
     * @param Config              $config
     *
     * @return PromiseInterface
     *
     * @throws ResourceExistsException
     */
    public function configureIndex(
        RepositoryReference $repositoryReference,
        Config $config
    ): PromiseInterface {
        return $this
            ->getOriginalIndexName($repositoryReference)
            ->then(function ($indexOldName) use ($repositoryReference, $config) {
                $indexNewName = $this->getRandomIndexName($repositoryReference);
                $indexAliasName = $this->getIndexAliasName($repositoryReference);

                return $this
                    ->createIndexByNameAndConfig(
                        $indexNewName,
                        $config
                    )
                    ->then(function () use ($indexOldName, $indexNewName) {
                        $reindex = new Reindex();
                        $reindex->setParams([
                            'wait_for_completion' => true,
                            'refresh' => true,
                        ]);
                        $reindex->setBody([
                            'source' => [
                                'index' => $indexOldName,
                            ],
                            'dest' => [
                                'index' => $indexNewName,
                            ],
                        ]);

                        return $this
                            ->client
                            ->requestAsyncEndpoint($reindex);
                    })
                    ->then(function () use ($indexAliasName, $indexOldName, $indexNewName) {
                        return $this->removeAlias($indexOldName, $indexAliasName);
                    })
                    ->then(function () use ($indexAliasName, $indexOldName, $indexNewName) {
                        return $this->addAlias($indexNewName, $indexAliasName);
                    })
                    ->then(function () use ($indexOldName) {
                        return $this->deleteIndexByName($indexOldName);
                    });
            });
    }

    /**
     * Simple search.
     *
     * @param RepositoryReference $repositoryReference
     * @param Search              $search
     *
     * @return PromiseInterface
     */
    public function simpleSearch(
        RepositoryReference $repositoryReference,
        Search $search
    ): PromiseInterface {
        $indexName = $this->getIndexAliasName($repositoryReference);

        $elasticsearchSearch = new AsyncSearch($this->client);

        return $elasticsearchSearch
            ->searchAsync($search->getQuery(), [
                'from' => $search->getFrom(),
                'size' => $search->getSize(),
            ], $indexName)
            ->otherwise(function ($exception) {
                throw ($exception instanceof ResponseException)
                    ? $this->getIndexNotAvailableException($exception->getMessage())
                    : $exception;
            });
    }

    /**
     * Multi search.
     *
     * @param RepositoryReference[] $repositoryReferences
     * @param Search[]              $searches
     *
     * @return PromiseInterface
     */
    public function multisearch(
        array $repositoryReferences,
        array $searches
    ): PromiseInterface {
        $elasticsearchMultiSearch = new AsyncMultiSearch($this->client);
        $queries = [];
        foreach ($searches as $position => $search) {
            $indexName = $this->getIndexAliasName($repositoryReferences[$position]);
            $queries[] = [$indexName, $search];
        }

        return $elasticsearchMultiSearch->multisearchAsync($queries, []);
    }

    /**
     * Export index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return ReadableStreamInterface
     */
    public function exportIndex(RepositoryReference $repositoryReference): ReadableStreamInterface
    {
        $scroll = new AsyncScroll($this->client, $this->loop);

        return $scroll->scroll(
            $this->getIndexAliasName($repositoryReference),
            100
        );
    }

    /**
     * Refresh.
     *
     * @param string $indexName
     *
     * @return PromiseInterface
     */
    public function refresh(string $indexName = null): PromiseInterface
    {
        $endpoint = new Refresh();

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint, $indexName);
    }

    /**
     * Create mapping.
     *
     * @param RepositoryReference $repositoryReference
     * @param Config              $config
     *
     * @return PromiseInterface
     *
     * @throws ResourceExistsException
     */
    public function createIndexMapping(
        RepositoryReference $repositoryReference,
        Config $config
    ): PromiseInterface {
        return $this->createIndexMappingByIndexName(
            $this->getIndexAliasName($repositoryReference),
            $config
        );
    }

    /**
     * Create index mapping by index name.
     *
     * @param string $indexName
     * @param Config $config
     *
     * @return PromiseInterface
     *
     * @throws ResourceExistsException
     */
    public function createIndexMappingByIndexName(
        string $indexName,
        Config $config
    ): PromiseInterface {
        $endpoint = new PutMapping();
        $endpoint->setBody($this->getIndexMapping($config));

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint, $indexName)
            ->otherwise(function (ResponseException $exception) {
                throw $this->getIndexNotAvailableException($exception->getMessage());
            });
    }

    /**
     * Add documents.
     *
     * @param RepositoryReference $repositoryReference
     * @param Document[]          $documents
     * @param bool                $refresh
     *
     * @return PromiseInterface
     *
     * @throws ResourceNotAvailableException
     */
    public function addDocuments(
        RepositoryReference $repositoryReference,
        array $documents,
        bool $refresh
    ): PromiseInterface {
        $indexName = $this->getIndexAliasName($repositoryReference);
        $endpoint = new Bulk(new ArrayToJSONSerializer());
        $data = [];
        foreach ($documents as $document) {
            $data[] = ['update' => ['_id' => $document->getId(), '_index' => $indexName]];
            $data[] = ['doc' => $document->getData(), 'doc_as_upsert' => true];
        }
        $endpoint->setBody($data);
        $endpoint->setParams([
            'refresh' => $refresh,
        ]);

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint, $indexName)
            ->otherwise(function (\Exception $exception) {
                throw (
                    $exception instanceof ResponseException ||
                    $exception instanceof BulkResponseException
                )
                    ? $this->getIndexNotAvailableException($exception->getMessage())
                    : $exception;
            });
    }

    /**
     * Delete documents by its.
     *
     * @param RepositoryReference $repositoryReference
     * @param string[]            $documentsId
     * @param bool                $refresh
     *
     * @return PromiseInterface
     *
     * @throws TransportableException
     */
    public function deleteDocumentsByIds(
        RepositoryReference $repositoryReference,
        array $documentsId,
        bool $refresh
    ): PromiseInterface {
        $indexName = $this->getIndexAliasName($repositoryReference);
        $query = Query::create(new Query\Ids(\array_values($documentsId)));

        $endpoint = new DeleteByQuery();
        $endpoint->setBody($query->toArray());
        $endpoint->setParams([
            'refresh' => $refresh,
        ]);

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint, $indexName)
            ->otherwise(function (ResponseException $exception) {
                throw $this->getIndexNotAvailableException($exception->getMessage());
            });
    }

    /**
     * Delete documents by query.
     *
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     * @param bool                $refresh
     *
     * @return PromiseInterface
     */
    public function deleteDocumentsByQuery(
        RepositoryReference $repositoryReference,
        Query $query,
        bool $refresh
    ): PromiseInterface {
        $indexName = $this->getIndexAliasName($repositoryReference);

        $endpoint = new DeleteByQuery();
        $endpoint->setBody($query->toArray());
        $endpoint->setParams([
            'refresh' => $refresh,
        ]);

        return $this
            ->refresh($indexName)
            ->then(function () use ($endpoint, $indexName) {
                return $this
                    ->client
                    ->requestAsyncEndpoint($endpoint, $indexName)
                    ->otherwise(function (ResponseException $exception) {
                        throw $this->getIndexNotAvailableException($exception->getMessage());
                    });
            });
    }

    /**
     * Get cluster status.
     *
     * @return PromiseInterface
     */
    public function getClusterStatus(): PromiseInterface
    {
        $endpoint = new Health();
        $endpoint->setParams(['level' => 'shards']);

        return $this
            ->requestAsyncEndpoint($endpoint)
            ->then(function (Response $response) {
                return $response->getData();
            });
    }

    /**
     * Build specific index reference.
     *
     * @param RepositoryReference $repositoryReference
     * @param string              $prefix
     *
     * @return string
     */
    protected function buildIndexReference(
        RepositoryReference $repositoryReference,
        string $prefix
    ) {
        if (\is_null($repositoryReference->getAppUUID())) {
            return '';
        }

        $appId = $repositoryReference->getAppUUID()->composeUUID();
        if (\is_null($repositoryReference->getIndexUUID())) {
            return "{$prefix}_{$appId}";
        }

        $indexId = $repositoryReference->getIndexUUID()->composeUUID();
        if ('*' === $indexId) {
            return "{$prefix}_{$appId}_*";
        }

        $indexIdsAsArray = \explode(',', $indexId);

        return \implode(',', \array_map(function (string $indexId) use ($prefix, $appId) {
            return \trim("{$prefix}_{$appId}_$indexId", '_ ');
        }, $indexIdsAsArray));
    }

    /**
     * Get original generated index name.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface<string|null>
     */
    private function getOriginalIndexName(RepositoryReference $repositoryReference): PromiseInterface
    {
        $appId = $repositoryReference->getAppUUID()->composeUUID();
        $indexId = $repositoryReference->getIndexUUID()->composeUUID();
        $indexName = $this->getIndexAliasName($repositoryReference);
        $aliases = new Aliases();
        $aliases->setName($this->getIndexAliasName($repositoryReference));

        return $this
            ->client
            ->requestAsyncEndpoint(
                $aliases,
                $indexName
            )
            ->then(function (Response $elasticaResponse) use ($appId, $indexId) {
                $regexToParse = "~apisearch_item_{$appId}_{$indexId}\\s*(?P<index_name>apisearch_\\d*_item_{$appId}_{$indexId})~";
                if (empty($elasticaResponse->getData())) {
                    return null;
                }

                \preg_match($regexToParse, $elasticaResponse->getData()['message'], $match);

                return $match['index_name'] ?? null;
            });
    }

    /**
     * Makes calls to the elasticsearch server based on this index.
     *
     * It's possible to make any REST query directly over this method
     *
     * @param string       $path        Path to call
     * @param string       $method      Rest method to use (GET, POST, DELETE, PUT)
     * @param array|string $data        OPTIONAL Arguments as array or pre-encoded string
     * @param array        $query       OPTIONAL Query params
     * @param string       $contentType Content-Type sent with this request
     *
     * @return PromiseInterface
     */
    public function requestAsync(
        string $path,
        string $method = Request::GET,
        $data = [],
        array $query = [],
        $contentType = Request::DEFAULT_CONTENT_TYPE
    ): PromiseInterface {
        return $this
            ->client
            ->requestAsync(
                $path,
                $method,
                $data,
                $query,
                $contentType
            );
    }

    /**
     * Makes calls to the elasticsearch server with usage official client Endpoint based on this index.
     *
     * @param AbstractEndpoint $endpoint
     * @param string|null      $index
     *
     * @return PromiseInterface
     */
    public function requestAsyncEndpoint(
        AbstractEndpoint $endpoint,
        string $index = null
    ): PromiseInterface {
        return $this
            ->client
            ->requestAsyncEndpoint(
                $endpoint,
                $index
            );
    }
}
