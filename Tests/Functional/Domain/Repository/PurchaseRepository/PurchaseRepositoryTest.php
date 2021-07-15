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

namespace Apisearch\Server\Tests\Functional\Domain\Repository\PurchaseRepository;

use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseFilter;
use DateTime;

trait PurchaseRepositoryTest
{
    /**
     * Load purchases.
     *
     * @return void
     */
    public function testLoadClicks(): void
    {
        $this->expectNotToPerformAssertions();
        $this->purchase('u1', ['3~it']);
        $this->purchase('u1', ['3~it', '4~it']);
        $this->purchase('u2', ['1~it', '4~it']);
        $this->purchase('u10', ['5~it', '3~it', '3~it']);
    }

    /**
     * Test basic usage.
     *
     * @return void
     */
    public function testBasicUsage(): void
    {
        $purchases = $this->getPurchases(false);
        $this->assertEquals(4, $purchases);
    }

    /**
     * Test when.
     *
     * @return void
     */
    public function testWhenFilter(): void
    {
        $this->assertEquals(4, $this->getPurchases(false, (new DateTime())->modify('-1 day')));
        $this->assertEquals(4, $this->getPurchases(false, null, (new DateTime())->modify('+1 day')));
        $this->assertEquals(4, $this->getPurchases(false, (new DateTime())->modify('-1 day'), (new DateTime())->modify('+1 day')));
    }

    /**
     * Test filter by user.
     *
     * @return void
     */
    public function testFilterByUser(): void
    {
        $this->assertEquals(2, $this->getPurchases(false, null, null, 'u1'));
        $this->assertEquals(1, $this->getPurchases(false, null, null, 'u2'));
        $this->assertEquals(1, $this->getPurchases(false, null, null, 'u10'));
        $this->assertEquals(0, $this->getPurchases(false, null, null, 'u100'));
    }

    /**
     * Test filter by item.
     *
     * @return void
     */
    public function testFilterByItem(): void
    {
        $this->assertEquals(1, $this->getPurchases(false, null, null, null, '1~it'));
        $this->assertEquals(0, $this->getPurchases(false, null, null, null, '2~it'));
        $this->assertEquals(3, $this->getPurchases(false, null, null, null, '3~it'));
        $this->assertEquals(2, $this->getPurchases(false, null, null, null, '4~it'));
        $this->assertEquals(1, $this->getPurchases(false, null, null, null, '5~it'));
        $this->assertEquals(0, $this->getPurchases(false, null, null, null, '99~it'));
    }

    /**
     * Test filter by index.
     *
     * @return void
     */
    public function testFilterByIndex(): void
    {
        $this->purchase('u1', ['3~it'], self::$appId, self::$anotherIndex);
        $this->purchase('u7', ['7~it'], self::$appId, self::$anotherIndex);

        $purchases = $this->getPurchases(false);
        $this->assertEquals(6, $purchases);

        $purchases = $this->getPurchases(false, null, null, null, null, PurchaseFilter::LINES, self::$appId, self::$index);
        $this->assertEquals(4, $purchases);

        $purchases = $this->getPurchases(false, null, null, null, null, PurchaseFilter::LINES, self::$appId, self::$anotherIndex);
        $this->assertEquals(2, $purchases);
    }

    /**
     * Test filter by app.
     *
     * @return void
     */
    public function testFilterByApp(): void
    {
        $this->click('u1', '3~it', 2, null, new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$index);
        $this->click('u1', '3~it', 2, null, new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$index);
        $this->click('u1', '2~it', 2, null, new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$index);
        $this->click('u1', '3~it', 2, null, new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$anotherIndex);
        $this->click('u1', '3~it', 2, null, new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$anotherIndex);

        $this->purchase('u1', ['3~it'], self::$anotherAppId, self::$index);
        $this->purchase('u1', ['3~it'], self::$anotherAppId, self::$index);
        $this->purchase('u1', ['3~it'], self::$anotherAppId, self::$index);
        $this->purchase('u1', ['3~it'], self::$anotherAppId, self::$anotherIndex);
        $this->purchase('u1', ['3~it'], self::$anotherAppId, self::$anotherIndex);

        $purchases = $this->getPurchases(false);
        $this->assertEquals(6, $purchases);

        $purchases = $this->getPurchases(false, null, null, null, null, PurchaseFilter::LINES, self::$appId, self::$index);
        $this->assertEquals(4, $purchases);

        $purchases = $this->getPurchases(false, null, null, null, null, PurchaseFilter::LINES, null, self::$index);
        $this->assertEquals(4, $purchases);

        $purchases = $this->getPurchases(false, null, null, null, null, PurchaseFilter::LINES, self::$anotherAppId, self::$index);
        $this->assertEquals(3, $purchases);

        $purchases = $this->getPurchases(false, null, null, null, null, PurchaseFilter::LINES, self::$anotherAppId, self::$anotherIndex);
        $this->assertEquals(2, $purchases);
    }

    /**
     * Test count unique users.
     *
     * @return void
     */
    public function testCountUniqueUsers(): void
    {
        $purchases = $this->getPurchases(true, null, null, null, null, PurchaseFilter::UNIQUE_USERS);
        $this->assertCount(1, $purchases);
        $this->assertEquals(4, \reset($purchases));
        $purchases = $this->getPurchases(false, null, null, null, null, PurchaseFilter::UNIQUE_USERS);
        $this->assertEquals(4, $purchases);

        $purchases = $this->getPurchases(true, null, null, null, '3~it', PurchaseFilter::UNIQUE_USERS);
        $this->assertCount(1, $purchases);
        $this->assertEquals(2, \reset($purchases));
        $purchases = $this->getPurchases(false, null, null, null, '3~it', PurchaseFilter::UNIQUE_USERS);
        $this->assertEquals(2, $purchases);

        $purchases = $this->getPurchases(true, null, null, null, '4~it', PurchaseFilter::UNIQUE_USERS);
        $this->assertCount(1, $purchases);
        $this->assertEquals(2, \reset($purchases));
        $purchases = $this->getPurchases(false, null, null, null, '4~it', PurchaseFilter::UNIQUE_USERS);
        $this->assertEquals(2, $purchases);

        $purchases = $this->getPurchases(true, null, null, null, '5~it', PurchaseFilter::UNIQUE_USERS);
        $this->assertCount(1, $purchases);
        $this->assertEquals(1, \reset($purchases));
        $purchases = $this->getPurchases(false, null, null, null, '5~it', PurchaseFilter::UNIQUE_USERS);
        $this->assertEquals(1, $purchases);

        $purchases = $this->getPurchases(true, null, null, null, '999~it', PurchaseFilter::UNIQUE_USERS);
        $this->assertEquals([], $purchases);
        $purchases = $this->getPurchases(false, null, null, null, '999~it', PurchaseFilter::UNIQUE_USERS);
        $this->assertEquals(0, $purchases);
    }
}
