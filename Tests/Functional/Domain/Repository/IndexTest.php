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

namespace Apisearch\Server\Tests\Functional\Domain\Repository;

use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;

/**
 * Class IndexTest.
 */
trait IndexTest
{
    /**
     * Test some index scenarios.
     */
    public function testIndexItemWithWrongSearchableValues()
    {
        $itemUUID = ItemUUID::createByComposedUUID('6~product');
        $item = Item::create(
                $itemUUID,
                [],
                [],
                [
                    'engonga' => [
                        '0',
                        '',
                        'engonga',
                    ],
                ],
                [
                    '0',
                    '',
                    'engonga',
                ]
            );
        $item = Item::createFromArray($item->toArray());
        $this->indexItems([$item]);

        $item = $this->query(
            Query::createByUUID($itemUUID)
        )->getFirstItem();

        $this->assertEquals(
            ['engonga'],
            $item->getSearchableMetadata()['engonga']
        );

        $this->assertEquals(
            ['engonga'],
            $item->getExactMatchingMetadata()
        );

        $this->resetScenario();
    }

    /**
     * Test some index scenarios.
     */
    public function testIndexEmptyArray()
    {
        $this->indexItems([]);

        $this->assertEquals(5, $this->query(Query::createMatchAll())->getTotalItems());
    }
}
