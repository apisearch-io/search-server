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
        $this->configureIndex(Config::createEmpty()->addSynonym(Synonym::createByWords(['percebeiro', 'alfaguarra'])));

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
    }
}
