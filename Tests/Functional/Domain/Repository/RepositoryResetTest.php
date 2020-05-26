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
use Apisearch\Query\Query;

/**
 * Class RepositoryResetTest.
 */
trait RepositoryResetTest
{
    /**
     * Test reset repository.
     */
    public function testResetRepository()
    {
        $this->assertCount(
            5,
            $this->query(Query::createMatchAll())->getItems()
        );
        $this->resetIndex();
        $this->assertCount(
            0,
            $this->query(Query::createMatchAll())->getItems()
        );
        $this->resetScenario();
    }

    /**
     * Test reset after field change
     */
    public function testResetRepositoryAfterFieldChange()
    {
        $this->indexItems([
            Item::createFromArray([
                'uuid' => [
                    'id' => '10',
                    'type' => 'test'
                ],
                'indexed_metadata' => [
                    'an_object' => [
                        'id' => '10',
                        'name' => 'lol'
                    ]
                ]
            ])
        ]);

        $index = $this->getPrincipalIndex();
        $this->assertEquals('object', $index->getFields()['indexed_metadata.an_object']);
        $this->assertTrue(in_array('an_object', $index->getMetadataValue('stored_metadata')['complex_fields']));

        $this->resetIndex();
        $this->assertCount(
            0,
            $this->query(Query::createMatchAll())->getItems()
        );

        $index = $this->getPrincipalIndex();
        $this->assertNull($index);

        $this->indexItems([
            Item::createFromArray([
                'uuid' => [
                    'id' => '10',
                    'type' => 'test'
                ],
                'indexed_metadata' => [
                    'an_object' => [
                        'obj1',
                        'obj2'
                    ]
                ]
            ])
        ]);

        $index = $this->getPrincipalIndex('indexed_metadata.an_object');
        $this->assertFalse(array_key_exists('complex_fields', $index->getMetadataValue('stored_metadata')));
        $this->resetScenario();
    }
}
