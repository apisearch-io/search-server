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
        $remoteIndexUUID = $this->getPrincipalIndex()->getMetadataValue('remote_uuid');
        $this->assertCount(0, $this->query(Query::create('Flipencio'))->getItems());
        $this->configureIndex(Config::createEmpty()->addSynonym(Synonym::createByWords(['Alfaguarra', 'Flipencio'])));
        $remoteIndexUUID2 = $this->getPrincipalIndex()->getMetadataValue('remote_uuid');
        $this->configureIndex(Config::createEmpty()->addSynonym(Synonym::createByWords(['hermenegildo', 'Alfaguarra', 'eleuterio'])));
        $remoteIndexUUID3 = $this->getPrincipalIndex()->getMetadataValue('remote_uuid');
        $this->assertCount(1, $this->query(Query::create('hermenegildo'))->getItems());
        $this->assertCount(1, $this->query(Query::create('Hermenegildo'))->getItems());
        $this->assertCount(1, $this->query(Query::create('eleuterio'))->getItems());
        $this->assertCount(1, $this->query(Query::create('Eleuterio'))->getItems());

        $this->assertNotEquals($remoteIndexUUID, $remoteIndexUUID2);
        $this->assertNotEquals($remoteIndexUUID, $remoteIndexUUID3);
        $this->assertNotEquals($remoteIndexUUID2, $remoteIndexUUID3);
    }

    /**
     * Test soft configuration, for example, changing a simple metadata. That
     * should not reindex
     */
    public function testSoftConfigureIndex()
    {
        $this->configureIndex(Config::createEmpty());
        $remoteIndexUUID = $this->getPrincipalIndex()->getMetadataValue('remote_uuid');
        $this->configureIndex(Config::createEmpty()->addMetadataValue('key1', 'val1'));
        $remoteIndexUUID2 = $this->getPrincipalIndex()->getMetadataValue('remote_uuid');
        $this->assertEquals($remoteIndexUUID, $remoteIndexUUID2);
    }

    /**
     * Test force configuration reindex
     */
    public function testConfigurationIndexWithForceReindex()
    {
        $this->configureIndex(Config::createEmpty());
        $remoteIndexUUID = $this->getPrincipalIndex()->getMetadataValue('remote_uuid');
        $this->configureIndex(Config::createEmpty()->addMetadataValue('key1', 'val1'), true);
        $remoteIndexUUID2 = $this->getPrincipalIndex()->getMetadataValue('remote_uuid');
        $this->assertNotEquals($remoteIndexUUID, $remoteIndexUUID2);
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
