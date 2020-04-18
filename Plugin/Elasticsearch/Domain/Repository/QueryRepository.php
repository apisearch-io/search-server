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
use Apisearch\Model\ItemUUID;
use Apisearch\Plugin\Elasticsearch\Domain\Builder\QueryBuilder;
use Apisearch\Plugin\Elasticsearch\Domain\Builder\ResultBuilder;
use Apisearch\Plugin\Elasticsearch\Domain\ElasticaWrapper;
use Apisearch\Plugin\Elasticsearch\Domain\Polyfill;
use Apisearch\Plugin\Elasticsearch\Domain\Search;
use Apisearch\Plugin\Elasticsearch\Domain\WithElasticaWrapper;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Repository\Repository\QueryRepository as QueryRepositoryInterface;
use Elastica\Multi\ResultSet as ElasticaMultiResultSet;
use Elastica\Query as ElasticaQuery;
use Elastica\ResultSet as ElasticaResultSet;
use Elastica\Suggest;
use React\Promise\PromiseInterface;

/**
 * Class QueryRepository.
 */
class QueryRepository extends WithElasticaWrapper implements QueryRepositoryInterface
{
    /**
     * @var QueryBuilder
     *
     * Query builder
     */
    private $queryBuilder;

    /**
     * @var ResultBuilder
     *
     * Result builder
     */
    private $resultBuilder;

    /**
     * ElasticaSearchRepository constructor.
     *
     * @param ElasticaWrapper $elasticaWrapper
     * @param bool            $refreshOnWrite
     * @param QueryBuilder    $queryBuilder
     * @param ResultBuilder   $resultBuilder
     */
    public function __construct(
        ElasticaWrapper $elasticaWrapper,
        bool $refreshOnWrite,
        QueryBuilder $queryBuilder,
        ResultBuilder $resultBuilder
    ) {
        parent::__construct(
            $elasticaWrapper,
            $refreshOnWrite
        );

        $this->queryBuilder = $queryBuilder;
        $this->resultBuilder = $resultBuilder;
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
     * Create Elasticsearch query by model query.
     *
     * @param Query $query
     *
     * @return ElasticaQuery
     */
    private function createElasticaQueryByModelQuery(Query $query): ElasticaQuery
    {
        $mainQuery = new ElasticaQuery();
        $boolQuery = new ElasticaQuery\BoolQuery();
        $this
            ->queryBuilder
            ->buildQuery(
                $query,
                $mainQuery,
                $boolQuery
            );

        $this->promoteUUIDs(
            $boolQuery,
            $query->getItemsPromoted()
        );

        if ($query->areHighlightEnabled()) {
            $this->addHighlights($mainQuery);
        }

        $this->addSuggest(
            $mainQuery,
            $query
        );

        $mainQuery->setExplain(false);

        return $mainQuery;
    }

    /**
     * Add suggest into an Elastica Query.
     *
     * @param ElasticaQuery $mainQuery
     * @param Query         $query
     */
    private function addSuggest($mainQuery, $query)
    {
        if ($query->areSuggestionsEnabled()) {
            $completitionText = new Suggest\Completion(
                'completion',
                'suggest'
            );
            $completitionText->setText($query->getQueryText());

            $mainQuery->setSuggest(
                new Suggest($completitionText)
            );
        }
    }

    /**
     * Promote UUID.
     *
     * The boosting values go from 1 (not included) to 3 (not included)
     *
     * @param ElasticaQuery\BoolQuery $boolQuery
     * @param ItemUUID[]              $itemsPriorized
     */
    private function promoteUUIDs(
        ElasticaQuery\BoolQuery $boolQuery,
        array $itemsPriorized
    ) {
        if (empty($itemsPriorized)) {
            return;
        }

        $it = \count($itemsPriorized);
        foreach ($itemsPriorized as $position => $itemUUID) {
            $boolQuery->addShould(new ElasticaQuery\Term([
                '_id' => [
                    'value' => $itemUUID->composeUUID(),
                    'boost' => 10 + ($it-- / (\count($itemsPriorized) + 1)),
                ],
            ]));
        }
    }

    /**
     * Highlight.
     *
     * @param ElasticaQuery $query
     */
    private function addHighlights(ElasticaQuery $query)
    {
        $query->setHighlight([
            'fields' => [
                '*' => [
                    'fragment_size' => 100,
                    'number_of_fragments' => 3,
                ],
            ],
        ]);
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
