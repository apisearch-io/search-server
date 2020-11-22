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

class EndpointNormalizer
{
    /**
     * @param string[] $endpoints
     *
     * @return string[]
     */
    public static function normalizeEndpoints(array $endpoints): array
    {
        return \array_map(function (string $endpoint) {
            return self::normalizeEndpoint($endpoint);
        }, $endpoints);
    }

    /**
     * @param string $endpoint
     *
     * @return string
     */
    public static function normalizeEndpoint(string $endpoint): string
    {
        return 0 === \strpos($endpoint, 'apisearch_')
            ? $endpoint
            : "apisearch_$endpoint";
    }
}
