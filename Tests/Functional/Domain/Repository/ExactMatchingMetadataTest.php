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
 * Class ExactMatchingMetadataTest.
 */
trait ExactMatchingMetadataTest
{
    /**
     * @return void
     */
    public function testSpecialWords(): void
    {
        $items = $this->query(Query::create('Vinci'))->getItems();
        $this->assertCount(2, $items);
        $item = $items[0];
        $this->assertSame(
            '5',
            $item->getUUID()->getId()
        );

        $item = $this->query(Query::create('vinci'))->getItems()[0];
        $this->assertSame(
            '5',
            $item->getUUID()->getId()
        );

        $item = $this->query(Query::create('vinc'))->getItems()[0];
        $this->assertSame(
            '3',
            $item->getUUID()->getId()
        );

        $item = $this->query(Query::create('engonga'))->getItems()[0];
        $this->assertSame(
            '3',
            $item->getUUID()->getId()
        );
    }

    /**
     * @return void
     */
    public function testExclusiveExactMatching()
    {
        $result = $this->query(Query::create('Vinc')->setMetadataValue('exclusive_exact_matching_metadata', true));
        $items = $result->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('3', $items[0]->getId());

        $result = $this->query(Query::create('Vinci')->setMetadataValue('exclusive_exact_matching_metadata', true));
        $items = $result->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('5', $items[0]->getId());

        $result = $this->query(Query::create('another composed wor')->setMetadataValue('exclusive_exact_matching_metadata', true));
        $this->assertCount(2, $result->getItems());

        $result = $this->query(Query::create('another composed word')->setMetadataValue('exclusive_exact_matching_metadata', true));
        $items = $result->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('5', $items[0]->getId());
    }

    /**
     * @return void
     */
    public function testExclusiveExactMatchingMultiQuery()
    {
        $result = $this->query(Query::createMultiquery([
            Query::create('Vinc'),
            Query::create('Vinci'),
            Query::create('another composed wor'),
            Query::create('another composed word'),
        ])->setMetadataValue('exclusive_exact_matching_metadata', true));

        $items = $result->getSubresults()[0]->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('3', $items[0]->getId());

        $items = $result->getSubresults()[1]->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('5', $items[0]->getId());

        $this->assertCount(2, $result->getSubresults()[2]->getItems());

        $items = $result->getSubresults()[3]->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('5', $items[0]->getId());
    }
}
