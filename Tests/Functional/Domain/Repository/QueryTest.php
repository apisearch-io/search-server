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

use Apisearch\Config\Config;
use Apisearch\Config\Synonym;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;

/**
 * Class QueryTest.
 */
trait QueryTest
{
    /**
     * Test get match all.
     */
    public function testMatchAll()
    {
        $this->assertCount(5,
            $this
                ->query(Query::createMatchAll())
                ->getItems()
        );
    }

    /**
     * Test basic search.
     */
    public function testBasicSearch()
    {
        $result = $this->query(Query::create('badal'));
        $this->assertNTypeElementId($result, 0, '5');
    }

    /**
     * Test basic search.
     */
    public function testBasicSearchUsingSearchToken()
    {
        $this->assertCount(
            5,
            $this
                ->query(
                    Query::createMatchAll(),
                    null,
                    null,
                    $this->createTokenByIdAndAppId(self::$readonlyToken, self::$appId)
                )
                ->getItems()
        );
    }

    /**
     * Test basic search with all results method call.
     */
    public function testAllResults()
    {
        $results = $this
            ->query(Query::create('barcelona'))
            ->getItems();

        $this->assertCount(1, $results);
        $this->assertInstanceof(Item::class, $results[0]);
    }

    /**
     * Test search by reference.
     */
    public function testSearchByReference()
    {
        $result = $this->query(Query::createByUUID(new ItemUUID('4', 'bike')));
        $this->assertCount(1, $result->getItems());
        $this->assertSame('4', $result->getItems()[0]->getUUID()->getId());
        $this->assertSame('bike', $result->getItems()[0]->getUUID()->getType());
    }

    /**
     * Test search by references.
     */
    public function testSearchByReferences()
    {
        $result = $this->query(Query::createByUUIDs([
            new ItemUUID('5', 'gum'),
            new ItemUUID('3', 'book'),
        ]));
        $this->assertCount(2, $result->getItems());
        $this->assertSame('3', $result->getItems()[0]->getUUID()->getId());
        $this->assertSame('5', $result->getItems()[1]->getUUID()->getId());

        $result = $this->query(Query::createByUUIDs([
            new ItemUUID('5', 'gum'),
            new ItemUUID('5', 'gum'),
        ]));
        $this->assertCount(1, $result->getItems());
        $this->assertSame('5', $result->getItems()[0]->getUUID()->getId());
    }

    /**
     * Test accents.
     */
    public function testAccents()
    {
        $this->assertEquals(
            3,
            $this
                ->query(Query::create('codigo'))
                ->getFirstItem()
                ->getId()
        );

        $this->assertEquals(
            3,
            $this
                ->query(Query::create('código'))
                ->getFirstItem()
                ->getId()
        );
    }

    /**
     * Test specific cases.
     */
    public function testSpecificCases()
    {
        $this->assertEquals(
            '3~book',
            $this
                ->query(Query::create('Da Vinci'))
                ->getFirstItem()
                ->getUuid()
                ->composeUUID()
        );

        $this->assertEquals(
            '3~book',
            $this
                ->query(Query::create('code Da Vinci'))
                ->getFirstItem()
                ->getUuid()
                ->composeUUID()
        );
    }

    /**
     * Test split words.
     */
    public function testSplitWords()
    {
        $this->assertEquals(
            '2~product',
            $this
                ->query(Query::create('Style step'))
                ->getFirstItem()
                ->getUuid()
                ->composeUUID()
        );

        $this->configureIndex(Config::createEmpty()->addSynonym(Synonym::createByWords(['Stylestep', 'Style step'])));
        $this->assertEquals(
            '1~product',
            $this
                ->query(Query::create('Style step'))
                ->getFirstItem()
                ->getUuid()
                ->composeUUID()
        );
    }

    /**
     * Test false values.
     */
    public function testUselessValuesOnIndex()
    {
        $this->indexItems([
            Item::create(
                ItemUUID::createByComposedUUID('999~default'),
                [
                    'value' => 'value',
                    'null' => null,
                    'false' => false,
                    'true' => true,
                    'empty_array' => [],
                    'array_null' => [
                        null,
                    ],
                    'array' => [
                        [
                            'null' => null,
                            'false' => false,
                            'true' => true,
                            'empty_array' => [],
                            'array_null' => [
                                null,
                            ],
                            'value' => 'value',
                        ],
                    ],
                ],
                [
                    'value' => 'value',
                    'null' => null,
                    'false' => false,
                    'true' => true,
                    'empty_array' => [],
                    'array_null' => [
                        null,
                    ],
                    'array' => [
                        [
                            'null' => null,
                            'false' => false,
                            'true' => true,
                            'empty_array' => [],
                            'array_null' => [
                                null,
                            ],
                            'value' => 'value',
                        ],
                    ],
                ],
                [
                    'value' => 'value',
                    'null' => null,
                    'false' => false,
                    'true' => true,
                    'empty_array' => [],
                    'array_null' => [
                        null,
                    ],
                    'empty_value' => '',
                    'array' => [
                        false,
                        true,
                    ],
                ],
                [
                    'value',
                    '',
                    true,
                    false,
                    null,
                ],
                [
                    'value',
                    '',
                    true,
                    false,
                    null,
                ]
            ),
        ]);

        $item = $this
            ->query(Query::createByUUID(ItemUUID::createByComposedUUID('999~default')))
            ->getFirstItem();

        $this->assertEquals(
            [
                'value' => 'value',
                'false' => false,
                'true' => true,
                'array' => [
                    [
                        'false' => false,
                        'true' => true,
                        'value' => 'value',
                    ],
                ],
            ],
            $item->getMetadata()
        );

        $this->assertEquals(
            [
                'value' => 'value',
                'false' => false,
                'true' => true,
                'array' => [
                    [
                        'false' => false,
                        'true' => true,
                        'value' => 'value',
                    ],
                ],
            ],
            $item->getIndexedMetadata()
        );

        $this->assertEquals(
            [
                'value' => 'value',
            ],
            $item->getSearchableMetadata()
        );

        $this->assertEquals(
            [
                'value',
            ],
            $item->getExactMatchingMetadata()
        );

        $this->assertEquals(
            [
                'value',
            ],
            $item->getSuggest()
        );

        self::resetScenario();
    }

    /**
     * Test min score.
     */
    public function testMinScore()
    {
        $this->assertCount(
            5,
            $this->query(Query::createMatchAll()->setMinScore(Query::NO_MIN_SCORE))->getItems()
        );

        $this->assertCount(
            5,
            $this->query(Query::createMatchAll()->setMinScore(1.0))->getItems()
        );

        $this->assertCount(
            4,
            $this->query(Query::create('a')->setMinScore(Query::NO_MIN_SCORE))->getItems()
        );

        $this->assertCount(
            3,
            $this->query(Query::create('a')->setMinScore(1.0))->getItems()
        );

        $this->assertCount(
            0,
            $this->query(Query::create('a')->setMinScore(2.0))->getItems()
        );
    }

    /**
     * Search by strange character.
     */
    public function testSearchByStrangeCharacter()
    {
        $this->assertCount(
            1,
            $this->query(Query::create('煮'))->getItems()
        );
    }

    /**
     * Test select some fields.
     */
    public function testSelectOnlyDesiredFields()
    {
        $query = Query::createMatchAll()
            ->setFields([
                'metadata.*',
            ]);

        $items = $this->query($query)->getItems();
        $this->assertCount(5, $items);
        $firstItem = $items[0];
        $this->assertEquals('1', $firstItem->getUUID()->getId());
        $this->assertNotEmpty($firstItem->getMetadata());
        $this->assertEmpty($firstItem->getIndexedMetadata());
        $this->assertEmpty($firstItem->getSearchableMetadata());

        $query = Query::createMatchAll()
            ->setFields([
                'metadata.*',
                'indexed_metadata.field_integer',
            ]);

        $items = $this->query($query)->getItems();
        $this->assertCount(5, $items);
        $firstItem = $items[0];
        $this->assertEquals('1', $firstItem->getUUID()->getId());
        $this->assertNotEmpty($firstItem->getMetadata());
        $this->assertNotEmpty($firstItem->getIndexedMetadata());
        $this->assertEquals(10, $firstItem->get('field_integer'));
        $this->assertNull($firstItem->get('field_boolean'));
        $this->assertEmpty($firstItem->getSearchableMetadata());

        $query = Query::createMatchAll()
            ->setFields([
                '*',
            ]);

        $items = $this->query($query)->getItems();
        $this->assertCount(5, $items);
        $firstItem = $items[0];
        $this->assertNotEmpty($firstItem->getMetadata());
        $this->assertNotEmpty($firstItem->getIndexedMetadata());
        $this->assertEquals(10, $firstItem->get('field_integer'));
        $this->assertTrue($firstItem->get('field_boolean'));
        $this->assertNotEmpty($firstItem->getSearchableMetadata());

        $query = Query::createMatchAll()
            ->setFields([]);

        $items = $this->query($query)->getItems();
        $this->assertCount(5, $items);
        $firstItem = $items[0];
        $this->assertNotEmpty($firstItem->getMetadata());
        $this->assertNotEmpty($firstItem->getIndexedMetadata());
        $this->assertNotEmpty($firstItem->getSearchableMetadata());

        $query = Query::createMatchAll()
            ->setFields([
                'metadata.*',
                'indexed_metadata.*',
                'searchable_metadata.*',
            ]);

        $items = $this->query($query)->getItems();
        $this->assertCount(5, $items);
        $firstItem = $items[0];
        $this->assertNotEmpty($firstItem->getMetadata());
        $this->assertNotEmpty($firstItem->getIndexedMetadata());
        $this->assertNotEmpty($firstItem->getSearchableMetadata());
    }

    /**
     * Test repository reference.
     */
    public function testRepositoryReference()
    {
        $item = $this->query(Query::createMatchAll())->getFirstItem();
        $this->assertEquals(
            static::$appId,
            $item->getAppUUID()->composeUUID()
        );
        $this->assertEquals(
            static::$index,
            $item->getIndexUUID()->composeUUID()
        );

        $item = $this->query(Query::createMatchAll(), self::$appId, '*')->getFirstItem();
        $this->assertEquals(
            static::$appId,
            $item->getAppUUID()->composeUUID()
        );
        $this->assertEquals(
            static::$index,
            $item->getIndexUUID()->composeUUID()
        );
    }

    /**
     * Test query on multiple indices.
     */
    public function testQueryOnMultipleIndices()
    {
        try {
            $this->deleteIndex(self::$appId, self::$anotherIndex);
        } catch (ResourceNotAvailableException $exception) {
            // Silent pass
        }

        $this->createIndex(self::$appId, self::$anotherIndex);
        $this->indexItems([Item::create(ItemUUID::createByComposedUUID('123~type2'), [], [], ['field1' => 'Engonga'])], self::$appId, self::$anotherIndex);
        $result = $this->query(Query::createMatchAll(), self::$appId, self::$index.','.self::$anotherIndex);
        $this->assertCount(6, $result->getItems());

        $result = $this->query(Query::createMatchAll(), self::$appId, '*');
        $this->assertCount(6, $result->getItems());

        $result = $this->query(Query::create('Engonga'), self::$appId, self::$anotherIndex);
        $this->assertCount(1, $result->getItems());
        $result = $this->query(Query::create('Engonga'), self::$appId, self::$index.','.self::$anotherIndex);
        $this->assertCount(2, $result->getItems());
        $this->deleteIndex(self::$appId, self::$anotherIndex);
    }
}
