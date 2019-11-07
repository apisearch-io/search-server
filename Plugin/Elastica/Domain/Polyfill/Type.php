<?php


namespace Apisearch\Plugin\Elastica\Domain\Polyfill;

use Elasticsearch\Endpoints\AbstractEndpoint;

/**
 * Class Type
 */
class Type
{
    /**
     * Set type to endpoint
     *
     * @param AbstractEndpoint $endpoint
     * @param string $version
     */
    static function setEndpointType(
        AbstractEndpoint $endpoint,
        string $version
    )
    {
        if ($version == '6') {
            $endpoint->setType('item');
        }
    }
}