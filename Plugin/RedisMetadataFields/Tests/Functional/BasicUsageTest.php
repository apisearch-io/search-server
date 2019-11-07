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

namespace Apisearch\Plugin\RedisMetadataFields\Tests\Functional;

use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;
use Clue\React\Block;

/**
 * Class BasicUsageTest.
 */
class BasicUsageTest extends MetadataFieldsFunctionalTest
{
    /**
     * Basic usage.
     */
    public function testBasicUsage()
    {
        $redisClient = self::get('apisearch_plugin.redis_metadata_fields.redis_wrapper')->getClient();
        $loop = $this->get('reactphp.event_loop');

        /*
         * 10 means 5 (5 keys + 5 values)
         */
        $this->assertCount(10, Block\await($redisClient->hGetAll($this->getParameter('apisearch_plugin.redis_metadata_fields.key')), $loop));
        $item = $this->query(Query::createMatchAll())->getFirstItem();
        $this->assertTrue(isset($item->getMetadata()['array_of_arrays']));

        $this->deleteItems([
            ItemUUID::createByComposedUUID('4~bike'),
            ItemUUID::createByComposedUUID('4~bike'),
            ItemUUID::createByComposedUUID('3~book'),
        ]);

        /*
         * 6 means 3 (3 keys + 3 values)
         */
        $this->assertCount(6, Block\await($redisClient->hGetall($this->getParameter('apisearch_plugin.redis_metadata_fields.key')), $loop));
    }
}
