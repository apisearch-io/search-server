<?php


namespace Apisearch\Server\Tests\Functional\Http;

use Apisearch\Query\Query;
use Apisearch\Result\Result;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class GetRecommendedItemsTest
 */
class GetRecommendedItemsTest extends HttpFunctionalTest
{
    public function testEmptyEndpoint()
    {
        $response = $this->request('v1_get_recommended_items', [
            'app_id' => static::$appId,
            'index_id' => static::$index,
        ]);

        $result = Result::createFromArray($response['body']);
        $this->assertCount(5, $result->getItems());
    }

    public function testEndpointWithQuery()
    {
        $query = Query::create('', 1, 2);
        $response = $this->request('v1_get_recommended_items', [
            'app_id' => static::$appId,
            'index_id' => static::$index,
        ], null, $query->toArray());

        $result = Result::createFromArray($response['body']);
        $this->assertCount(2, $result->getItems());
    }
}