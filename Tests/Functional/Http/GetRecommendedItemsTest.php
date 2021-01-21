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

namespace Apisearch\Server\Tests\Functional\Http;

use Apisearch\Query\Query;
use Apisearch\Result\Result;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class GetRecommendedItemsTest.
 */
class GetRecommendedItemsTest extends HttpFunctionalTest
{
    public function testEmptyEndpoint(): void
    {
        $response = $this->request('v1_get_recommended_items', [
            'app_id' => static::$appId,
            'index_id' => static::$index,
        ]);

        $result = Result::createFromArray($response['body']);
        $this->assertCount(5, $result->getItems());
    }

    public function testEndpointWithQuery(): void
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
