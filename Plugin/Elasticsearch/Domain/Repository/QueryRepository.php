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
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Helper\Str;
use Apisearch\Server\Domain\Model\InternalVersionUUID;
use Apisearch\Server\Domain\Model\ServerQuery;
use Apisearch\Server\Domain\Repository\Repository\QueryRepository as QueryRepositoryInterface;
use function Drift\React\wait_for_stream_listeners;
use Elastica\Multi\ResultSet as ElasticaMultiResultSet;
use Elastica\Query\BoolQuery;
use Elastica\Query\ConstantScore;
use Elastica\Query\MatchQuery;
use Elastica\Result as ElasticaResult;
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
            ->queryCheckExactMatchingFilters($repositoryReference, $query)
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
                return $this->queryCheckExactMatchingFilters($repositoryReference, $query);
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

    private function queryCheckExactMatchingFilters(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        $queryMetadata = $query->getMetadata();
        if (false === \boolval($queryMetadata['progressive_exact_matching_metadata'] ?? false)) {
            return resolve($query);
        }

        $queryText = $query->getQueryText();
        if (empty($queryText)) {
            return resolve($query);
        }

        $minCharacters = \intval($queryMetadata['min_characters_progressive_exact_matching_metadata'] ?? 3);
        if (\strlen($queryText) < $minCharacters) {
            return resolve($query);
        }

        $queryText = Str::toAscii($queryText);
        $queryText = \trim($queryText);

        $words = \explode(' ', $queryText);
        $words = \array_values(\array_filter($words, fn ($word) => !empty($word)));
        $maxWords = $queryMetadata['max_words_progressive_exact_matching_metadata'] ?? 3;
        $partialWords = $this->createPartialWords($words, $maxWords);

        $allowFuzzy = $queryMetadata['fuzzy_progressive_exact_matching_metadata'] ?? false;
        $boolQuery = $this->createBoolQueryByWords($partialWords, $allowFuzzy);
        $elasticaQuery = new \Elastica\Query($boolQuery);
        $elasticaQuery->setSource([false]);
        $search = new Search(
            $elasticaQuery,
            0, 10
        );

        return $this
            ->elasticaWrapper
            ->simpleSearch($repositoryReference, $search)
            ->then(function (ElasticaResultSet $resultSet) use ($query, $boolQuery, $queryText, $partialWords, $allowFuzzy) {
                if ($resultSet->getTotalHits() > 0) {
                    $firstResult = $this->getBestFoundWordFromMatchedWords($resultSet);
                    $score = $firstResult->getScore();
                    $matchedQueries = $firstResult->getParam('matched_queries');
                    $newMatchingWords = \array_intersect($partialWords, $matchedQueries);
                    $newQueryText = $this->cleanQueryTextFromMatchedPartialWords($queryText, $newMatchingWords);

                    $newQueryAsArray = $query->toArray();
                    $newQueryAsArray['q'] = $newQueryText;
                    $query = Query::createFromArray($newQueryAsArray);

                    $query->filterBy('exact_matching_tokenized', 'exact_matching_tokenized', ['']);
                    $query->setMetadataValue('exact_matching_tokenized_min_score', \intval($score));
                    $query->setMetadataValue('exact_matching_tokenized_words', $newMatchingWords);
                    $query->setMetadataValue('exact_matching_tokenized_allow_fuzzy', $allowFuzzy);
                }

                return $query;
            });
    }

    private function createBoolQueryByWords(array $words, bool $allowFuzzy): BoolQuery
    {
        $boolQuery = new BoolQuery();
        foreach ($words as $word) {
            $class = MatchQuery::class;
            $matchQueryBody = [
                'query' => $word,
                '_name' => $word,
            ];

            if ($allowFuzzy) {
                $matchQueryBody['fuzziness'] = '1';
            }

            $matchQuery = new $class('exact_matching_metadata', $matchQueryBody);
            $constantScore = new ConstantScore($matchQuery);
            $constantScore->setBoost(1);
            $boolQuery->addShould($constantScore);
        }

        return $boolQuery;
    }

    /**
     * @param array $words
     * @param int   $maxWords
     *
     * @return array
     */
    private function createPartialWords(array $words, int $maxWords): array
    {
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

        return $partialWords;
    }

    /**
     * @param string   $queryText
     * @param string[] $matchedPartialWords
     *
     * @return string
     */
    private function cleanQueryTextFromMatchedPartialWords(string $queryText, array $matchedPartialWords): string
    {
        \usort($matchedPartialWords, fn ($a, $b) => \strlen($b) - \strlen($a));

        foreach ($matchedPartialWords as $word) {
            $queryText = \str_replace($word, '', $queryText);
        }

        return \trim($queryText);
    }

    /**
     * @param ElasticaResultSet $resultSet
     *
     * @return ElasticaResult
     */
    private function getBestFoundWordFromMatchedWords(ElasticaResultSet $resultSet): ElasticaResult
    {
        $results = $resultSet->getResults();
        $desiredScore = $results[0]->getScore();
        $results = \array_filter($results, fn (ElasticaResult $result) => $result->getScore() === $desiredScore);
        $desiredResult = $results[0];
        $desiredResultStrLen = \array_reduce($desiredResult->getParam('matched_queries'), function (int $carry, string $word) {
            return $carry + \strlen($word);
        }, 0);
        foreach ($results as $result) {
            $partialStrLen = \array_reduce($result->getParam('matched_queries'), function (int $carry, string $word) {
                return $carry + \strlen($word);
            }, 0);

            if ($partialStrLen > $desiredResultStrLen) {
                $desiredResult = $result;
                $desiredResultStrLen = $partialStrLen;
            }
        }

        return $desiredResult;
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
