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
     * @param array  $mergeableQuery
     * @param string $type
     *
     * @return array
     */
    public static function mergeQueries(
        array $baseQuery,
        array $mergeableQuery,
        string $type
    ): array {
        if (empty($mergeableQuery)) {
            return $baseQuery;
        }

        if (empty($baseQuery)) {
            return $mergeableQuery;
        }

        if (self::FORCE === $type) {
            return \array_merge(
                $baseQuery,
                $mergeableQuery
            );
        }

        if (self::BASE === $type) {
            return \array_merge(
                $mergeableQuery,
                $baseQuery
            );
        }

        $fieldsKeys = \array_fill_keys(self::MERGE_FIELDS, true);

        return \array_merge(
            \array_diff_key(\array_merge(
                $mergeableQuery,
                $baseQuery
            ), $fieldsKeys),
            \array_merge(
                $baseQuery,
                \array_merge_recursive(
                    \array_intersect_key($baseQuery, \array_fill_keys(self::MERGE_FIELDS, true)),
                    \array_intersect_key($mergeableQuery, \array_fill_keys(self::MERGE_FIELDS, true))
                )
            )
        );
    }
}
