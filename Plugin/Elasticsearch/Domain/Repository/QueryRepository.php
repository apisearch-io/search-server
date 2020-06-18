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

use Apisearch\Model\IndexUUID;
use Apisearch\Model\Item;
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
use Apisearch\Server\Domain\Repository\Repository\QueryRepository as QueryRepositoryInterface;
use Elastica\Multi\ResultSet as ElasticaMultiResultSet;
use Elastica\ResultSet as ElasticaResultSet;
use function Drift\React\wait_for_stream_listeners;
use function React\Promise\resolve;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Stream\DuplexStreamInterface;
use React\Stream\ThroughStream;
use React\Stream\TransformerStream;

/**
 * Class QueryRepository.
 */
class QueryRepository extends WithElasticaWrapper implements QueryRepositoryInterface
{
    use Transformers;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var ResultBuilder
     */
    private $resultBuilder;

    /**
     * @var LoopInterface
     */
    private $loop;

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
            ? $this->makeMultiQuery($repositoryReference, $query)
            : $this->makeSimpleQuery($repositoryReference, $query);
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
                            $stream->write($item);
                        }
                    });

                $sourceStream->pipe($elasticaTransformer);
                $elasticaTransformer->on('close', function () use ($sourceStream) {
                    $sourceStream->close();
                });
            });

        return resolve($stream);
    }

    /**
     * Make simple query.
     *
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface
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
        $repositoryReferencies = [];
        foreach ($query->getSubqueries() as $name => $subquery) {
            $repositoryReferencies[] = $this->getRepositoryReferenceIndexSpecific(
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
                $name
            );
        }

        return $this
            ->elasticaWrapper
            ->multisearch(
                $repositoryReferencies,
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
            foreach ($suggests['completion'][0]['options'] as $suggest) {
                $result->addSuggest($suggest['text']);
            }
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
