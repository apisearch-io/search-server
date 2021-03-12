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
use Apisearch\Query\Query;
use Apisearch\Query\SortBy;

/**
 * Class SynonymsTest.
 */
trait SynonymsTest
{
    /**
     * Test synonyms.
     *
     * @return void
     */
    public function testSynonyms(): void
    {
        $this->configureIndex(Config::createEmpty()
            ->addSynonym(Synonym::createByWords(['percebeiro', 'alfaguarra']))
            ->addSynonym(Synonym::createByWords(['composed word', 'composed LOLAZO word']))
            ->addSynonym(Synonym::createByWords(['composed LOLAZO word', 'another composed word', 'little-word']))
            ->addSynonym(Synonym::createByWords(['composed 1', 'another composed word']))
        );

        $result = $this->query(Query::create('alfaguarra'));
        $this->assertCount(1, $result->getItems());
        $this->assertEquals(1, $result->getFirstItem()->getId());
        $result = $this->query(Query::create('percebeiro'));
        $this->assertCount(1, $result->getItems());
        $this->assertEquals(1, $result->getFirstItem()->getId());
        $result = $this->query(Query::create('percebe'));
        $this->assertCount(1, $result->getItems());
        $this->assertEquals(1, $result->getFirstItem()->getId());
        $result = $this->query(Query::create('perc'));
        $this->assertCount(1, $result->getItems());
        $this->assertEquals(1, $result->getFirstItem()->getId());
        $result = $this->query(Query::create('alfaguar'));
        $this->assertCount(1, $result->getItems());
        $this->assertEquals(1, $result->getFirstItem()->getId());

        $result = $this->query(Query::create('composed wor')->setSearchableFields(['exact_matching_metadata']));
        $this->assertCount(0, $result->getItems());

        $result = $this->query(Query::create('composed word')->setSearchableFields(['exact_matching_metadata']));
        $this->assertCount(2, $result->getItems());
        $this->assertEquals(3, $result->getFirstItem()->getId());

        $result = $this->query(Query::create('composed LOLAZO word')->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))->setSearchableFields(['exact_matching_metadata']));
        $this->assertCount(2, $result->getItems());
        $this->assertEquals(3, $result->getFirstItem()->getId());
        $this->assertEquals(5, $result->getItems()[1]->getId());

        $result = $this->query(Query::create('another composed word')->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))->setSearchableFields(['exact_matching_metadata']));
        $this->assertCount(2, $result->getItems());
        $this->assertEquals(3, $result->getFirstItem()->getId());
        $this->assertEquals(5, $result->getItems()[1]->getId());

        $result = $this->query(Query::create('little-word')->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))->setSearchableFields(['exact_matching_metadata']));
        $this->assertCount(2, $result->getItems());
        $this->assertEquals(3, $result->getFirstItem()->getId());
        $this->assertEquals(5, $result->getItems()[1]->getId());

        $result = $this->query(Query::create('composed 1')->setSearchableFields(['exact_matching_metadata']));
        $this->assertCount(1, $result->getItems());
        $this->assertEquals(5, $result->getFirstItem()->getId());
    }
}
