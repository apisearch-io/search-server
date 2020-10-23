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

namespace Apisearch\Plugin\Campaign\Domain;

use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignCriteria;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;
use Apisearch\Query\Range;
use Apisearch\Repository\RepositoryReference;
use DateTime;

/**
 * Class Matcher.
 */
class Matcher
{
    /**
     * @param RepositoryReference $repositoryReference
     * @param Campaign            $campaign
     *
     * @return bool
     */
    public function repositoryReferenceMatchesCampaign(
        RepositoryReference $repositoryReference,
        Campaign $campaign
    ): bool {
        $indexUUIDComposed = $repositoryReference->getIndexUUID()->composeUUID();

        if (empty($indexUUIDComposed)) {
            return true;
        }

        $indices = \explode(',', $indexUUIDComposed);
        $indices = \array_map('trim', $indices);
        $indices = \array_flip($indices);

        return isset($indices[$campaign->getIndexUUID()->composeUUID()]);
    }

    /**
     * Match a query and return if the campaign should apply.
     *
     * @param Query    $query
     * @param Campaign $campaign
     * @param DateTime $dateTime
     *
     * @return bool
     */
    public function queryMatchesCampaign(
        Query $query,
        Campaign $campaign,
        DateTime $dateTime
    ): bool {
        $dateTimeTimestamp = $dateTime->getTimestamp();
        $fromTimestamp = $campaign->getFromTimestamp();
        $toTimestamp = $campaign->getToTimestamp();

        /*
         * Campaign not active
         */
        if (
            ($fromTimestamp && $dateTimeTimestamp < $fromTimestamp) ||
            ($toTimestamp && $dateTimeTimestamp >= $toTimestamp)
        ) {
            return false;
        }

        if (empty($campaign->getMatchCriteria())) {
            return true;
        }

        $matchCriteriaMode = $campaign->getMatchCriteriaMode();
        if (Campaign::MATCH_CRITERIA_MODE_MUST_ALL === $matchCriteriaMode) {
            foreach ($campaign->getMatchCriteria() as $criteria) {
                if (!$this->queryMatchesCriteria(
                    $query,
                    $criteria
                )) {
                    return false;
                }
            }

            return true;
        }

        if (Campaign::MATCH_CRITERIA_MODE_AT_LEAST_ONE === $matchCriteriaMode) {
            foreach ($campaign->getMatchCriteria() as $criteria) {
                if ($this->queryMatchesCriteria(
                    $query,
                    $criteria
                )) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * @param Query            $query
     * @param CampaignCriteria $criteria
     *
     * @return bool
     */
    private function queryMatchesCriteria(
        Query $query,
        CampaignCriteria $criteria
    ): bool {
        $queryMatches = true;

        if (!\is_null($criteria->getQueryText())) {
            $queryText = \trim(\strtolower($query->getQueryText()));
            $criteriaText = \trim(\strtolower($criteria->getQueryText()));

            $queryMatches = ('' === $queryText || '' === $criteriaText)
                ? $queryText === $criteriaText
                :
                    (
                        CampaignCriteria::MATCH_TYPE_EXACT === $criteria->getMatchType() &&
                        $criteriaText === $queryText
                    ) ||
                    (
                        CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT === $criteria->getMatchType() &&
                        (
                            $criteriaText === $queryText ||
                            str_contains($queryText, $criteriaText)
                        )
                    ) ||
                    (
                        CampaignCriteria::MATCH_TYPE_SIMILAR === $criteria->getMatchType() &&
                        (
                            $criteriaText === $queryText ||
                            \levenshtein($queryText, $criteriaText) <= 1
                        )
                    ) ||
                    (
                        CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR === $criteria->getMatchType() &&
                        (
                            $criteriaText === $queryText ||
                            \count(\array_filter(\explode(' ', $queryText), function (string $word) use ($criteriaText) {
                                return \levenshtein(\trim($word), $criteriaText) <= 1;
                            })) > 0
                        )
                    );
        }

        return
            $queryMatches &&
            $this->queryMatchesFilters($query, $criteria->getFilters());
    }

    /**
     * All filters must match.
     *
     * @param Query    $query
     * @param Filter[] $filters
     *
     * @return bool
     */
    private function queryMatchesFilters(
        Query $query,
        array $filters
    ): bool {
        foreach ($filters as $filter) {
            if (
                Filter::TYPE_FIELD === $filter->getFilterType() &&
                !$this->queryMatchesFieldFilter($query, $filter)
            ) {
                return false;
            }

            if (
                Filter::TYPE_RANGE === $filter->getFilterType() &&
                !$this->queryMatchesRangeFilter($query, $filter)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * All field filters must match.
     *
     * @param Query  $query
     * @param Filter $filter
     *
     * @return bool
     */
    private function queryMatchesFieldFilter(
        Query $query,
        Filter $filter
    ): bool {
        $filterValuesCount = \count($filter->getValues());
        if (0 === $filterValuesCount) {
            return true;
        }

        foreach ($query->getFilters() as $queryFilter) {
            if (
                Filter::TYPE_FIELD === $queryFilter->getFilterType() &&
                $queryFilter->getField() === $filter->getField() &&
                \count(\array_intersect(
                    $queryFilter->getValues(),
                    $filter->getValues()
                )) === $filterValuesCount
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * All range filters must match.
     *
     * @param Query  $query
     * @param Filter $filter
     *
     * @return bool
     */
    private function queryMatchesRangeFilter(
        Query $query,
        Filter $filter
    ): bool {
        $filterValuesCount = \count($filter->getValues());
        if (0 === $filterValuesCount) {
            return true;
        }

        foreach ($query->getFilters() as $queryFilter) {
            if (
                Filter::TYPE_RANGE === $queryFilter->getFilterType() &&
                $queryFilter->getField() === $filter->getField() &&
                $this->rangeIsIncludedIntoRange(
                    Range::stringToArray($queryFilter->getValues()[0]),
                    Range::stringToArray($filter->getValues()[0])
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Range1 is included into Range2.
     *
     * @param array $range1
     * @param array $range2
     *
     * @return bool
     */
    private function rangeIsIncludedIntoRange(
        array $range1,
        array $range2
    ): bool {
        if (
            Range::MINUS_INFINITE === $range1[0] &&
            Range::MINUS_INFINITE !== $range2[0]
        ) {
            return false;
        }

        if (
            Range::INFINITE === $range1[1] &&
            Range::INFINITE !== $range2[1]
        ) {
            return false;
        }

        return
            (
                Range::MINUS_INFINITE === $range2[0] ||
                $range2[0] <= $range1[0]
            ) &&
            (
                Range::INFINITE === $range2[1] ||
                $range2[1] >= $range1[1]
            );
    }
}
