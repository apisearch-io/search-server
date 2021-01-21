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
     *
     * @return void
     */
    public function testIndexExport(): void
    {
        $data = $this->exportIndex('source');
        $this->assertCount(6, $data);
        $this->assertNotContains('category_data', $data[1]);
        $this->assertNotContains('category_id', $data[1]);
    }

    /**
     * Test item export.
     *
     * @return void
     */
    public function testIndexExportStandardFormat(): void
    {
        $this->assertCount(6, $this->exportIndex('standard'));
    }

    /**
     * Test item export with not found index.
     *
     * @return void
     */
    public function testIndexExportAppNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->exportIndex('', false, static::$anotherInexistentAppId);
    }

    /**
     * Test item export with not found index.
     *
     * @return void
     */
    public function testIndexExportIndexNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->exportIndex('', false, static::$appId, static::$yetAnotherIndex);
    }

    /**
     * Test complete export.
     *
     * @return void
     */
    public function testCompleteExport(): void
    {
        $items = [];
        $itemsUUID = [];
        for ($i = 0; $i < 1000; ++$i) {
            $itemsUUID[] = ItemUUID::createFromArray([
                'id' => $i,
                'type' => 't1',
            ]);

            $items[] = Item::createFromArray([
                'uuid' => [
                    'id' => $i,
                    'type' => 't1',
                ],
            ]);
        }

        static::indexItems($items);
        $this->assertCount(1006, $this->exportIndex('source'));
        static::deleteItems($itemsUUID);
        $this->assertCount(6, $this->exportIndex('source'));
    }
}
