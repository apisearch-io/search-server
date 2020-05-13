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

use Apisearch\Model\Changes;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;

/**
 * Class UpdateItemsTest.
 */
trait UpdateItemsTest
{
    /**
     * Test basic update.
     */
    public function testUpdateBasicFields()
    {
        $this->updateItems(
            Query::createMatchAll(),
            Changes::create()
                ->addChange('indexed_metadata.new_field_int', 10, Changes::TYPE_VALUE)
                ->addChange('indexed_metadata.new_field_string', 'holi', Changes::TYPE_VALUE)
                ->addChange('indexed_metadata.new_field_array', ['one', 'two', 'three'], Changes::TYPE_VALUE)
        );

        $this->assertCount(
            5,
            $this->query(Query::createMatchAll()->filterBy(
                'new_field_int',
                'new_field_int',
                [10]
            ))->getItems()
        );

        $this->assertCount(
            5,
            $this->query(Query::createMatchAll()->filterBy(
                'new_field_string',
                'new_field_string',
                ['holi']
            ))->getItems()
        );

        $this->assertCount(
            5,
            $this->query(Query::createMatchAll()->filterBy(
                'new_field_array',
                'new_field_array',
                ['one']
            ))->getItems()
        );

        $this->updateItems(
            Query::createMatchAll(),
            Changes::create()
                ->addChange('indexed_metadata.new_field_int', 'indexed_metadata.new_field_int+1', Changes::TYPE_LITERAL)
        );

        $this->assertCount(
            5,
            $this->query(Query::createMatchAll()->filterBy(
                'new_field_int',
                'new_field_int',
                [11]
            ))->getItems()
        );

        $this->updateItems(
            Query::createByUUID(ItemUUID::createByComposedUUID('1~product')),
            Changes::create()
                ->addChange('indexed_metadata.new_field_int', 'indexed_metadata.new_field_int+10', Changes::TYPE_LITERAL)
                ->addChange('indexed_metadata.even_another_field', '(indexed_metadata.new_field_int+indexed_metadata.new_field_int)*2', Changes::TYPE_LITERAL)
        );

        $this->assertCount(
            1,
            $this->query(Query::createMatchAll()
                ->filterBy(
                    'new_field_int',
                    'new_field_int',
                    [21]
                )
                ->filterBy(
                    'even_another_field',
                    'even_another_field',
                    [84]
                )
            )->getItems()
        );

        /*
         * Reseting scenario for next calls.
         */
        self::resetScenario();
    }

    /**
     * Test array updates.
     */
    public function testArrayUpdates()
    {
        $this->updateItems(
            Query::createByUUID(ItemUUID::createByComposedUUID('4~bike')),
            Changes::create()
                ->updateElementFromList(
                    'indexed_metadata.category',
                    'element.id == 6',
                    160,
                    Changes::TYPE_VALUE
                )
                ->updateElementFromList(
                    'indexed_metadata.category',
                    'element == 5',
                    'element*100',
                    Changes::TYPE_LITERAL
                )
        );

        $this->assertCount(
            1,
            $this->query(Query::createMatchAll()->filterBy('category', 'category', [160]))->getItems()
        );

        $this->assertCount(
            1,
            $this->query(Query::createMatchAll()->filterBy('category', 'category', [500]))->getItems()
        );

        $this->updateItems(
            Query::createByUUID(ItemUUID::createByComposedUUID('4~bike')),
            Changes::create()
                ->addElementInList(
                    'indexed_metadata.category',
                    7788,
                    Changes::TYPE_VALUE
                )
        );

        $this->assertCount(
            1,
            $this->query(Query::createMatchAll()->filterBy('category', 'category', [7788]))->getItems()
        );

        $this->updateItems(
            Query::createByUUID(ItemUUID::createByComposedUUID('4~bike')),
            Changes::create()
                ->deleteElementFromList(
                    'indexed_metadata.category',
                    'element == 7788'
                )
        );

        $this->assertCount(
            0,
            $this->query(Query::createMatchAll()->filterBy('category', 'category', [7788]))->getItems()
        );

        $this->assertCount(
            2,
            $this->query(Query::createMatchAll()->filterBy('array_of_arrays', 'array_of_arrays_ids', [3]))->getItems()
        );

        $this->updateItems(
            Query::createMatchAll(),
            Changes::create()
                ->updateElementFromList(
                    'metadata.array_of_arrays',
                    'element.id == 3',
                    [
                        'id' => 10,
                        'name' => 'number_10',
                    ],
                    Changes::TYPE_VALUE
                )
                ->updateElementFromList(
                    'indexed_metadata.array_of_arrays_ids',
                    'element == "3"',
                    '10',
                    Changes::TYPE_VALUE
                )
        );

        $this->assertCount(
            0,
            $this->query(Query::createMatchAll()->filterBy('array_of_arrays', 'array_of_arrays_ids', [3]))->getItems()
        );

        $this->assertCount(
            2,
            $this->query(Query::createMatchAll()->filterBy('array_of_arrays', 'array_of_arrays_ids', [10]))->getItems()
        );

        $arrayOfArrays = $this
            ->query(Query::createByUUID(ItemUUID::createByComposedUUID('2~product')))
            ->getFirstItem()
            ->getMetadata()['array_of_arrays'];

        $this->assertCount(
            2,
            $arrayOfArrays
        );

        $this->assertEquals(
            [
                'id' => 10,
                'name' => 'number_10',
            ],
            $arrayOfArrays[1]
        );

        /*
         * Reseting scenario for next calls.
         */
        self::resetScenario();
    }
}
