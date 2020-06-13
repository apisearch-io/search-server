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

namespace Apisearch\Plugin\Elasticsearch\Domain\Parser;

/**
 * Class IndexParser
 */
class IndexParser
{
    /**
     * @param string $indexName
     *
     * @return array|null
     */
    public static function parseIndexName(string $indexName) : ?array
    {
        $regexToParse = "~apisearch_.+?_item_(?P<app_uuid>.+?)_(?P<index_uuid>.+)~";
        \preg_match($regexToParse, $indexName, $match);
        if (
            !array_key_exists('app_uuid', $match) ||
            !array_key_exists('index_uuid', $match)
        ) {
            return null;
        }

        return [
            'app_uuid' => $match['app_uuid'],
            'index_uuid' => $match['index_uuid'],
        ];
    }
}