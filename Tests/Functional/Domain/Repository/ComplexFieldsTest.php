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
use Apisearch\Query\Filter;
use Apisearch\Query\Query;

/**
 * trait ComplexDataStructuresTest.
 */
trait ComplexFieldsTest
{
    /**
     * Test token queries.
     */
    public function testComplexDataStructures()
    {
        $item = Item::create(
            ItemUUID::createByComposedUUID('6~item'),
            [],
            [
                'complex_field' => [
                    ['id' => 1, 'name' => 'name1'],
                    ['id' => 2, 'name' => 'name2', 'slug' => 'slug2'],
                    ['id' => 3, 'name' => 'name3', 'level' => 2],
                ],
                'complex_field_2' => [
                    'id' => 'A',
                    'name' => 'lol',
                    'slug' => '/lol',
                ],
            ]
        );

        $this->indexItems([$item]);

        $result = $this->query(Query::createMatchAll()->filterBy('complex_field', 'complex_field', [1], Filter::MUST_ALL, true));
        $this->assertCount(1, $result->getItems());
        $firstItem = $result->getFirstItem();
        $this->assertFalse(\array_key_exists('complex_field_id', $firstItem->getIndexedMetadata()));
        $this->assertFalse(\array_key_exists('complex_field_data', $firstItem->getIndexedMetadata()));
        $this->assertCount(3, $result->getAggregations()->getAggregation('complex_field')->getCounters());

        $result = $this->query(Query::createMatchAll()
            ->filterBy('complex_field_2', 'complex_field_2', ['A'], Filter::MUST_ALL, true)
            ->setFields(['indexed_metadata.complex_field_2'])
        );

        $firstItem = $result->getFirstItem();

        $this->assertCount(1, $result->getItems());
        $this->assertCount(1, $result->getAggregations());
        $complexField2Aggregation = $result->getAggregations()->getAggregation('complex_field_2');
        $complexField2AggregationACounter = $complexField2Aggregation->getCounters()['A'];
        $this->assertEquals([
            'id' => 'A',
            'name' => 'lol',
            'slug' => '/lol',
        ], $complexField2AggregationACounter->getValues());
        $this->assertTrue($complexField2AggregationACounter->isUsed());
        $this->assertEquals(1, $complexField2AggregationACounter->getN());

        $this->assertFalse(\array_key_exists('complex_field_2', $firstItem->getMetadata()));
        $this->assertEquals('/lol', $firstItem->getIndexedMetadata()['complex_field_2']['slug']);

        $result = $this->query(Query::createMatchAll()
            ->filterBy('complex_field_2', 'complex_field_2', ['A'], Filter::MUST_ALL, true)
            ->setFields(['!indexed_metadata.complex_field_2']));

        $firstItem = $result->getFirstItem();
        $this->assertFalse(\array_key_exists('complex_field_2', $firstItem->getMetadata()));
        $this->assertFalse(\array_key_exists('complex_field_2', $firstItem->getIndexedMetadata()));

        $index = $this->getPrincipalIndex('indexed_metadata.complex_field_2');
        $fields = $index->getFields();
        $this->assertTrue(\array_key_exists('indexed_metadata.complex_field_2', $fields));
        $this->assertFalse(\array_key_exists('indexed_metadata.complex_field_2_id', $fields));
        $this->assertFalse(\array_key_exists('indexed_metadata.complex_field_2_data', $fields));
        $this->assertFalse(\array_key_exists('metadata.complex_field_2', $fields));

        /**
         * Requiring all metadata.
         */
        $result = $this->query(Query::createMatchAll()
            ->filterBy('complex_field_2', 'complex_field_2', ['A'], Filter::MUST_ALL, true)
            ->setFields(['metadata.*', 'indexed_metadata.complex_field_2'])
        );

        $firstItem = $result->getFirstItem();
        $this->assertFalse(\array_key_exists('complex_field_2_id', $firstItem->getIndexedMetadata()));
        $this->assertFalse(\array_key_exists('complex_field_2_data', $firstItem->getIndexedMetadata()));
        $this->assertFalse(\array_key_exists('complex_field_2', $firstItem->getMetadata()));
        $this->assertTrue(\array_key_exists('complex_field_2', $firstItem->getIndexedMetadata()));

        static::resetScenario();
    }
}
