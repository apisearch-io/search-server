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

namespace Apisearch\Server\Domain\Model;

/**
 * Class QueryMerger.
 */
class QueryMerger
{
    /**
     * @var string
     *
     * Pre query
     */
    const BASE = 'base';

    /**
     * @var string
     *
     * Merged query
     */
    const MERGE = 'merge';

    /**
     * @var string
     *
     * Forced query
     */
    const FORCE = 'force';

    /**
     * @var string[]
     *
     * Merge fields
     */
    const MERGE_FIELDS = [
        'filters',
        'universe_filters',
        'filter_fields',
        'items_promoted',
    ];

    /**
     * Merge queries.
     *
     * @param array  $baseQuery
     * @param array  $queryToMerge
     * @param string $type
     *
     * @return array
     */
    public static function mergeQueries(
        array $baseQuery,
        array $queryToMerge,
        string $type
    ): array {
        if (empty($queryToMerge)) {
            return $baseQuery;
        }

        if (empty($baseQuery)) {
            return $queryToMerge;
        }

        if (self::FORCE === $type) {
            return \array_merge(
                $baseQuery,
                $queryToMerge
            );
        }

        if (self::BASE === $type) {
            return \array_merge(
                $queryToMerge,
                $baseQuery
            );
        }

        $fieldsKeys = \array_fill_keys(self::MERGE_FIELDS, true);

        return \array_merge(
            \array_diff_key(\array_merge(
                $queryToMerge,
                $baseQuery
            ), $fieldsKeys),
            \array_merge(
                $baseQuery,
                \array_merge_recursive(
                    \array_intersect_key($baseQuery, \array_fill_keys(self::MERGE_FIELDS, true)),
                    \array_intersect_key($queryToMerge, \array_fill_keys(self::MERGE_FIELDS, true))
                )
            )
        );
    }
}
