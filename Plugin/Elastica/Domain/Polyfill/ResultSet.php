<?php


namespace Apisearch\Plugin\Elastica\Domain\Polyfill;

use Elastica\ResultSet as ElasticaResultSet;

/**
 * Class ResultSet
 */
class ResultSet
{
    static function getTotalHits(ElasticaResultSet $resultSet)
    {
        $totalHits = $resultSet->getResponse()->getData()['hits']['total'];

        return \intval(is_array($totalHits)
            ? $totalHits['value']
            : $totalHits);
    }
}