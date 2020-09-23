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

namespace Apisearch\Server\Tests\Unit\Domain\Format;

use Apisearch\Model\Item;
use Apisearch\Server\Domain\Format\FormatTransformer;
use Apisearch\Server\Tests\Unit\BaseUnitTest;

/**
 * Class FormatTransformerTest.
 */
abstract class FormatTransformerTest extends BaseUnitTest
{
    /**
     * @return FormatTransformer
     */
    abstract protected function getFormatTransformer(): FormatTransformer;

    /**
     * Test data.
     *
     * @param Item   $item
     * @param array  $header
     * @param string $expectedLine
     * @param Item   $optimizedItem
     *
     * @dataProvider dataExportItem
     */
    public function testTransformItem(
        Item $item,
        array $header,
        string $expectedLine,
        Item $optimizedItem
    ) {
        $formatter = $this->getFormatTransformer();
        $line = $formatter->itemToLine($item);
        $this->assertSame($expectedLine, $line);
        $newItem = $formatter->lineToItem($header, $expectedLine);
        if ($this->optimizes()) {
            $this->assertEquals($optimizedItem, $newItem);
        }
        $this->assertSame($expectedLine, $formatter->itemToLine($newItem));
        $newItem = $formatter->lineToItem($header, $expectedLine);
        $this->assertSame($expectedLine, $formatter->itemToLine($newItem));
    }

    /**
     * @return array[]
     */
    public function dataExportItem()
    {
        return [
            [
                Item::createFromArray([
                    'uuid' => ['id' => '123', 'type' => 'test'],
                    'metadata' => [
                        'name' => 'n1',
                        'title' => 't1',
                        'description' => 'd1',
                        'extra_description' => 'ed1 && and & another | % %% chars',
                        'another_extra_description' => 'aed1',
                        'link' => 'l1',
                        'image' => '',
                        'brand' => 'b1',
                        's_field2' => 'sv2ã',
                        'structure1' => ['1', '2', '3'],
                        'ampersand' => 'One & Two && Three',
                        'chars' => 'One % Two %% | Three',
                    ],
                    'indexed_metadata' => [
                        'categories' => [
                            [
                                'id' => '1',
                                'name' => 'cat1',
                                'level' => '1',
                            ],
                            [
                                'id' => '2',
                                'level' => '2',
                            ],
                            [
                                'name' => 'cat2',
                                'level' => '2',
                            ],
                            [
                                'id' => 'invalid',
                            ],
                        ],
                        'alternative_categories' => [
                            [
                                'id' => '3',
                                'name' => 'cat3 && what',
                                'level' => '3',
                            ],
                            [
                                'id' => '4',
                                'level' => '4',
                            ],
                            [
                                'name' => 'cat4',
                                'level' => '4',
                            ],
                            [
                                'id' => 'invalid',
                            ],
                        ],
                        'reference' => 'rf1',
                        'alternative_reference' => 'arf1',
                        'price' => 10,
                        'reduced_price' => 8,
                        'reduced_price_percent' => 0,
                        'stock' => 100,
                        'on_offer' => true,
                        's_field1' => 'sv1',
                        's_field2' => 'sv2ã',
                        'an_array' => [
                            'val1',
                            'val2',
                        ],
                        'a_complex_array' => [
                            [
                                'val' => 1,
                                'price' => 2,
                                'char' => 'ã',
                            ],
                        ],
                        'a_multi_complex_array' => [
                            [
                                'val' => 1,
                                'price' => 2,
                            ],
                            [
                                'val' => 3,
                                'price' => 4,
                            ],
                        ],
                    ],
                    'searchable_metadata' => [
                        'name' => 'n1',
                        'title' => 't1',
                        'description' => 'd1',
                        'extra_description' => 'ed1 && and & another | % %% chars',
                        'another_extra_description' => 'aed1',
                        'brand' => 'b1',
                        's_field1' => 'sv1',
                        's_field2' => 'sv2ã',
                    ],
                    'exact_matching_metadata' => [
                        '123',
                        'rf1',
                        'arf1',
                        'sv2ã',
                    ],
                    'suggest' => [
                        'sug1',
                        'sug2',
                    ],
                ]),
                $this->getFormatTransformer()->headers(),
                $this->getLine(),
                Item::createFromArray([
                    'uuid' => ['id' => '123', 'type' => 'test'],
                    'metadata' => [
                        'name' => 'n1',
                        'title' => 't1',
                        'description' => 'd1',
                        'extra_description' => 'ed1 && and & another | % %% chars',
                        'another_extra_description' => 'aed1',
                        'link' => 'l1',
                        'image' => '',
                        'brand' => 'b1',
                        's_field2' => 'sv2ã',
                        'structure1' => ['1', '2', '3'],
                        'ampersand' => 'One & Two && Three',
                        'chars' => 'One % Two %% | Three',
                    ],
                    'indexed_metadata' => [
                        'categories' => [
                            [
                                'id' => '1',
                                'name' => 'cat1',
                                'level' => '1',
                            ],
                            [
                                'id' => '2',
                                'name' => '2',
                                'level' => '2',
                            ],
                        ],
                        'alternative_categories' => [
                            [
                                'id' => '3',
                                'name' => 'cat3 && what',
                                'level' => '3',
                            ],
                            [
                                'id' => '4',
                                'name' => '4',
                                'level' => '4',
                            ],
                        ],
                        'reference' => 'rf1',
                        'alternative_reference' => 'arf1',
                        'price' => 10,
                        'reduced_price' => 8,
                        'reduced_price_percent' => 0,
                        'stock' => 100,
                        'on_offer' => true,
                        's_field1' => 'sv1',
                        's_field2' => 'sv2ã',
                        'an_array' => [
                            'val1',
                            'val2',
                        ],
                        'a_complex_array' => [
                            [
                                'val' => 1,
                                'price' => 2,
                                'char' => 'ã',
                            ],
                        ],
                        'a_multi_complex_array' => [
                            [
                                'val' => 1,
                                'price' => 2,
                            ],
                            [
                                'val' => 3,
                                'price' => 4,
                            ],
                        ],
                    ],
                    'searchable_metadata' => [
                        'name' => 'n1',
                        'title' => 't1',
                        'description' => 'd1',
                        'extra_description' => 'ed1 && and & another | % %% chars',
                        'another_extra_description' => 'aed1',
                        'brand' => 'b1',
                        's_field1' => 'sv1',
                        's_field2' => 'sv2ã',
                    ],
                    'exact_matching_metadata' => [
                        '123',
                        'rf1',
                        'arf1',
                        'sv2ã',
                    ],
                    'suggest' => [
                        'sug1',
                        'sug2',
                    ],
                ]),
            ],
        ];
    }

    /**
     * Test data.
     *
     * @param Item   $item
     * @param array  $header
     * @param string $expectedLine
     *
     * @dataProvider dataAlternativeExportItem
     */
    public function testAlternativeTransformItem(
        Item $item,
        array $header,
        string $expectedLine
    ) {
        $formatter = $this->getFormatTransformer();
        $line = $formatter->itemToLine($item);
        $this->assertSame($expectedLine, $line);
        $newItem = $formatter->lineToItem($header, $expectedLine);
        $this->assertSame($expectedLine, $formatter->itemToLine($newItem));
        $newItem = $formatter->lineToItem($header, $expectedLine);
        $this->assertSame($expectedLine, $formatter->itemToLine($newItem));
    }

    /**
     * @return array[]
     */
    public function dataAlternativeExportItem()
    {
        return [
            [
                Item::createFromArray([
                    'uuid' => ['id' => '123', 'type' => 'test'],
                    'coordinate' => [
                        'lat' => 45.6,
                        'lon' => 123.0,
                    ],
                ]),
                $this->getFormatTransformer()->headers(),
                $this->getAlternativeLine(),
            ],
        ];
    }

    /**
     * @return string
     */
    abstract protected function getLine(): string;

    /**
     * @return string
     */
    abstract protected function getAlternativeLine(): string;

    /**
     * @return bool
     */
    abstract protected function optimizes(): bool;
}
