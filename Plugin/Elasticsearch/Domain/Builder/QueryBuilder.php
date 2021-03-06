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

namespace Apisearch\Plugin\Elasticsearch\Domain\Builder;

use Apisearch\Geo\CoordinateAndDistance;
use Apisearch\Geo\LocationRange;
use Apisearch\Geo\Polygon;
use Apisearch\Geo\Square;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Aggregation as QueryAggregation;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;
use Apisearch\Query\Range;
use Apisearch\Query\ScoreStrategies;
use Apisearch\Query\ScoreStrategy;
use Apisearch\Query\SortBy;
use Apisearch\Server\Domain\Model\ServerQuery;
use Elastica\Aggregation as ElasticaAggregation;
use Elastica\Query as ElasticaQuery;
use Elastica\Script\Script;

/**
 * File header placeholder.
 */
class QueryBuilder
{
    /**
     * Creates an elastic query given a model query.
     *
     * @param Query                   $query
     * @param ElasticaQuery           $mainQuery
     * @param ElasticaQuery\BoolQuery $boolQuery
     *
     * @return void
     */
    public function buildQuery(
        Query $query,
        ElasticaQuery &$mainQuery,
        ElasticaQuery\BoolQuery $boolQuery
    ): void {
        $this->selectFields(
            $query,
            $mainQuery
        );

        $isARegularQuery = !$this->setMoreLikeThis($query, $boolQuery);

        if ($isARegularQuery) {
            $this->addFilters(
                $query,
                $boolQuery,
                $query->getFilters(),
                $query->getSearchableFields(),
                null,
                false
            );
        }

        $boolQuery = $this->addAdditionalBoostings(
            $query,
            $boolQuery
        );

        $universeFilters = $query->getUniverseFilters();
        if (!empty($universeFilters)) {
            $universeQuery = new ElasticaQuery\BoolQuery();

            $this->addFilters(
                $query,
                $universeQuery,
                $universeFilters,
                $query->getSearchableFields(),
                null,
                false
            );

            $universeQuery->addMust($boolQuery);
            $boolQuery = $universeQuery;
        }

        $mainQuery->setQuery($boolQuery);

        $minScore = $query->getMinScore();
        if ($minScore > 0 && !empty($query->getQueryText())) {
            $mainQuery->setMinScore($minScore);
        }

        $mainQuery = $this->setSortBy(
            $query,
            $mainQuery,
            $boolQuery
        );

        if ($isARegularQuery && $query->areAggregationsEnabled()) {
            $this->addAggregations(
                $query,
                $mainQuery,
                $query->getAggregations(),
                $query->getUniverseFilters(),
                $query->getFilters(),
                $query->getSearchableFields()
            );
        }
    }

    /**
     * Select fields.
     *
     * @param Query         $query
     * @param ElasticaQuery $mainQuery
     *
     * @return void
     */
    private function selectFields(
        Query $query,
        ElasticaQuery $mainQuery
    ) {
        if (empty($query->getFields())) {
            return;
        }

        $fields = \array_values($query->getFields());
        $fields = \array_unique($fields);
        $includes = \array_filter($fields, function (string $field) {
            return 0 !== \strpos($field, '!');
        });

        $excludes = \array_map(function (string $string) {
            return \ltrim($string, '!');
        }, \array_filter($fields, function (string $field) {
            return 0 === \strpos($field, '!');
        }));

        if (!empty($includes)) {
            $includes[] = 'uuid.*';
        } else {
            $includes = ['*'];
        }

        $mainQuery->setSource([
            'includes' => \array_values($includes),
            'excludes' => \array_values($excludes),
        ]);
    }

    /**
     * Add filters to a Query.
     *
     * @param Query                   $query
     * @param ElasticaQuery\BoolQuery $boolQuery
     * @param Filter[]                $filters
     * @param string[]                $searchableFields
     * @param string|null             $filterToIgnore
     * @param bool                    $takeInAccountDefinedTermFilter
     *
     * @return void
     */
    private function addFilters(
        Query $query,
        ElasticaQuery\BoolQuery $boolQuery,
        array $filters,
        array $searchableFields,
        ? string $filterToIgnore,
        bool $takeInAccountDefinedTermFilter
    ): void {
        foreach ($filters as $filterName => $filter) {
            $onlyAddDefinedTermFilter = (
                empty($filter->getValues()) ||
                $filterName === $filterToIgnore
            );

            $this->addFilter(
                $query,
                $boolQuery,
                $filter,
                $searchableFields,
                $onlyAddDefinedTermFilter,
                $takeInAccountDefinedTermFilter
            );
        }
    }

    /**
     * Add filters to a Query.
     *
     * @param Query                   $query
     * @param ElasticaQuery\BoolQuery $boolQuery
     * @param Filter                  $filter
     * @param string[]                $searchableFields
     * @param bool                    $onlyAddDefinedTermFilter
     * @param bool                    $takeInAccountDefinedTermFilter
     *
     * @return void
     */
    private function addFilter(
        Query $query,
        ElasticaQuery\BoolQuery $boolQuery,
        Filter $filter,
        array $searchableFields,
        bool $onlyAddDefinedTermFilter,
        bool $takeInAccountDefinedTermFilter
    ) {
        if (Filter::TYPE_QUERY === $filter->getFilterType()) {
            $queryString = $filter->getValues()[0];
            $match = $this->createMainQueryObject(
                $query,
                $queryString,
                $searchableFields
            );
            $boolQuery->addMust($match);

            return;
        }

        if (Filter::TYPE_GEO === $filter->getFilterType()) {
            $boolQuery->addMust(
                $this->createLocationFilter($filter)
            );

            return;
        }

        if ('indexed_metadata.exact_matching_tokenized' === $filter->getField()) {
            $boolQuery->addFilter(
                $this->createExactMatchingMetadataFilter($query)
            );

            return;
        }

        $this->addFieldOrRangeFilter(
            $boolQuery,
            $filter,
            $onlyAddDefinedTermFilter,
            $takeInAccountDefinedTermFilter
        );
    }

    /**
     * Add filters to a Query.
     *
     * @param ElasticaQuery\BoolQuery $boolQuery
     * @param Filter                  $filter
     * @param bool                    $onlyAddDefinedTermFilter
     * @param bool                    $takeInAccountDefinedTermFilter
     *
     * @return void
     */
    private function addFieldOrRangeFilter(
        ElasticaQuery\BoolQuery $boolQuery,
        Filter $filter,
        bool $onlyAddDefinedTermFilter,
        bool $takeInAccountDefinedTermFilter
    ): void {
        $boolQuery->addFilter(
            $this->createQueryFilterByApplicationType(
                $filter,
                $onlyAddDefinedTermFilter,
                $takeInAccountDefinedTermFilter,
                false
            )
        );
    }

    /**
     * Create a filter and decide type of match.
     *
     * @param Filter $filter
     * @param bool   $onlyAddDefinedTermFilter
     * @param bool   $takeInAccountDefinedTermFilter
     * @param bool   $checkNested
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function createQueryFilterByApplicationType(
        Filter $filter,
        bool $onlyAddDefinedTermFilter,
        bool $takeInAccountDefinedTermFilter,
        bool $checkNested = true
    ) {
        $verb = 'addMust';
        switch ($filter->getApplicationType()) {
            case Filter::AT_LEAST_ONE:
                $verb = 'addShould';
                break;
            case Filter::EXCLUDE:
                $verb = 'addMustNot';
                break;
        }

        return $this->createQueryFilterByMethod(
            $filter,
            $verb,
            $onlyAddDefinedTermFilter,
            $takeInAccountDefinedTermFilter,
            $checkNested
        );
    }

    /**
     * Creates query filter by method.
     *
     * @param Filter $filter
     * @param string $method
     * @param bool   $onlyAddDefinedTermFilter
     * @param bool   $takeInAccountDefinedTermFilter
     * @param bool   $checkNested
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function createQueryFilterByMethod(
        Filter $filter,
        string $method,
        bool $onlyAddDefinedTermFilter,
        bool $takeInAccountDefinedTermFilter,
        bool $checkNested = true
    ) {
        $boolQueryFilter = new ElasticaQuery\BoolQuery();
        if (!$onlyAddDefinedTermFilter) {
            foreach ($filter->getValues() as $value) {
                $queryFilter = $this->createQueryFilter(
                    $filter,
                    $value,
                    $checkNested
                );

                if ($queryFilter instanceof ElasticaQuery\AbstractQuery) {
                    $boolQueryFilter->$method($queryFilter);
                }
            }
        }

        /*
         * This is specifically for Tags.
         * Because you can make subgroups of Tags, each aggregation must define
         * its values from this given subgroup.
         */
        if ($takeInAccountDefinedTermFilter && !empty($filter->getFilterTerms())) {
            list($field, $value) = $filter->getFilterTerms();
            $filteringFilter = Filter::create(
                $field, $value, Filter::AT_LEAST_ONE, $filter->getFilterType(), []
            );

            $boolQueryFilter->addFilter(
                $this
                    ->createQueryFilterByApplicationType(
                        $filteringFilter,
                        false,
                        false,
                        $checkNested
                    )
            );
        }

        return $boolQueryFilter;
    }

    /**
     * Creates Term/Terms query depending on the elements value.
     *
     * @param Filter $filter
     * @param mixed  $value
     * @param bool   $checkNested
     *
     * @return ElasticaQuery\AbstractQuery|null
     */
    private function createQueryFilter(
        Filter $filter,
        $value,
        bool $checkNested = true
    ): ? ElasticaQuery\AbstractQuery {
        switch ($filter->getFilterType()) {
            case Filter::TYPE_FIELD:
                return $this->createTermFilter(
                    $filter,
                    $value,
                    $checkNested
                );

            case Filter::TYPE_RANGE:
            case Filter::TYPE_RANGE_WITH_MIN_MAX:
            case Filter::TYPE_DATE_RANGE:
            case Filter::TYPE_DATE_RANGE_WITH_MIN_MAX:
                return $this->createRangeFilter(
                    $filter,
                    $value
                );
        }

        return null;
    }

    /**
     * Create and return Term filter
     * Returns null if no need to be applicable (true=true).
     *
     * @param Filter $filter
     * @param mixed  $value
     * @param bool   $checkNested
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function createTermFilter(
        Filter $filter,
        $value,
        bool $checkNested = true
    ): ? ElasticaQuery\AbstractQuery {
        $filterField = $filter->getField();
        $filterField = \in_array($filterField, ['indexed_metadata.uuid', 'uuid'])
            ? '_id'
            : $filterField;

        return $this->createMultipleTermFilter(
            $filterField,
            $value,
            $checkNested
        );
    }

    /**
     * Create multiple Term filter.
     *
     * @param string          $field
     * @param string|string[] $value
     * @param bool            $checkNested
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function createMultipleTermFilter(
        string $field,
        $value,
        bool $checkNested
    ): ElasticaQuery\AbstractQuery {
        if (!\is_array($value)) {
            return $this->createTermFilterOrNestedFilterDependingOnTheField(
                $field,
                $value,
                $checkNested
            );
        }

        $multipleBoolQuery = new ElasticaQuery\BoolQuery();
        foreach ($value as $singleValue) {
            $multipleBoolQuery->addShould(
                $this->createTermFilterOrNestedFilterDependingOnTheField(
                    $field,
                    $singleValue,
                    $checkNested
                )
            );
        }

        return $multipleBoolQuery;
    }

    /**
     * Create term filter or nested depending on the field.
     *
     * @param string          $field
     * @param string|string[] $value
     * @param bool            $checkNested
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function createTermFilterOrNestedFilterDependingOnTheField(
        string $field,
        $value,
        bool $checkNested
    ): ElasticaQuery\AbstractQuery {
        $termFilter = new ElasticaQuery\Term([$field => $value]);
        $fieldParts = \explode('.', $field, 3);
        if ($checkNested && 3 === \count($fieldParts)) {
            $nested = new ElasticaQuery\Nested();
            $nested->setPath($fieldParts[0].'.'.$fieldParts[1]);
            $nested->setQuery($termFilter);

            return $nested;
        }

        return $termFilter;
    }

    /**
     * Create Range filter.
     *
     * @param Filter $filter
     * @param string $value
     *
     * @return ElasticaQuery\AbstractQuery|null
     */
    private function createRangeFilter(
        Filter $filter,
        string $value
    ): ? ElasticaQuery\AbstractQuery {
        list($from, $to) = Range::stringToArray($value);
        $rangeData = [];
        if (Range::MINUS_INFINITE !== $from) {
            $rangeData = [
                'gte' => $from,
            ];
        }

        if (Range::INFINITE !== $to) {
            $to = \strval($to);
            $trimmedTo = \rtrim(\strval($to), ']');
            $inclusiveTo = (\strlen($to) > \strlen($trimmedTo));
            $rangeData[($inclusiveTo ? 'lte' : 'lt')] = $trimmedTo;
        }

        $rangeClass = ElasticaQuery\Range::class;

        return empty($rangeData)
            ? null
            : new $rangeClass($filter->getField(), $rangeData);
    }

    /**
     * Create Location filter.
     *
     * @param Filter $filter
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function createLocationFilter(Filter $filter): ElasticaQuery\AbstractQuery
    {
        $locationRange = LocationRange::createFromArray($filter->getValues());
        $locationRangeData = $locationRange->toFilterArray();
        switch (\get_class($locationRange)) {
            case CoordinateAndDistance::class:

                return new ElasticaQuery\GeoDistance(
                    $filter->getField(),
                    $locationRangeData['coordinate'],
                    $locationRangeData['distance']
                );

            case Polygon::class:

                return new ElasticaQuery\GeoPolygon(
                    $filter->getField(),
                    $locationRangeData['coordinates']
                );

            case Square::class:

                return new ElasticaQuery\GeoBoundingBox(
                    $filter->getField(),
                    \array_values($locationRangeData)
                );
        }
    }

    /**
     * @param Query $query
     */
    public function createExactMatchingMetadataFilter(Query $query)
    {
        $metadata = $query->getMetadata();
        $words = $metadata['exact_matching_tokenized_words'];
        $allowFuzzy = $metadata['exact_matching_tokenized_allow_fuzzy'];
        $boolQuery = new ElasticaQuery\BoolQuery();
        $class = $allowFuzzy ? ElasticaQuery\MatchQuery::class : ElasticaQuery\MatchPhrase::class;
        foreach ($words as $word) {
            $matchQueryBody = ['query' => $word];
            if ($allowFuzzy) {
                $matchQueryBody['fuzziness'] = 'AUTO';
            }

            $matchQuery = new $class('exact_matching_metadata', $matchQueryBody);
            $boolQuery->addMust($matchQuery);
        }

        return $boolQuery;
    }

    /**
     * Add aggregations.
     *
     * @param Query              $query
     * @param ElasticaQuery      $elasticaQuery
     * @param QueryAggregation[] $aggregations
     * @param Filter[]           $universeFilters
     * @param Filter[]           $filters
     * @param string[]           $searchableFields
     *
     * @return void
     */
    private function addAggregations(
        Query $query,
        ElasticaQuery $elasticaQuery,
        array $aggregations,
        array $universeFilters,
        array $filters,
        array $searchableFields
    ): void {
        $globalAggregation = new ElasticaAggregation\GlobalAggregation('all');
        $universeAggregation = new ElasticaAggregation\Filter('universe');
        $aggregationBoolQuery = new ElasticaQuery\BoolQuery();
        $this->addFilters(
            $query,
            $aggregationBoolQuery,
            $universeFilters,
            $searchableFields,
            null,
            true
        );
        $universeAggregation->setFilter($aggregationBoolQuery);
        $globalAggregation->addAggregation($universeAggregation);
        $elasticaAggregations = [];

        foreach ($aggregations as $aggregation) {
            $filterType = $aggregation->getFilterType();
            $withFilters = true;
            switch ($filterType) {
                case Filter::TYPE_RANGE:
                case Filter::TYPE_DATE_RANGE:
                    $elasticaAggregations[] = $this->createRangeAggregation($aggregation);
                    break;
                case Filter::TYPE_RANGE_WITH_MIN_MAX:
                case Filter::TYPE_DATE_RANGE_WITH_MIN_MAX:
                    $withFilters = false;
                    $elasticaAggregations[] = $this->createRangeMinAggregation($aggregation);
                    $elasticaAggregations[] = $this->createRangeMaxAggregation($aggregation);
                    break;
                default:
                    $elasticaAggregations[] = $this->createAggregation($aggregation);
                    break;
            }

            $builtAggregation = new ElasticaAggregation\Filter($aggregation->getName());
            $boolQuery = new ElasticaQuery\BoolQuery();
            if ($withFilters) {
                $this->addFilters(
                    $query,
                    $boolQuery,
                    $filters,
                    $searchableFields,
                    $aggregation->getApplicationType() & Filter::AT_LEAST_ONE
                        ? $aggregation->getName()
                        : null,
                    true
                );
            }

            $builtAggregation->setFilter($boolQuery);
            foreach ($elasticaAggregations as $elasticaAggregation) {
                $builtAggregation->addAggregation($elasticaAggregation);
            }
            $universeAggregation->addAggregation($builtAggregation);
        }

        $elasticaQuery->addAggregation($globalAggregation);
    }

    /**
     * Create aggregation.
     *
     * @param QueryAggregation $aggregation
     *
     * @return ElasticaAggregation\AbstractAggregation
     */
    private function createAggregation(QueryAggregation $aggregation): ElasticaAggregation\AbstractAggregation
    {
        $termsAggregation = new ElasticaAggregation\Terms($aggregation->getName());
        $aggregationFields = \explode('|', $aggregation->getField());
        $termsAggregation->setField($aggregationFields[0]);
        $termsAggregation->setSize(
            $aggregation->getLimit() > 0
                ? $aggregation->getLimit()
                : 1000
        );
        $termsAggregation->setOrder($aggregation->getSort()[0], $aggregation->getSort()[1]);

        return $termsAggregation;
    }

    /**
     * Create range aggregation.
     *
     * @param QueryAggregation $aggregation
     *
     * @return ElasticaAggregation\AbstractAggregation
     */
    private function createRangeAggregation(QueryAggregation $aggregation): ElasticaAggregation\AbstractAggregation
    {
        $rangeClass = Filter::TYPE_DATE_RANGE === $aggregation->getFilterType()
            ? ElasticaAggregation\DateRange::class
            : ElasticaAggregation\Range::class;

        $rangeAggregation = new $rangeClass($aggregation->getName());
        $rangeAggregation->setKeyed();
        $rangeAggregation->setField($aggregation->getField());
        foreach ($aggregation->getSubgroup() as $range) {
            list($from, $to) = Range::stringToArray($range);
            $rangeAggregation->addRange($from, $to, $range);
        }

        return $rangeAggregation;
    }

    /**
     * @param QueryAggregation $aggregation
     *
     * @return ElasticaAggregation\AbstractAggregation
     */
    private function createRangeMinAggregation(QueryAggregation $aggregation): ElasticaAggregation\AbstractAggregation
    {
        $minAggregation = new ElasticaAggregation\Min('min');
        $minAggregation->setField($aggregation->getField());

        return $minAggregation;
    }

    /**
     * @param QueryAggregation $aggregation
     *
     * @return ElasticaAggregation\AbstractAggregation
     */
    private function createRangeMaxAggregation(QueryAggregation $aggregation): ElasticaAggregation\AbstractAggregation
    {
        $minAggregation = new ElasticaAggregation\Max('max');
        $minAggregation->setField($aggregation->getField());

        return $minAggregation;
    }

    /**
     * Create main query object.
     *
     * @param Query  $query
     * @param string $queryString
     * @param array  $searchableFields
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function createMainQueryObject(
        Query $query,
        string $queryString,
        array $searchableFields
    ): ElasticaQuery\AbstractQuery {
        if (empty($queryString)) {
            $match = new ElasticaQuery\MatchAll();
        } else {
            $fuzziness = $query->getFuzziness();
            $searchableFields = empty($searchableFields)
                ? [
                    'searchable_metadata.*',
                    'exact_matching_metadata^5',
                ]
                : $searchableFields;

            $match = \is_array($fuzziness)
                ? $this->createMainQueryObjectAsFuzzy(
                    $queryString,
                    $searchableFields,
                    $fuzziness
                )
                : $this->createMainQueryObjectAsMatchAll(
                    $queryString,
                    $searchableFields,
                    $fuzziness
                );
        }

        return $this->setScoreStrategies(
            $query,
            $match
        );
    }

    /**
     * Create main query object as a multimatch.
     *
     * @param string            $queryString
     * @param array             $searchableFields
     * @param float|string|null $fuzziness
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function createMainQueryObjectAsMatchAll(
        string $queryString,
        array $searchableFields,
        $fuzziness
    ): ElasticaQuery\AbstractQuery {
        $match = new ElasticaQuery\MultiMatch();
        $match
            ->setFields($searchableFields)
            ->setQuery($queryString);

        if (!\is_null($fuzziness)) {
            $match->setFuzziness($fuzziness);
        }

        return $match;
    }

    /**
     * Create main query object as a multimatch.
     *
     * @param string $queryString
     * @param array  $searchableFields
     * @param array  $fuzziness
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function createMainQueryObjectAsFuzzy(
        string $queryString,
        array $searchableFields,
        array $fuzziness
    ): ElasticaQuery\AbstractQuery {
        $boolQuery = new ElasticaQuery\BoolQuery();
        foreach ($searchableFields as $filterField) {
            $filterFieldParts = \explode('^', $filterField, 2);
            $filterFieldWithoutWeight = $filterFieldParts[0];
            $specificFuzziness = $fuzziness[$filterFieldWithoutWeight] ?? false;

            $match = $specificFuzziness
                ? new ElasticaQuery\MatchQuery($filterFieldWithoutWeight)
                : new ElasticaQuery\MatchPhrase($filterFieldWithoutWeight);

            $match->setFieldQuery($filterFieldWithoutWeight, $queryString);

            if (isset($filterFieldParts[1])) {
                $match->setFieldBoost(
                    $filterFieldWithoutWeight,
                    (float) ($filterFieldParts[1])
                );
            }

            if ($specificFuzziness) {
                $match->setFieldFuzziness(
                    $filterFieldWithoutWeight,
                    $specificFuzziness
                );
            }

            $boolQuery->addShould($match);
        }

        return $boolQuery;
    }

    /**
     * Set score strategies.
     *
     * @param Query                       $query
     * @param ElasticaQuery\AbstractQuery $elasticaQuery
     *
     * @return ElasticaQuery\AbstractQuery
     */
    private function setScoreStrategies(
        Query $query,
        ElasticaQuery\AbstractQuery $elasticaQuery
    ): ElasticaQuery\AbstractQuery {
        $scoreStrategies = $query->getScoreStrategies();

        if (
            !($scoreStrategies instanceof ScoreStrategies) ||
            empty($scoreStrategies->getScoreStrategies())
        ) {
            return $elasticaQuery;
        }

        /**
         * @var ScoreStrategy[]
         */
        $scoreStrategiesArray = \array_filter($scoreStrategies->getScoreStrategies(), function (ScoreStrategy $scoreStrategy) {
            return $scoreStrategy->getConfigurationValue('match_main_query') ?? true;
        });

        $newQuery = new ElasticaQuery\FunctionScore();
        $boolQuery = new ElasticaQuery\BoolQuery();
        $boolQuery->addMust($elasticaQuery);
        $newQuery->setQuery($boolQuery);
        $newQuery->setScoreMode($scoreStrategies->getScoreMode());
        $newQuery->setBoostMode($scoreStrategies->getScoreMode());

        /*
         * @var ScoreStrategy
         */
        foreach ($scoreStrategiesArray as $scoreStrategy) {
            $filters = $scoreStrategy->getFilters() ?: [$scoreStrategy->getFilter()];
            $mustAllFilter = null;

            if ($filters !== [null]) {
                $mustAllFilter = new ElasticaQuery\BoolQuery();
                foreach ($filters as $filter) {
                    $mustAllFilter->addMust($this->createQueryFilterByApplicationType(
                        $filter,
                        false,
                        false,
                        false
                    ));
                }
            }

            $field = Item::getPathByField(
                (string) $scoreStrategy->getConfigurationValue('field')
            );
            $fieldParts = \explode('.', $field);
            $nestedFunctionQuery = null;

            if (\count($fieldParts) >= 3) {
                \array_pop($fieldParts);
                $nestedQuery = new ElasticaQuery\Nested();
                $boolQuery->addShould($nestedQuery);
                $nestedQuery->setPath(\implode('.', $fieldParts));
                $nestedQuery->setScoreMode($scoreStrategy->getScoreMode());
                $nestedFunctionQuery = new ElasticaQuery\FunctionScore();
                $nestedQuery->setQuery($nestedFunctionQuery);
            }

            switch ($scoreStrategy->getType()) {
                case ScoreStrategy::BOOSTING_FIELD_VALUE:
                    $this->addBoostingFieldValueScoreStrategy(
                        $scoreStrategy,
                        $nestedFunctionQuery instanceof ElasticaQuery\FunctionScore
                            ? $nestedFunctionQuery
                            : $newQuery,
                        $mustAllFilter
                    );
                    break;
                case ScoreStrategy::DECAY:
                    $this->addDecayFunctionScoreStrategy(
                        $scoreStrategy,
                        $nestedFunctionQuery instanceof ElasticaQuery\FunctionScore
                            ? $nestedFunctionQuery
                            : $newQuery,
                        $mustAllFilter
                    );
                    break;
                case ScoreStrategy::CUSTOM_FUNCTION:
                    $this->addCustomFunctionScoreStrategy(
                        $scoreStrategy,
                        $newQuery,
                        $mustAllFilter
                    );
                    break;

                case ScoreStrategy::WEIGHT:
                    $this->addWeightScoreStrategy(
                        $scoreStrategy,
                        $newQuery,
                        $mustAllFilter
                    );
                    break;
            }
        }

        return $newQuery;
    }

    /**
     * Create score strategy by using a custom function.
     *
     * @param ScoreStrategy                    $scoreStrategy
     * @param ElasticaQuery\FunctionScore      $functionScore
     * @param ElasticaQuery\AbstractQuery|null $filter
     *
     * @return void
     */
    private function addCustomFunctionScoreStrategy(
        ScoreStrategy $scoreStrategy,
        ElasticaQuery\FunctionScore $functionScore,
        ?ElasticaQuery\AbstractQuery $filter
    ): void {
        $functionScore->addScriptScoreFunction(
            new Script($scoreStrategy->getConfigurationValue('function')),
            $filter,
            $scoreStrategy->getWeight()
        );
    }

    /**
     * Create score strategy by using a weight function.
     *
     * @param ScoreStrategy                    $scoreStrategy
     * @param ElasticaQuery\FunctionScore      $functionScore
     * @param ElasticaQuery\AbstractQuery|null $filter
     *
     * @return void
     */
    private function addWeightScoreStrategy(
        ScoreStrategy $scoreStrategy,
        ElasticaQuery\FunctionScore $functionScore,
        ?ElasticaQuery\AbstractQuery $filter
    ): void {
        $functionScore->addWeightFunction(
            $scoreStrategy->getWeight(),
            $filter
        );
    }

    /**
     * Create score strategy by field value.
     *
     * @param ScoreStrategy                    $scoreStrategy
     * @param ElasticaQuery\FunctionScore      $functionScore
     * @param ElasticaQuery\AbstractQuery|null $filter
     *
     * @return void
     */
    private function addBoostingFieldValueScoreStrategy(
        ScoreStrategy $scoreStrategy,
        ElasticaQuery\FunctionScore $functionScore,
        ?ElasticaQuery\AbstractQuery $filter
    ): void {
        $functionScore->addFieldValueFactorFunction(
            Item::getPathByField(
                (string) $scoreStrategy->getConfigurationValue('field')
            ),
            (float) $scoreStrategy->getConfigurationValue('factor'),
            (string) $scoreStrategy->getConfigurationValue('modifier'),
            (float) $scoreStrategy->getConfigurationValue('missing'),
            $scoreStrategy->getWeight(),
            $filter
        );
    }

    /**
     * Create score strategy by using a decay function.
     *
     * @param ScoreStrategy                    $scoreStrategy
     * @param ElasticaQuery\FunctionScore      $functionScore
     * @param ElasticaQuery\AbstractQuery|null $filter
     *
     * @return void
     */
    private function addDecayFunctionScoreStrategy(
        ScoreStrategy $scoreStrategy,
        ElasticaQuery\FunctionScore $functionScore,
        ?ElasticaQuery\AbstractQuery $filter
    ): void {
        $functionScore->addDecayFunction(
            (string) $scoreStrategy->getConfigurationValue('type'),
            Item::getPathByField(
                (string) $scoreStrategy->getConfigurationValue('field')
            ),
            (string) $scoreStrategy->getConfigurationValue('origin'),
            (string) $scoreStrategy->getConfigurationValue('scale'),
            (string) $scoreStrategy->getConfigurationValue('offset'),
            (float) $scoreStrategy->getConfigurationValue('decay'),
            $scoreStrategy->getWeight(),
            $filter
        );
    }

    /**
     * Build sort.
     *
     * @param Query                   $query
     * @param ElasticaQuery           $mainQuery
     * @param ElasticaQuery\BoolQuery $boolQuery
     *
     * @return ElasticaQuery
     */
    private function setSortBy(
        Query $query,
        ElasticaQuery $mainQuery,
        ElasticaQuery\BoolQuery $boolQuery
    ): ElasticaQuery {
        $sortBy = $query->getSortBy();
        if ($sortBy->hasRandomSort()) {
            /**
             * Random elements in Elasticsearch need a wrapper in order to
             * apply a random score per each result.
             */
            $functionScore = new ElasticaQuery\FunctionScore();
            $functionScore->addRandomScoreFunction(\rand());
            $functionScore->setQuery($boolQuery);
            $newMainQuery = new ElasticaQuery();
            $newMainQuery->setQuery($functionScore);
            $mainQuery = $newMainQuery;

            return $mainQuery;
        }

        $sortByElements = $sortBy->all();

        /*
         * Because elasticsearch, by default, sorts by score, if score is the
         * only applied sortBy (or by default, because no sortBy elements were
         * added) we will skip this step
         */
        if (
            1 === \count($sortByElements) &&
            SortBy::SCORE === $sortByElements[0]
        ) {
            return $mainQuery;
        }

        $sortByElements = \array_map(function (array $sortBy) {
            $type = $sortBy['type'] ?? SortBy::TYPE_FIELD;
            $mode = $sortBy['mode'] ?? SortBy::MODE_AVG;
            $order = $sortBy['order'] ?? SortBy::ASC;

            switch ($type) {
                case SortBy::TYPE_SCORE:
                    return '_score';

                case SortBy::TYPE_FUNCTION:
                    return [
                        '_script' => [
                            'type' => 'number',
                            'script' => [
                                'lang' => 'painless',
                                'source' => $sortBy['function'],
                            ],
                            'order' => $order,
                        ],
                    ];

                case SortBy::TYPE_FIELD:
                    return [
                        $sortBy['field'] => [
                            'order' => $order,
                        ],
                    ];

                case SortBy::TYPE_DISTANCE:
                    return [
                        '_geo_distance' => [
                            'coordinate' => $sortBy['coordinate']->toArray(),
                            'order' => SortBy::ASC,
                            'unit' => $sortBy['unit'],
                        ],
                    ];

                case SortBy::TYPE_NESTED:

                    $key = $sortBy['field'];
                    $path = \explode('.', $key);
                    \array_pop($path);

                    return [
                        $key => [
                            'mode' => $mode,
                            'order' => $order,
                            'nested' => \array_filter([
                                'path' => \implode('.', $path),
                                'filter' => (
                                isset($sortBy['filter'])
                                    ? $this->createQueryFilterByApplicationType(
                                    $sortBy['filter'],
                                    false,
                                    false,
                                    false
                                ) : false
                                ),
                            ]),
                        ],
                    ];
            }

            return [];
        }, $sortByElements);

        $mainQuery->setSort($sortByElements);

        return $mainQuery;
    }

    /**
     * Add parallel filters.
     *
     * @param Query                   $query
     * @param ElasticaQuery\BoolQuery $boolQuery
     *
     * @return ElasticaQuery\BoolQuery
     */
    private function addAdditionalBoostings(
        Query $query,
        ElasticaQuery\BoolQuery $boolQuery
    ) {
        $scoreStrategies = $query->getScoreStrategies();

        if (
            !($scoreStrategies instanceof ScoreStrategies) ||
            empty($scoreStrategies->getScoreStrategies())
        ) {
            return $boolQuery;
        }

        /**
         * @var ScoreStrategy[]
         */
        $scoreStrategiesArray = \array_filter($scoreStrategies->getScoreStrategies(), function (ScoreStrategy $scoreStrategy) {
            return !($scoreStrategy->getConfigurationValue('match_main_query') ?? true);
        });

        if (empty($scoreStrategiesArray)) {
            return $boolQuery;
        }

        $parentBoolQuery = new ElasticaQuery\BoolQuery();
        $parentBoolQuery->addShould($boolQuery);
        foreach ($scoreStrategiesArray as $scoreStrategy) {
            $constantQuery = new ElasticaQuery\ConstantScore();
            $functionQuery = new ElasticaQuery\BoolQuery();
            $constantQuery->setBoost($scoreStrategy->getWeight());
            $constantQuery->setFilter($functionQuery);
            $filters = $scoreStrategy->getFilters() ?: [$scoreStrategy->getFilter()];

            if ($filters !== [null]) {
                foreach ($filters as $filter) {
                    $partialFilter = new ElasticaQuery\BoolQuery();
                    $functionQuery->addMust($partialFilter);
                    $this->addFieldOrRangeFilter(
                        $partialFilter,
                        $filter,
                        false,
                        false
                    );
                }
            }

            $parentBoolQuery->addShould($constantQuery);
        }

        return $parentBoolQuery;
    }

    /**
     * @param Query                   $query
     * @param ElasticaQuery\BoolQuery $boolQuery
     *
     * @return bool
     */
    private function setMoreLikeThis(
        Query $query,
        ElasticaQuery\BoolQuery $boolQuery
    ): bool {
        if (
            !$query instanceof ServerQuery ||
            empty($query->getLikeItems())
        ) {
            return false;
        }

        $moreLikeThis = new ElasticaQuery\MoreLikeThis();
        $moreLikeThis->setLike(\array_map(function (ItemUUID $itemUUID) {
            return [
                '_id' => $itemUUID->composeUUID(),
            ];
        }, $query->getLikeItems()));
        $moreLikeThis->setMinDocFrequency(5);
        $moreLikeThis->setMinTermFrequency(1);

        $boolQuery->addMust($moreLikeThis);

        return true;
    }
}
