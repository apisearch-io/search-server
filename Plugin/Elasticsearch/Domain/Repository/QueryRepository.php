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

namespace Apisearch\Plugin\Elasticsearch\Domain\Repository;

use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Plugin\Elasticsearch\Domain\Builder\QueryBuilder;
use Apisearch\Plugin\Elasticsearch\Domain\Builder\ResultBuilder;
use Apisearch\Plugin\Elasticsearch\Domain\ElasticaWrapper;
use Apisearch\Plugin\Elasticsearch\Domain\Parser\IndexParser;
use Apisearch\Plugin\Elasticsearch\Domain\Polyfill;
use Apisearch\Plugin\Elasticsearch\Domain\Search;
use Apisearch\Plugin\Elasticsearch\Domain\WithElasticaWrapper;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Helper\Str;
use Apisearch\Server\Domain\Model\InternalVersionUUID;
use Apisearch\Server\Domain\Model\ServerQuery;
use Apisearch\Server\Domain\Repository\Repository\QueryRepository as QueryRepositoryInterface;
use function Drift\React\wait_for_stream_listeners;
use Elastica\Multi\ResultSet as ElasticaMultiResultSet;
use Elastica\ResultSet as ElasticaResultSet;
use React\EventLoop\LoopInterface;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use React\Stream\DuplexStreamInterface;
use React\Stream\ThroughStream;
use React\Stream\TransformerStream;
use React\Stream\Util;

/**
 * Class QueryRepository.
 */
class QueryRepository extends WithElasticaWrapper implements QueryRepositoryInterface
{
    use Transformers;

    private QueryBuilder $queryBuilder;
    private ResultBuilder $resultBuilder;
    private LoopInterface $loop;

    /**
     * ElasticaSearchRepository constructor.
     *
     * @param ElasticaWrapper $elasticaWrapper
     * @param bool            $refreshOnWrite
     * @param QueryBuilder    $queryBuilder
     * @param ResultBuilder   $resultBuilder
     * @param LoopInterface   $loop
     */
    public function __construct(
        ElasticaWrapper $elasticaWrapper,
        bool $refreshOnWrite,
        QueryBuilder $queryBuilder,
        ResultBuilder $resultBuilder,
        LoopInterface $loop
    ) {
        parent::__construct(
            $elasticaWrapper,
            $refreshOnWrite
        );

        $this->queryBuilder = $queryBuilder;
        $this->resultBuilder = $resultBuilder;
        $this->loop = $loop;
    }

    /**
     * Search cross the index types.
     *
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface
     */
    public function query(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        return (\count($query->getSubqueries()) > 0)
            ? $this->makeMultipleQueryOrMatchExclusiveText($repositoryReference, $query)
            : $this->makeSimpleQueryOrMatchExclusiveText($repositoryReference, $query);
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
        $query = ServerQuery::createFromArray($query->toArray());
        $query->likeItemUUIDs($itemsUUID);

        return $this->makeSimpleQuery($repositoryReference, $query);
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
                $query = Query::createMatchAll();
                $sourceStream = $this
                    ->elasticaWrapper
                    ->exportIndex($repositoryReference);

                $elasticaTransformer = TransformerStream::withCallback(
                    $stream,
                    function (ElasticaResultSet $resultSet) use ($query, $stream) {
                        $result = $this->elasticaResultSetToResult(
                            $query,
                            $resultSet
                        );

                        foreach ($result->getItems() as $item) {
                            $item->deleteIndexedMetadata(InternalVersionUUID::INDEXED_METADATA_FIELD);
                            $stream->write($item);
                        }
                    });

                $sourceStream->pipe($elasticaTransformer);
                Util::forwardEvents($elasticaTransformer, $sourceStream, ['close']);
            });

        return resolve($stream);
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface<Result>
     */
    private function makeSimpleQueryOrMatchExclusiveText(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        return $this
            ->queryCheckExactMatchingMetadataRelatedModifiers($repositoryReference, $query)
            ->then(function (Query $query) use ($repositoryReference) {
                return $this->makeSimpleQuery($repositoryReference, $query);
            });
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface<Result>
     */
    private function makeSimpleQuery(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        return $this
            ->elasticaWrapper
            ->simpleSearch(
                $this->getRepositoryReferenceIndexSpecific(
                    $repositoryReference,
                    $query->getIndexUUID()
                ),
                new Search(
                    $this->createElasticaQueryByModelQuery($query),
                    $query->areResultsEnabled()
                        ? $query->getFrom()
                        : 0,
                    $query->areResultsEnabled()
                        ? $query->getSize()
                        : 0
                )
            )
            ->then(function (ElasticaResultSet $resultSet) use ($query) {
                return $this->elasticaResultSetToResult(
                    $query,
                    $resultSet
                );
            });
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface<Result>
     */
    private function makeMultipleQueryOrMatchExclusiveText(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        return
            all(\array_map(function (Query $query) use ($repositoryReference) {
                return $this->queryCheckExactMatchingMetadataRelatedModifiers($repositoryReference, $query);
            }, $query->getSubqueries()))
            ->then(function (array $queries) use ($repositoryReference) {
                return $this->makeMultiQuery(
                    $repositoryReference,
                    Query::createMultiquery($queries)
                );
            });
    }

    /**
     * Make multi query.
     *
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface
     */
    private function makeMultiQuery(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        $searches = [];
        $repositoryReferences = [];
        foreach ($query->getSubqueries() as $name => $subquery) {
            $repositoryReferences[] = $this->getRepositoryReferenceIndexSpecific(
                $repositoryReference,
                $subquery->getIndexUUID()
            );
            $searches[] = new Search(
                $this->createElasticaQueryByModelQuery($subquery),
                $subquery->areResultsEnabled()
                    ? $subquery->getFrom()
                    : 0,
                $subquery->areResultsEnabled()
                    ? $subquery->getSize()
                    : 0,
                \strval($name)
            );
        }

        return $this
            ->elasticaWrapper
            ->multisearch(
                $repositoryReferences,
                $searches
            )
            ->then(function (ElasticaMultiResultSet $multiResultSet) use ($query) {
                return $this->elasticaMultiResultSetToResult(
                    $query,
                    $multiResultSet
                );
            });
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface<Query>
     */
    private function queryCheckExactMatchingMetadataRelatedModifiers(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        $queryText = $query->getQueryText();
        $queryText = Str::toAscii($queryText);
        $queryText = \trim($queryText);

        if (
            ('' === $queryText) ||
            (\strlen($queryText) < ($query->getMetadata()['min_characters_progressive_exact_matching_metadata'] ?? 0)) ||
            (false === ($query->getMetadata()['progressive_exact_matching_metadata'] ?? false))
        ) {
            return resolve($query);
        }

        // We compose all possible word combinations
        $words = \explode(' ', $queryText);
        $words = \array_values(\array_filter($words, fn ($word) => !empty($word)));
        $maxWords = $query->getMetadata()['max_characters_progressive_exact_matching_metadata'] ?? 3;
        $numWords = \count($words);
        $partialWords = [];

        for ($i = 0; $i < $numWords; ++$i) {
            for ($j = $i; $j < $numWords; ++$j) {
                $partialWord = '';
                for ($k = $i; $k <= \min($k + $maxWords, $j); ++$k) {
                    $partialWord .= $words[$k].' ';
                }

                $partialWords[] = \trim($partialWord);
            }
        }

        $specialQueryAsArray = [];
        $specialQueryAsArray['universal_filters'] = \array_map(function (Filter $filter) {
            return $filter->toArray();
        }, $query->getUniverseFilters());

        $specialQuery = Query::createFromArray($specialQueryAsArray);
        $specialQuery->filterBy(
            'exact_matching_metadata_max_frequency',
            'exact_matching_metadata_max_frequency',
            $partialWords,
            Filter::AT_LEAST_ONE,
            false
        );

        return $this
            ->elasticaWrapper
            ->simpleSearch(
                $this->getRepositoryReferenceIndexSpecific(
                    $repositoryReference,
                    $query->getIndexUUID()
                ),
                new Search(
                    $this->createElasticaQueryByModelQuery($specialQuery),
                    0, 1
                )
            )
            ->then(function (ElasticaResultSet $resultSet) use ($query) {
                $result = $this->elasticaResultSetToResult(
                    $query,
                    $resultSet
                );

                return $result->getFirstItem();
            })
            ->then(function (?Item $firstItem) use ($query, $partialWords, $queryText) {
                if (
                    !\is_null($firstItem) &&
                    $firstItem->getScore() > 0
                ) {
                    $foundWords = \array_intersect(
                        \array_map(fn ($str) => \strtolower($str), $firstItem->getExactMatchingMetadata()),
                        \array_map(fn ($str) => \strtolower($str), $partialWords)
                    );

                    $newQueryText = \str_replace(
                        \array_values($foundWords),
                        \array_fill(0, \count($foundWords), ''),
                        \strtolower($queryText)
                    );

                    $newQueryAsArray = $query->toArray();
                    $newQueryAsArray['q'] = \trim($newQueryText);
                    $query = Query::createFromArray($newQueryAsArray);
                    $query->filterBy(
                        'indexed_exact_matching_metadata',
                        'indexed_exact_matching_metadata',
                        $foundWords,
                        Filter::MUST_ALL,
                        false
                    );
                }

                return $query;
            });
    }

    /**
     * Build a Result object given elastica resultset.
     *
     * @param Query             $query
     * @param ElasticaResultSet $resultSet
     *
     * @return Result
     */
    private function elasticaResultSetToResult(
        Query $query,
        ElasticaResultSet $resultSet
    ): Result {
        $resultAggregations = [];
        $elasticaResultAggregations = $resultSet->getAggregations();
        $resultsCount = 0;

        /*
         * Build Result instance
         */
        if (
            $query->areAggregationsEnabled() &&
            isset($elasticaResultAggregations['all'])
        ) {
            $resultAggregations = $elasticaResultAggregations['all']['universe'];
            unset($resultAggregations['common']);
            $resultsCount = $resultAggregations['doc_count'];
        }

        $result = new Result(
            $query->getUUID(),
            $resultsCount,
            Polyfill\ResultSet::getTotalHits($resultSet)
        );

        /*
         * @var ElasticaResult
         */
        foreach ($resultSet->getResults() as $elasticaResult) {
            $source = $elasticaResult->getSource();

            if (
                isset($elasticaResult->getParam('sort')[0]) &&
                \is_float($elasticaResult->getParam('sort')[0])
            ) {
                $source['distance'] = $elasticaResult->getParam('sort')[0];
            }

            unset($source['indexed_metadata'][InternalVersionUUID::INDEXED_METADATA_FIELD]);

            $item = Item::createFromArray($source);
            $score = $elasticaResult->getScore();
            $item->setScore(\is_float($score)
                ? $score
                : 1
            );

            if ($query->areHighlightEnabled()) {
                $formedHighlights = [];
                foreach ($elasticaResult->getHighlights() as $highlightField => $highlightValue) {
                    $formedHighlights[\str_replace('searchable_metadata.', '', $highlightField)] = $highlightValue[0];
                }

                $item->setHighlights($formedHighlights);
            }

            $indexInfo = IndexParser::parseIndexName($elasticaResult->getIndex());
            if (!\is_null($indexInfo)) {
                $composed = $indexInfo['app_uuid'].'_'.$indexInfo['index_uuid'];
                $item->setRepositoryReference(RepositoryReference::createFromComposed($composed));
            }

            $result->addItem($item);
        }

        if (
            $query->areAggregationsEnabled() &&
            isset($resultAggregations['doc_count'])
        ) {
            $result->setAggregations(
                $this
                    ->resultBuilder
                    ->buildResultAggregations(
                        $query,
                        $resultAggregations
                    )
            );
        }

        /*
         * Build suggests
         */
        $suggests = $resultSet->getSuggests();

        if (isset($suggests['completion']) && $query->areSuggestionsEnabled()) {
            $suggestions = $suggests['completion'][0]['options'];
            $queryText = \trim(\strtolower($query->getQueryText()));
            $suggestions = \array_map(function (array $suggestion) use ($queryText) {
                $suggestionText = \trim($suggestion['text']);
                $suggestionTextLower = \strtolower($suggestionText);

                return $queryText == $suggestionTextLower
                    ? false
                    : $suggestionText;
            }, $suggestions);
            $suggestions = \array_filter($suggestions);

            $suggestions = \array_slice($suggestions, 0, $query->getMetadata()['number_of_suggestions']);
            \array_walk($suggestions, function ($suggestion) use ($result) {
                $result->addSuggestion($suggestion);
            });
        }

        return $result;
    }

    /**
     * Build a Result object given elastica multi resultset.
     *
     * @param Query                  $query
     * @param ElasticaMultiResultSet $multiResultSet
     *
     * @return Result
     */
    private function elasticaMultiResultSetToResult(
        Query $query,
        ElasticaMultiResultSet $multiResultSet
    ): Result {
        $subqueries = $query->getSubqueries();
        $subresults = [];
        foreach ($multiResultSet->getResultSets() as $name => $resultSet) {
            $subresults[$name] = $this->elasticaResultSetToResult($subqueries[$name], $resultSet);
        }

        return Result::createMultiResult($subresults);
    }

    /**
     * Create a new RepositoryReference instance given a possible Index UUID. If
     * this given IndexUUID is null, then return the same value object.
     *
     * @param RepositoryReference $repositoryReference
     * @param IndexUUID|null      $indexUUID
     *
     * @return RepositoryReference
     */
    private function getRepositoryReferenceIndexSpecific(
        RepositoryReference $repositoryReference,
        ?IndexUUID $indexUUID
    ): RepositoryReference {
        if (!$indexUUID instanceof IndexUUID) {
            return $repositoryReference;
        }

        return $repositoryReference->changeIndex($indexUUID);
    }
}
