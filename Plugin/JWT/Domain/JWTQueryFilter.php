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

namespace Apisearch\Plugin\JWT\Domain;

use Apisearch\Query\Filter;
use Apisearch\Query\Query;
use Apisearch\Query\Query as QueryModel;

/**
 * Class JWTQueryFilter.
 *
 * Given a JWT  token payload
 *  role: user
 *  user_id: 123
 *
 * We can create a small tree of filter assignations. All elements inside this
 * list will be considered as a AND between them
 *
 *  filters:
 *      role: // field
 *          user: // value
 *              field_1: {{user_id}}
 *              field_2: [anothervalue]
 *          *:
 *              field3: $1
 */
final class JWTQueryFilter
{
    private array $filters;

    /**
     * @param array $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @param QueryModel $query
     * @param array      $jwtPayload
     *
     * @return void
     */
    public function configureQueryByArrayAndJWTPayload(
        QueryModel $query,
        array $jwtPayload
    ): void {
        $filters = $this->filters;
        $placeholders = \array_map(function (string $key) {
            return "{{{$key}}}";
        }, \array_keys($jwtPayload));
        $placeholderValues = \array_values($jwtPayload);

        foreach ($filters as $field => $values) {
            if (!isset($jwtPayload[$field])) {
                continue;
            }

            if (isset($values['*'])) {
                $this->applyConditionsToQuery(
                    $query,
                    $values['*'],
                    \array_merge($placeholders, ['$1']),
                    \array_merge($placeholderValues, [$jwtPayload[$field]])
                );

                continue;
            }

            if (isset($values[$jwtPayload[$field]])) {
                $this->applyConditionsToQuery(
                    $query,
                    $values[$jwtPayload[$field]],
                    $placeholders,
                    $placeholderValues
                );
            }
        }
    }

    /**
     * @param QueryModel $query
     * @param array      $conditions
     * @param array      $placeholders
     * @param array      $placeholderValues
     *
     * @return void
     */
    private function applyConditionsToQuery(
        Query $query,
        array $conditions,
        array $placeholders,
        array $placeholderValues
    ): void {
        foreach ($conditions as $conditionField => $conditionValues) {
            $conditionValues = \is_array($conditionValues) ? $conditionValues : [$conditionValues];
            $conditionValues = \array_map(function (string $conditionValue) use ($placeholders, $placeholderValues) {
                return \str_replace($placeholders, $placeholderValues, $conditionValue);
            }, $conditionValues);

            $query->filterUniverseBy($conditionField, $conditionValues, Filter::AT_LEAST_ONE);
        }
    }
}
