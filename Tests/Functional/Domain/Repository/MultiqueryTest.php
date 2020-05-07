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

use Apisearch\Model\IndexUUID;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;
use Apisearch\Result\Result;

/**
 * Trait MultiqueryTest.
 */
trait MultiqueryTest
{
    /**
     * Test simple multiquery.
     */
    public function testSimpleMultiQuery()
    {
        $query = Query::createMultiquery([
            'q1' => Query::create('alfaguarra')->identifyWith('123'),
            'q2' => Query::create('boosting')->identifyWith('456'),
        ]);

        /**
         * @var Result
         */
        $result = $this->query($query);
        $subresults = $result->getSubresults();
        $this->assertCount(2, $subresults);
        $this->assertEquals('123', $subresults['q1']->getQueryUUID());
        $this->assertEquals(1, $subresults['q1']->getTotalHits());
        $this->assertEquals('456', $subresults['q2']->getQueryUUID());
        $this->assertEquals(3, $subresults['q2']->getTotalHits());
    }

    /**
     * Test query on multiple indices.
     */
    public function testMultiQueryOnMultipleIndices()
    {
        static::safeDeleteIndex(self::$appId, self::$anotherIndex);
        static::safeDeleteIndex(self::$appId, self::$yetAnotherIndex);

        $this->createIndex(self::$appId, self::$anotherIndex);
        $this->createIndex(self::$appId, self::$yetAnotherIndex);
        $this->indexItems([Item::create(ItemUUID::createByComposedUUID('123~type2'), [], [], ['field1' => 'Engonga'])], self::$appId, self::$anotherIndex);
        $this->indexItems([Item::create(ItemUUID::createByComposedUUID('123~type10'), [], [], ['field1' => 'Engonga troloro'])], self::$appId, self::$yetAnotherIndex);
        $result = $this->query(Query::createMultiquery([
            'q1' => Query::createMatchAll(),
            'q2' => Query::create('Engonga'),
        ]), self::$appId, self::$index.','.self::$anotherIndex);

        $resultQ1 = $result->getSubresults()['q1'];
        $this->assertCount(6, $resultQ1->getItems());
        $resultQ2 = $result->getSubresults()['q2'];
        $this->assertCount(2, $resultQ2->getItems());

        //$this->indexItems([Item::create(ItemUUID::createByComposedUUID('123~type2'), [], [], ['field1' => 'Engonga'])], self::$appId, self::$anotherIndex);
        $result = $this->query(Query::createMultiquery([
            'q1' => Query::createMatchAll(),
            'q2' => Query::create('Engonga'),
            'q3' => Query::create('troloro'),
            'q4' => Query::createByUUID(ItemUUID::createByComposedUUID('123~type2')),
        ]), self::$appId, '*');

        $resultQ1 = $result->getSubresults()['q1'];
        $this->assertCount(7, $resultQ1->getItems());
        $this->assertNotEmpty($resultQ1->getQueryUUID());
        $resultQ2 = $result->getSubresults()['q2'];
        $this->assertCount(3, $resultQ2->getItems());
        $this->assertNotEmpty($resultQ2->getQueryUUID());
        $resultQ3 = $result->getSubresults()['q3'];
        $this->assertCount(1, $resultQ3->getItems());
        $this->assertNotEmpty($resultQ3->getQueryUUID());
        $resultQ4 = $result->getSubresults()['q4'];
        $this->assertCount(1, $resultQ4->getItems());
        $this->assertNotEmpty($resultQ4->getQueryUUID());

        /*
         * Test multiquery with forced indices each
         */
        $result = $this->query(Query::createMultiquery([
            'q1' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(static::$index)),
            'q2' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(static::$anotherIndex)),
            'q3' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(static::$index.','.static::$anotherIndex)),
            'q4' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(static::$yetAnotherIndex.','.static::$anotherIndex)),
        ]), self::$appId, '*');

        $resultQ1 = $result->getSubresults()['q1'];
        $this->assertCount(5, $resultQ1->getItems());
        $resultQ2 = $result->getSubresults()['q2'];
        $this->assertCount(1, $resultQ2->getItems());
        $resultQ3 = $result->getSubresults()['q3'];
        $this->assertCount(6, $resultQ3->getItems());
        $resultQ4 = $result->getSubresults()['q4'];
        $this->assertCount(2, $resultQ4->getItems());

        $this->deleteIndex(self::$appId, self::$anotherIndex);
        $this->deleteIndex(self::$appId, self::$yetAnotherIndex);

        static::resetScenario();
    }
}
