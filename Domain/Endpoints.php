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

namespace Apisearch\Server\Domain;

/**
 * Class Endpoints.
 */
class Endpoints
{
    /**
     * Get all endpoints.
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            'v1_put_token',
            'v1_delete_token',
            'v1_get_tokens',
            'v1_delete_tokens',

            'v1_get_indices',
            'v1_put_index',
            'v1_delete_index',
            'v1_reset_index',
            'v1_configure_index',
            'v1_check_index',
            'v1_export_index',

            'v1_put_items',
            'v1_update_items_by_query',
            'v1_delete_items',

            'v1_get_usage',
            'v1_get_usage_per_day',
            'v1_get_index_usage',
            'v1_get_index_usage_per_day',

            'v1_query',
            'v1_query_all_indices',

            'v1_post_interaction',

            'check_health',
            'ping',
        ];
    }

    /**
     * Clean input with only valid elements.
     *
     * @param string[] $permissions
     *
     * @return string[]
     */
    public static function filter(array $permissions)
    {
        return \array_intersect(
            $permissions,
            self::all()
        );
    }

    /**
     * Read and Write endpoints.
     */
    public static function readWrite(): array
    {
        return self::all();
    }

    /**
     * Query endpoints.
     */
    public static function queryOnly(): array
    {
        return [
            'v1_query',
            'v1_query_all_indices',
        ];
    }

    /**
     * Query endpoints.
     */
    public static function usageOnly(): array
    {
        return [
            'v1_get_usage',
            'v1_get_usage_per_day',
            'v1_get_index_usage',
            'v1_get_index_usage_per_day',
        ];
    }

    /**
     * Interaction endpoints.
     */
    public static function interactionOnly(): array
    {
        return [
            'v1_post_interaction',
        ];
    }
}
