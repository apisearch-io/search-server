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

use Apisearch\Query\Query;

/**
 * Class FuzzinessTest.
 */
trait FuzzinessTest
{
    /**
     * Test fuzziness.
     *
     * @return void
     */
    public function testFuzziness(): void
    {
        $items = $this
            ->query(Query::create('matutano'))
            ->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getId());

        $items = $this
            ->query(Query::create('mautano'))
            ->getItems();

        $this->assertCount(0, $items);

        $items = $this
            ->query(Query::create('mautano')->setFuzziness(1))
            ->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getId());

        $items = $this
            ->query(Query::create('matano')->setFuzziness(1))
            ->getItems();

        $this->assertCount(0, $items);

        $items = $this
            ->query(Query::create('mautano')->setFuzziness(2))
            ->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getId());

        $items = $this
            ->query(Query::create('mautano')->setFuzziness('AUTO'))
            ->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getId());

        $items = $this
            ->query(Query::create('mautano')->setAutoFuzziness())
            ->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getId());
    }

    /**
     * Test array fuzziness.
     *
     * @return void
     */
    public function testArrayFuzziness(): void
    {
        $items = $this
            ->query(
                Query::create('matano')
                    ->setsearchableFields([
                        'searchable_metadata.editorial^10',
                        'searchable_metadata.specific^5',
                        'searchable_metadata.boosting^1',
                        'exact_matching_metadata.*^50',
                    ])
                    ->setFuzziness([
                        'searchable_metadata.editorial' => 2.0,
                        'searchable_metadata.specific' => 'auto',
                        'searchable_metadata.boosting' => 5,
                        'searchable_metadata.non-existing' => 10,
                    ])
            )
            ->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getId());

        $items = $this
            ->query(
                Query::create('matano')
                    ->setsearchableFields([
                        'searchable_metadata.editorial^10',
                        'searchable_metadata.specific^5',
                        'searchable_metadata.boosting^1',
                        'exact_matching_metadata.*^50',
                    ])
                    ->setFuzziness([
                        'searchable_metadata.editorial' => 1.0,
                        'searchable_metadata.specific' => 'auto',
                        'searchable_metadata.boosting' => 5,
                        'searchable_metadata.non-existing' => 10,
                    ])
            )
            ->getItems();

        $this->assertCount(0, $items);

        $items = $this
            ->query(
                Query::create('nither matano')
                    ->setsearchableFields([
                        'searchable_metadata.editorial^10',
                        'searchable_metadata.specific^5',
                        'searchable_metadata.boosting^1',
                        'exact_matching_metadata.*^50',
                    ])
                    ->setFuzziness([
                        'searchable_metadata.editorial' => 2.0,
                        'searchable_metadata.specific' => 'auto',
                    ])
            )
            ->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getId());
    }

    /**
     * Test array fuzziness.
     *
     * @return void
     */
    public function testChangedCharsFuzziness(): void
    {
        $items = $this
            ->query(
                Query::create('mattuano')
                    ->setsearchableFields([
                        'searchable_metadata.editorial',
                        'searchable_metadata.specific',
                    ])
                    ->setFuzziness([
                        'searchable_metadata.editorial' => 1.0,
                        'searchable_metadata.specific' => 'auto',
                    ])
            )
            ->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getId());

        $items = $this
            ->query(
                Query::create('mattauno')
                    ->setsearchableFields([
                        'searchable_metadata.editorial',
                        'searchable_metadata.specific',
                    ])
                    ->setFuzziness([
                        'searchable_metadata.editorial' => 2.0,
                        'searchable_metadata.specific' => 'auto',
                    ])
            )
            ->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getId());

        $items = $this
            ->query(
                Query::create('mattauno')
                    ->setsearchableFields([
                        'searchable_metadata.editorial',
                        'searchable_metadata.specific',
                    ])
                    ->setFuzziness([
                        'searchable_metadata.editorial' => 1.0,
                        'searchable_metadata.specific' => 'auto',
                    ])
            )
            ->getItems();

        $this->assertCount(0, $items);
    }
}
