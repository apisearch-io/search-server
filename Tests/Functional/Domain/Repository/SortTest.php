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

use Apisearch\Model\Coordinate;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;
use Apisearch\Query\SortBy;

/**
 * Class SortTest.
 */
trait SortTest
{
    /**
     * Test sort by indexable metadata integer asc.
     */
    public function testSortByIndexableMetadataIntegerAsc()
    {
        $result = $this->query(Query::createMatchAll()->sortBy(SortBy::byFieldsValues(['simple_int' => 'asc'])));
        $this->assertResults(
            $result,
            ['5', '3', '2', '1', '4']
        );
    }

    /**
     * Test sort by indexable metadata integer desc.
     */
    public function testSortByIndexableMetadataIntegerDesc()
    {
        $result = $this->query(Query::createMatchAll()->sortBy(SortBy::byFieldsValues(['simple_int' => 'desc'])));
        $this->assertResults(
            $result,
            ['4', '1', '2', '3', '5']
        );
    }

    /**
     * Test sort by indexable metadata string asc.
     */
    public function testSortByIndexableMetadataStringAsc()
    {
        $result = $this->query(Query::createMatchAll()->sortBy(SortBy::byFieldsValues(['simple_string' => 'asc'])));
        $this->assertResults(
            $result,
            ['5', '2', '3', '4', '1']
        );
    }

    /**
     * Test sort by indexable metadata string desc.
     */
    public function testSortByIndexableMetadataStringDesc()
    {
        $result = $this->query(Query::createMatchAll()->sortBy(SortBy::byFieldsValues(['simple_string' => 'desc'])));
        $this->assertResults(
            $result,
            ['1', '4', '3', '2', '5']
        );
    }

    /**
     * Test sort by location.
     */
    public function testSortByLocationKmAsc()
    {
        $result = $this->query(Query::createLocated(new Coordinate(45.0, 45.0), '')->sortBy(SortBy::create()->byValue(SortBy::LOCATION_KM_ASC)));
        $this->assertResults(
            $result,
            ['3', '4', '2', '1', '5']
        );

        $items = $result->getItems();
        $this->assertTrue($items[0]->getDistance() < 558);
        $this->assertTrue($items[0]->getDistance() > 554);
    }

    /**
     * Test sort by location.
     */
    public function testSortByLocationKmDesc()
    {
        $result = $this->query(Query::createLocated(new Coordinate(45.0, 45.0), '')->sortBy(SortBy::create()->byValue(SortBy::LOCATION_MI_ASC)));
        $this->assertResults(
            $result,
            ['3', '4', '2', '1', '5']
        );

        $items = $result->getItems();
        $this->assertTrue($items[0]->getDistance() < 346);
        $this->assertTrue($items[0]->getDistance() > 344);
    }

    /**
     * Test random sort.
     */
    public function testRandomSort()
    {
        $this->expectNotToPerformAssertions();
        $iterations = 10;
        $id = $this->generateFirstResultRandomSort();
        $sameIdTimes = 0;
        for ($i = 0; $i < $iterations; ++$i) {
            if ($id === $this->generateFirstResultRandomSort()) {
                ++$sameIdTimes;
            }
        }

        if ($sameIdTimes === $iterations) {
            $this->fail('Random sort is not working...');
        }
    }

    /**
     * Return first item from random search.
     */
    private function generateFirstResultRandomSort()
    {
        return $this->query(Query::createMatchAll()->sortBy(SortBy::create()->byValue(SortBy::RANDOM)))->getFirstItem()->getId();
    }

    /**
     * Test by nested field and filter.
     */
    public function testNestedFieldAndFilter()
    {
        $this->markTestIncomplete('Should be tested deeper with complex fields');
        $result = $this->query(Query::createMatchAll()
            ->sortBy(SortBy::create()->byNestedField('brand.rank', SortBy::ASC, SortBy::MODE_MIN))
        );
        $this->assertResults(
            $result,
            ['5', '2', '1', '3', '4']
        );

        $result = $this->query(Query::createMatchAll()
            ->sortBy(SortBy::create()
                ->byNestedFieldAndFilter(
                    'brand.rank',
                    SortBy::DESC,
                    Filter::create(
                        'brand.category',
                        [1],
                        Filter::MUST_ALL,
                        Filter::TYPE_FIELD
                    ),
                    SortBy::MODE_MAX
                )
            )
        );

        $this->assertResults(
            $result,
            ['4', '2', '1', '5', '3']
        );
    }

    /**
     * Test sort by function.
     */
    public function testSortByFunction()
    {
        $result = $this->query(Query::createMatchAll()
            ->sortBy(SortBy::create()->byFunction(
                'doc["indexed_metadata.simple_int"].value * doc["indexed_metadata.relevance"].value',
                SortBy::DESC
            ))
        );

        $this->assertResults(
            $result,
            ['4', '1', '5', '3', '2']
        );
    }
}
