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
use Apisearch\Model\ItemUUID;

/**
 * Class ExportTest.
 */
trait ExportTest
{
    /**
     * Test item export.
     */
    public function testIndexExport()
    {
        $this->assertCount(5, $this->exportIndex());
    }

    /**
     * Test item export with not found index.
     */
    public function testIndexExportAppNotFound()
    {
        $this->expectException(\Exception::class);
        $this->exportIndex(false, static::$anotherInexistentAppId);
    }

    /**
     * Test item export with not found index.
     */
    public function testIndexExportIndexNotFound()
    {
        $this->expectException(\Exception::class);
        $this->exportIndex(false, static::$appId, static::$yetAnotherIndex);
    }

    /**
     * Test complete export.
     */
    public function testCompleteExport()
    {
        $items = [];
        $itemsUUID = [];
        for ($i = 0; $i < 1000; ++$i) {
            $itemsUUID[] = ItemUUID::createFromArray([
                'id' => $i,
                'type' => 'type1',
            ]);

            $items[] = Item::createFromArray([
                'uuid' => [
                    'id' => $i,
                    'type' => 'type1',
                ],
                'metadata' => [
                    'title' => 'value',
                ],
            ]);
        }

        static::indexItems($items);
        $this->assertCount(1005, $this->exportIndex());
        static::deleteItems($itemsUUID);
        $this->assertCount(5, $this->exportIndex());
    }
}
