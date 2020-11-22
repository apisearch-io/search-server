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

namespace Apisearch\Plugin\Elasticsearch\Tests\SpecificScenarios;

use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;
use Apisearch\Result\Result;

/**
 * Trait GetSimilarItems.
 */
trait GetSimilarItems
{
    public function testSimilarItemsSimple()
    {
        static::indexItems([
            Item::create(ItemUUID::createByComposedUUID('10~element'), [], ['data1' => 'dataA', 'data2' => 'dataA', 'data3' => 'dataA', 'data4' => 'dataA', 'data5' => 'dataA']),
            Item::create(ItemUUID::createByComposedUUID('11~element'), [], ['data1' => 'dataA', 'data2' => 'dataA', 'data3' => 'dataA11', 'data4' => 'dataA', 'data5' => 'dataA']),
            Item::create(ItemUUID::createByComposedUUID('12~element'), [], ['data1' => 'dataA', 'data2' => 'dataA2', 'data3' => 'dataA12', 'data4' => 'dataA112', 'data5' => 'dataA02']),
            Item::create(ItemUUID::createByComposedUUID('13~element'), [], ['data1' => 'dataA', 'data2' => 'dataA3', 'data3' => 'dataA13', 'data4' => 'dataA113', 'data5' => 'dataA03']),
            Item::create(ItemUUID::createByComposedUUID('14~element'), [], ['data1' => 'dataA', 'data2' => 'dataA4', 'data3' => 'dataA14', 'data4' => 'dataA114', 'data5' => 'dataA04']),
        ]);

        $response = static::request(
            'v1_get_similar',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            null, [
                'query' => Query::createMatchAll()->toArray(),
                'items_uuid' => [
                    ItemUUID::createByComposedUUID('10~element')->toArray(),
                ],
            ]
        );

        $result = Result::createFromArray($response['body']);
        $this->assertEquals(4, $result->getTotalHits());
    }
}
