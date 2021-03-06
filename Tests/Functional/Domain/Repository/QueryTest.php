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
     *
     * @return void
     */
    public function testMatchAll(): void
    {
        $this->assertCount(5,
            $this
                ->query(Query::createMatchAll())
                ->getItems()
        );
    }

    /**
     * Test basic search.
     *
     * @return void
     */
    public function testBasicSearch(): void
    {
        $result = $this->query(Query::create('badal'));
        $this->assertNTypeElementId($result, 0, '5');
    }

    /**
     * Test basic search.
     *
     * @return void
     */
    public function testBasicSearchUsingSearchToken(): void
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
     *
     * @return void
     */
    public function testAllResults(): void
    {
        $results = $this
            ->query(Query::create('barcelona'))
            ->getItems();

        $this->assertCount(1, $results);
        $this->assertInstanceof(Item::class, $results[0]);
    }

    /**
     * Test search by reference.
     *
     * @return void
     */
    public function testSearchByReference(): void
    {
        $result = $this->query(Query::createByUUID(new ItemUUID('4', 'bike')));
        $this->assertCount(1, $result->getItems());
        $this->assertSame('4', $result->getItems()[0]->getUUID()->getId());
        $this->assertSame('bike', $result->getItems()[0]->getUUID()->getType());
    }

    /**
     * Test search by references.
     *
     * @return void
     */
    public function testSearchByReferences(): void
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
     *
     * @return void
     */
    public function testAccents(): void
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
     *
     * @return void
     */
    public function testSpecificCases(): void
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
     *
     * @return void
     */
    public function testSplitWords(): void
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
     *
     * @return void
     */
    public function testUselessValuesOnIndex(): void
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
     *
     * @return void
     */
    public function testMinScore(): void
    {
        $this->assertCount(
            5,
            $this->query(Query::createMatchAll()->setMinScore(Query::NO_MIN_SCORE))->getItems()
        );

        $this->assertCount(
            5,
            $this->query(Query::createMatchAll()->setMinScore(1.0))->getItems()
        );

        /*
         * Min score should only apply when has an active search
         */
        $this->assertCount(
            5,
            $this->query(Query::createMatchAll()->setMinScore(2.0))->getItems()
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
     *
     * @return void
     */
    public function testSearchByStrangeCharacter(): void
    {
        $this->assertCount(
            1,
            $this->query(Query::create('煮'))->getItems()
        );
    }

    /**
     * Test select some fields.
     *
     * @return void
     */
    public function testSelectOnlyDesiredFields(): void
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
     *
     * @return void
     */
    public function testRepositoryReference(): void
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
     *
     * @return void
     */
    public function testQueryOnMultipleIndices(): void
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

    /**
     * We test that, when applying some language in an index, we apply stopwords while indexing (analyzing) but
     * we don't do that when searching.
     *
     * - Item 1 indexes both "Algas" and "a", but because "a" is a stopword in Spanish, only Algas is really indexed
     * - Item 2 indexes "a", but as is a stopwork, don't really indexes nothing
     *
     * - When searching for "a", we get the first item, because "Algas", after tokanization, we have an A.
     *
     * @return void
     */
    public function testQueryWithLanguage(): void
    {
        $this->deleteIndex();
        $this->createIndex(self::$appId, self::$index, self::getGodToken(), new Config('es'));
        $this->indexItems([
            Item::create(ItemUUID::createByComposedUUID('123~type1'), [], [], ['description' => 'Algas a la fuerza']),
            Item::create(ItemUUID::createByComposedUUID('123~type2'), [], [], ['description' => 'a']),
        ]);

        $result = $this->query(Query::create('a'));
        $this->assertCount(1, $result->getItems());
        $this->assertEquals('type1', $result->getFirstItem()->getType());
    }
}
