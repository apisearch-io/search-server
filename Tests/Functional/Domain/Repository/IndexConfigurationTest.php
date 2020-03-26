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
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;

/**
 * Class IndexConfigurationTest.
 */
trait IndexConfigurationTest
{
    /**
     * Test index check.
     */
    public function testSimpleReindex()
    {
        $this->assertCount(5, $this->query(Query::createMatchAll())->getItems());
        $this->configureIndex(Config::createEmpty());
        $this->assertCount(5, $this->query(Query::createMatchAll())->getItems());
    }

    /**
     * Test index check.
     */
    public function testConfigureIndexWithSynonyms()
    {
        $this->assertCount(0, $this->query(Query::create('Flipencio'))->getItems());
        $this->configureIndex(Config::createEmpty()->addSynonym(Synonym::createByWords(['Alfaguarra', 'Flipencio'])));
        $this->configureIndex(Config::createEmpty()->addSynonym(Synonym::createByWords(['hermenegildo', 'Alfaguarra', 'eleuterio'])));
        $this->assertCount(1, $this->query(Query::create('hermenegildo'))->getItems());
        $this->assertCount(1, $this->query(Query::create('Hermenegildo'))->getItems());
        $this->assertCount(1, $this->query(Query::create('eleuterio'))->getItems());
        $this->assertCount(1, $this->query(Query::create('Eleuterio'))->getItems());
    }

    /**
     * Test index check.
     */
    public function testIndexAndDeleteAfterConfigure()
    {
        $this->configureIndex(Config::createEmpty());
        self::indexItems([
            Item::create(ItemUUID::createByComposedUUID('1~lele')),
        ]);
        $this->assertCount(
            6,
            $this->query(Query::createMatchAll())->getItems()
        );
        self::deleteItems([
            ItemUUID::createByComposedUUID('1~lele'),
        ]);
        $this->assertCount(
            5,
            $this->query(Query::createMatchAll())->getItems()
        );
    }
}
