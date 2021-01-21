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

namespace Apisearch\Plugin\Elasticsearch\Domain\Polyfill;

use Elastica\ResultSet as ElasticaResultSet;

/**
 * Class ResultSet.
 */
class ResultSet
{
    public static function getTotalHits(ElasticaResultSet $resultSet): int
    {
        $data = $resultSet
            ->getResponse()
            ->getData();

        if (!isset($data['hits'])) {
            return 0;
        }

        $totalHits = $data['hits']['total'];

        return \intval(\is_array($totalHits)
            ? $totalHits['value']
            : $totalHits);
    }
}
