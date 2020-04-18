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

use Apisearch\Query\Filter;
use Apisearch\Query\Query;
use Apisearch\Result\Aggregation as ResultAggregation;
use Apisearch\Result\Aggregations as ResultAggregations;

/**
 * Class ResultBuilder.
 */
class ResultBuilder
{
    /**
     * Build result aggregations.
     *
     * @param Query $query
     * @param array $resultAggregations
     *
     * @return ResultAggregations
     */
    public function buildResultAggregations(
        Query $query,
        array $resultAggregations
    ): ResultAggregations {
        $aggregations = new ResultAggregations($resultAggregations['doc_count']);
        unset($resultAggregations['doc_count']);
        unset($resultAggregations['meta']);
        foreach ($resultAggregations as $aggregationName => $resultAggregation) {
            $queryAggregation = $query->getAggregation($aggregationName);
            $relatedFilter = $query->getFilter($aggregationName);
            $relatedFilterValues = $relatedFilter instanceof Filter
                ? $relatedFilter->getValues()
                : [];

            $aggregation = new ResultAggregation(
                $aggregationName,
                $queryAggregation->getApplicationType(),
                $resultAggregation['doc_count'],
                $relatedFilterValues
            );

            $aggregations->addAggregation($aggregationName, $aggregation);
            $buckets = isset($resultAggregation[$aggregationName]['buckets'])
                ? $resultAggregation[$aggregationName]['buckets']
                : $resultAggregation[$aggregationName][$aggregationName]['buckets'];

            if (empty($buckets)) {
                continue;
            }

            foreach ($buckets as $key => $bucket) {
                $usedKey = $bucket['key'] ?? $key;
                if (
                    empty($queryAggregation->getSubgroup()) ||
                    \in_array($usedKey, $queryAggregation->getSubgroup())
                ) {
                    $aggregation->addCounter(
                        (string) $usedKey,
                        (int) $bucket['doc_count']
                    );
                }
            }

            /*
             * We should filter the bucket elements with level that are not part
             * of the result.
             *
             * * Filter type MUST_ALL
             * * Elements already filtered
             * * Elements with level (if exists) than the highest one
             */
            if (Filter::MUST_ALL_WITH_LEVELS === $queryAggregation->getApplicationType()) {
                $aggregation->cleanCountersByLevel();
            }
        }

        return $aggregations;
    }
}
