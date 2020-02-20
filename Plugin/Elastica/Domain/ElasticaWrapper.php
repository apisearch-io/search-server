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

namespace Apisearch\Plugin\Elastica\Domain;

use Apisearch\Config\Config;
use Apisearch\Config\Synonym;
use Apisearch\Exception\ResourceExistsException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Index as ApisearchIndex;
use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\Elastica\Adapter\AsyncClient;
use Apisearch\Plugin\Elastica\Adapter\AsyncMultiSearch;
use Apisearch\Plugin\Elastica\Adapter\AsyncSearch;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Exception\ParsedCreatingIndexException;
use Apisearch\Server\Exception\ParsedResourceNotAvailableException;
use Apisearch\Server\Exception\ResponseException;
use Elastica\Document;
use Elastica\Exception\Bulk\ResponseException as BulkResponseException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Request;
use Elastica\Response;
use Elastica\Search as ElasticaSearch;
use Elasticsearch\Endpoints\AbstractEndpoint;
use Elasticsearch\Endpoints\Bulk;
use Elasticsearch\Endpoints\Cat\Aliases;
use Elasticsearch\Endpoints\Cat\Indices;
use Elasticsearch\Endpoints\Cluster\Health;
use Elasticsearch\Endpoints\DeleteByQuery;
use Elasticsearch\Endpoints\Indices\Alias\Delete as DeleteAlias;
use Elasticsearch\Endpoints\Indices\Aliases\Update as UpdateAlias;
use Elasticsearch\Endpoints\Indices\Create as CreateIndex;
use Elasticsearch\Endpoints\Indices\Delete as DeleteIndex;
use Elasticsearch\Endpoints\Indices\Mapping as MappingEndpoint;
use Elasticsearch\Endpoints\Indices\Refresh;
use Elasticsearch\Endpoints\Reindex;
use Elasticsearch\Serializers\ArrayToJSONSerializer;
use React\Promise;
use React\Promise\PromiseInterface;
use React\Promise\RejectedPromise;

/**
 * Class ElasticaWrapper.
 */
class ElasticaWrapper implements AsyncRequestAccessor
{
    /**
     * @var AsyncClient
     *
     * Elastica client
     */
    private $client;

    /**
     * @var string
     *
     * Version
     */
    private $elasticaVersion;

    /**
     * Construct.
     *
     * @param AsyncClient $client
     * @param string      $elasticaVersion
     */
    public function __construct(
        AsyncClient $client,
        string $elasticaVersion
    ) {
        $this->client = $client;
        $this->elasticaVersion = $elasticaVersion;
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
        $randomID = rand(100000000000, 1000000000000);

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
     * @return ResourceNotAvailableException
     */
    public function getIndexNotAvailableException(string $message): ResourceNotAvailableException
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
            50 => 'ngram_filter',
        ];

        $searchAnalyzerFilter = [
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
                ],
                'filter' => [
                    'ngram_filter' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 1,
                        'max_gram' => 20,
                        'token_chars' => [
                            'letter',
                        ],
                    ],
                ],
                'normalizer' => [
                    'exact_matching_normalizer' => [
                        'type' => 'custom',
                        'filter' => [
                            'lowercase',
                            'asciifolding',
                        ],
                    ],
                ],
            ],
        ];

        $stopWordsLanguage = ElasticaLanguages::getStopwordsLanguageByIso($language);
        if (!is_null($stopWordsLanguage)) {
            $defaultAnalyzerFilter[30] = 'stop_words';
            $searchAnalyzerFilter[30] = 'stop_words';
            $indexConfiguration['analysis']['filter']['stop_words'] = [
                'type' => 'stop',
                'stopwords' => $stopWordsLanguage,
            ];
        }

        $stemmer = ElasticaLanguages::getStemmerLanguageByIso($language);
        if (!is_null($stemmer)) {
            $searchAnalyzerFilter[35] = 'stemmer';
            $indexConfiguration['analysis']['filter']['stemmer'] = [
                'type' => 'stemmer',
                'name' => $stemmer,
            ];
        }

        $synonyms = $config->getSynonyms();
        if (!empty($synonyms)) {
            $defaultAnalyzerFilter[40] = 'synonym';
            $indexConfiguration['analysis']['filter']['synonym'] = [
                'type' => 'synonym',
                'synonyms' => array_map(function (Synonym $synonym) {
                    return strtolower($synonym->expand());
                }, $synonyms),
            ];
        }

        ksort($defaultAnalyzerFilter, SORT_NUMERIC);
        ksort($searchAnalyzerFilter, SORT_NUMERIC);
        $indexConfiguration['analysis']['analyzer']['default']['filter'] = array_values($defaultAnalyzerFilter);
        $indexConfiguration['analysis']['analyzer']['search_analyzer']['filter'] = array_values($searchAnalyzerFilter);

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
                'type' => 'keyword',
                'normalizer' => 'exact_matching_normalizer',
            ],
            'suggest' => [
                'type' => 'completion',
                'analyzer' => 'search_analyzer',
                'search_analyzer' => 'search_analyzer',
            ],
        ];

        return $mapping;
    }

    /**
     * Get search index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return Index
     */
    public function getIndex(RepositoryReference $repositoryReference): Index
    {
        $indexAliasName = $this->getIndexAliasName($repositoryReference);

        return $this
            ->client
            ->getIndex($indexAliasName);
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
            ->requestAsyncEndpoint((new Indices())->setIndex($indexSearchKeyword));

        $mappingPromise = $this
            ->client
            ->requestAsyncEndpoint((new MappingEndpoint\Get())->setIndex($indexSearchKeyword));

        return

            Promise\resolve(Promise\all([
                $indicesPromise,
                $mappingPromise,
            ]))
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
                preg_match_all($regexToParse, $elasticaResponse->getData()['message'], $matches, PREG_SET_ORDER, 0);
                if ($matches) {
                    foreach ($matches as $metaData) {
                        $indices[] = new ApisearchIndex(
                            IndexUUID::createById($metaData['id']),
                            AppUUID::createById($metaData['app_id']),
                            (
                                'open' === $metaData['status'] &&
                                in_array($metaData['color'], ['green', 'yellow'])
                            ),
                            (int) $metaData['doc_count'],
                            (string) $metaData['index_size'],
                            (int) $metaData['primary_shards'],
                            (int) $metaData['replica_shards'],
                            $mappingData[$metaData['fullname']] ?? [],
                            [
                                'allocated' => ('green' === $metaData['color']),
                                'doc_deleted' => (int) $metaData['doc_deleted'],
                            ]
                        );
                    }
                }

                return $indices;
            })
            ->then(null, function (\Exception $e) {
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
                trim("$field.$property", '.'),
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
        if (!is_null($this->getOriginalIndexName($repositoryReference))) {
            return new RejectedPromise(ResourceExistsException::indexExists());
        }

        $indexAliasName = $this->getIndexAliasName($repositoryReference);
        $indexName = $this->getRandomIndexName($repositoryReference);
        $searchIndex = $this
            ->client
            ->getIndex($indexName);

        $endpoint = new CreateIndex();
        $endpoint->setBody($this->getImmutableIndexConfiguration($config));
        $endpoint->setIndex($searchIndex->getName());
        Polyfill\Type::setEndpointType($endpoint, $this->elasticaVersion);

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint)
            ->then(function () use ($searchIndex, $indexAliasName) {
                return $this->addAlias(
                    $searchIndex,
                    $indexAliasName
                );
            }, function (ResponseException $exception) {
                throw ParsedCreatingIndexException::parse($exception->getMessage());
            });
    }

    /**
     * Delete index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     *
     * @throws ResourceNotAvailableException
     *
     * @SYNC
     */
    public function deleteIndex(RepositoryReference $repositoryReference): PromiseInterface
    {
        $originalIndexName = $this->getOriginalIndexName($repositoryReference);
        if (is_null($originalIndexName)) {
            return new RejectedPromise(
                ResourceNotAvailableException::indexNotAvailable(
                    $repositoryReference->compose()
                )
            );
        }

        $indexAliasName = $this->getIndexAliasName($repositoryReference);
        $indexOriginalName = $this->getOriginalIndexName($repositoryReference);
        $searchIndex = $this
            ->client
            ->getIndex($indexOriginalName);

        return $this
            ->removeAlias($searchIndex, $indexAliasName)
            ->then(function () use ($searchIndex) {
                return $this->deleteIndexByName($searchIndex->getName());
            }, function (ResponseException $exception) {
                throw $this->getIndexNotAvailableException($exception->getMessage());
            });
    }

    /**
     * Delete index by name.
     *
     * @param string $indexName
     *
     * @return PromiseInterface
     *
     * @throws ResourceNotAvailableException
     */
    public function deleteIndexByName(string $indexName): PromiseInterface
    {
        $endpoint = new DeleteIndex();
        $endpoint->setIndex($indexName);
        Polyfill\Type::setEndpointType($endpoint, $this->elasticaVersion);

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint)
            ->then(null, function (ResponseException $exception) {
                throw $this->getIndexNotAvailableException($exception->getMessage());
            });
    }

    /**
     * Remove alias.
     *
     * @param Index  $index
     * @param string $alias
     *
     * @return PromiseInterface
     */
    private function removeAlias(
        Index $index,
        string $alias
    ): PromiseInterface {
        $endpoint = new DeleteAlias();
        $endpoint->setName($alias);

        return $this->requestAsyncEndpoint($endpoint, $index);
    }

    /**
     * Adds an alias to an index.
     *
     * @param Index  $index
     * @param string $name
     *
     * @return PromiseInterface
     */
    public function addAlias(
        Index $index,
        string $name
    ) {
        $data = ['actions' => [
            ['add' => [
                'index' => $index->getName(),
                'alias' => $name,
            ],
        ], ]];
        $endpoint = new UpdateAlias();
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
        $searchIndex = $this
            ->client
            ->getIndex($indexAliasName);

        $query = new Query\MatchAll();
        $query = Query::create($query)->getQuery();
        $endpoint = new DeleteByQuery();
        $endpoint->setBody(['query' => is_array($query) ? $query : $query->toArray()]);
        $endpoint->setParams([
            'refresh' => true,
        ]);

        return $this
            ->requestAsyncEndpoint($endpoint, $searchIndex)
            ->then(null, function (ResponseException $exception) {
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
        $indexAliasName = $this->getIndexAliasName($repositoryReference);
        $indexOriginalOldName = $this->getOriginalIndexName($repositoryReference);
        $indexOriginalNewName = $this->getRandomIndexName($repositoryReference);

        $oldIndex = $this
            ->client
            ->getIndex($indexOriginalOldName);

        $newIndex = $this
            ->client
            ->getIndex($indexOriginalNewName);

        $newIndex->create($this->getImmutableIndexConfiguration($config));

        return
            $this->createIndexMappingByIndexName(
                $indexOriginalNewName,
                $config
            )
            ->then(function () use ($indexOriginalOldName, $indexOriginalNewName) {
                $reindex = new Reindex();
                $reindex->setParams([
                    'wait_for_completion' => true,
                    'refresh' => true,
                ]);
                $reindex->setBody([
                    'source' => [
                        'index' => $indexOriginalOldName,
                    ],
                    'dest' => [
                        'index' => $indexOriginalNewName,
                    ],
                ]);

                return $this
                    ->client
                    ->requestAsyncEndpoint($reindex);
            })
            ->then(function () use ($indexAliasName, $oldIndex) {
                return $this->removeAlias($oldIndex, $indexAliasName);
            })
            ->then(function () use ($indexAliasName, $newIndex) {
                return $this->addAlias($newIndex, $indexAliasName);
            })
            ->then(function () use ($indexOriginalOldName) {
                return $this->deleteIndexByName($indexOriginalOldName);
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
        $index = $this->getIndex($repositoryReference);
        $client = $index->getClient();

        $elasticsearchSearch = new AsyncSearch($client);
        $elasticsearchSearch->addIndex($index);

        return $elasticsearchSearch
            ->searchAsync($search->getQuery(), [
                'from' => $search->getFrom(),
                'size' => $search->getSize(),
            ])
            ->then(null, function ($exception) {
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
        foreach ($searches as $position => $search) {
            $index = $this->getIndex($repositoryReferences[$position]);
            $elasticsearchSearch = new ElasticaSearch($this->client);
            $elasticsearchSearch->addIndices(explode(',', $index->getName()));
            $elasticsearchSearch->setOptionsAndQuery([
                'from' => $search->getFrom(),
                'size' => $search->getSize(),
            ], $search->getQuery());
            $elasticsearchMultiSearch->addSearch($elasticsearchSearch, $search->getName());
        }

        return $elasticsearchMultiSearch->searchAsync();
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
        if (!is_null($indexName)) {
            $endpoint->setIndex($indexName);
        }

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint);
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
        $endpoint = new MappingEndpoint\Put();
        $endpoint->setBody($this->getIndexMapping($config));
        Polyfill\Type::setEndpointType($endpoint, $this->elasticaVersion);
        $index = $this->getIndexByName($indexName);

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint, $index)
            ->then(null, function (ResponseException $exception) {
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
     * @throws ResourceExistsException
     */
    public function addDocuments(
        RepositoryReference $repositoryReference,
        array $documents,
        bool $refresh
    ): PromiseInterface {
        $index = $this->getIndex($repositoryReference);
        $endpoint = new Bulk(new ArrayToJSONSerializer());
        $data = [];
        foreach ($documents as $document) {
            $data[] = ['update' => ['_id' => $document->getId(), '_index' => $index->getName()]];
            $data[] = ['doc' => $document->getData(), 'doc_as_upsert' => true];
        }
        $endpoint->setBody($data);
        $endpoint->setParams([
            'refresh' => $refresh,
        ]);
        Polyfill\Type::setEndpointType($endpoint, $this->elasticaVersion);

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint, $index)
            ->then(null, function (\Exception $exception) {
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
     * @throws ResourceExistsException
     */
    public function deleteDocumentsByIds(
        RepositoryReference $repositoryReference,
        array $documentsId,
        bool $refresh
    ): PromiseInterface {
        $index = $this->getIndex($repositoryReference);
        $query = Query::create(new Query\Ids(array_values($documentsId)));

        $endpoint = new DeleteByQuery();
        $endpoint->setBody($query->toArray());
        $endpoint->setParams([
            'refresh' => $refresh,
        ]);

        return $this
            ->client
            ->requestAsyncEndpoint($endpoint, $index)
            ->then(null, function (ResponseException $exception) {
                throw $this->getIndexNotAvailableException($exception->getMessage());
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
                return $response->getData()['status'];
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
        if (is_null($repositoryReference->getAppUUID())) {
            return '';
        }

        $appId = $repositoryReference->getAppUUID()->composeUUID();
        if (is_null($repositoryReference->getIndexUUID())) {
            return "{$prefix}_{$appId}";
        }

        $indexId = $repositoryReference->getIndexUUID()->composeUUID();
        if ('*' === $indexId) {
            return "{$prefix}_{$appId}_*";
        }

        $splittedIndexId = explode(',', $indexId);

        return implode(',', array_map(function (string $indexId) use ($prefix, $appId) {
            return trim("{$prefix}_{$appId}_$indexId", '_ ');
        }, $splittedIndexId));
    }

    /**
     * Get original generated index name.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return string|null
     */
    private function getOriginalIndexName(RepositoryReference $repositoryReference): ? string
    {
        $appId = $repositoryReference->getAppUUID()->composeUUID();
        $indexId = $repositoryReference->getIndexUUID()->composeUUID();
        $aliases = new Aliases();
        $aliases->setName($this->getIndexAliasName($repositoryReference));
        $elasticaResponse = $this->client->requestEndpoint($aliases);
        $regexToParse = "~apisearch_item_{$appId}_{$indexId}\\s*(?P<index_name>apisearch_\\d*_item_{$appId}_{$indexId})~";
        if (empty($elasticaResponse->getData())) {
            return null;
        }

        preg_match($regexToParse, $elasticaResponse->getData()['message'], $match);

        return $match['index_name'] ?? null;
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
     * @param Index            $index
     *
     * @return PromiseInterface
     */
    public function requestAsyncEndpoint(
        AbstractEndpoint $endpoint,
        ?Index $index = null
    ): PromiseInterface {
        return $this
            ->client
            ->requestAsyncEndpoint(
                $endpoint,
                $index
            );
    }

    /**
     * Get index by name.
     *
     * @param string $indexName
     *
     * @return Index
     */
    private function getIndexByName(string $indexName): Index
    {
        return $this
            ->client
            ->getIndex($indexName);
    }
}
