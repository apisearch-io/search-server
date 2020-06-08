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

namespace Apisearch\Server\Tests\Functional\Domain\Repository\InteractionRepository;

use Apisearch\Server\Domain\Model\InteractionType;
use Apisearch\Server\Domain\Model\Origin;
use DateTime;

/**
 * Trait InteractionRepositoryTest.
 */
trait InteractionRepositoryTest
{
    /**
     * Load clicks.
     */
    public function testLoadClicks()
    {
        $this->expectNotToPerformAssertions();
        $this->click('u1', '3~it', new Origin('d.com', '0.0.0.0', Origin::PHONE));
        $this->click('u1', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', new Origin('d.com', '0.0.0.1', origin::PHONE));
        $this->click('u1', '4~it', new Origin('a.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u2', '2~it', new Origin('b.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u1', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '3~it', new Origin('d.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '4~it', new Origin('a.com', '0.0.0.1', origin::TABLET));
        $this->click('u5', '2~it', new Origin('b.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u5', '2~it', new Origin('d.com', '0.0.0.1', origin::PHONE));
        $this->click('u1', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', new Origin('b.com', '0.0.0.0', origin::DESKTOP));
        $this->click('u3', '1~it', new Origin('d.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u3', '1~it', new Origin('a.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u2', '2~it', new Origin('b.com', '0.0.0.1', origin::TABLET));
        $this->click('u2', '1~it', new Origin('d.com', '0.0.0.0', origin::DESKTOP));
        $this->click('u1', '1~it', new Origin('a.com', '0.0.0.1', origin::PHONE));
        $this->click('u2', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '2~it', new Origin('d.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '1~it', new Origin('a.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u2', '2~it', new Origin('a.com', '0.0.0.1', origin::PHONE));
        $this->click('u4', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '2~it', new Origin('a.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', new Origin('a.com', '0.0.0.1', origin::PHONE));
        $this->click('u3', '3~it', new Origin('b.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '2~it', new Origin('a.com', '0.0.0.0', origin::TABLET));
        $this->click('u2', '2~it', new Origin('b.com', '0.0.0.1', origin::DESKTOP));
    }

    /**
     * Test basic usage.
     */
    public function testBasicUsage()
    {
        $interactions = $this->getInteractions(false);
        $this->assertEquals(30, $interactions);
    }

    /**
     * Test when.
     */
    public function testWhenFilter()
    {
        $this->assertEquals(30, $this->getInteractions(false, (new DateTime())->modify('-1 day')));
        $this->assertEquals(30, $this->getInteractions(false, null, (new DateTime())->modify('+1 day')));
        $this->assertEquals(30, $this->getInteractions(false, (new DateTime())->modify('-1 day'), (new DateTime())->modify('+1 day')));
    }

    /**
     * Test filter by user.
     */
    public function testFilterByUser()
    {
        $this->assertEquals(10, $this->getInteractions(false, null, null, 'u1'));
        $this->assertEquals(6, $this->getInteractions(false, null, null, 'u2'));
    }

    /**
     * Test filter by platform.
     */
    public function testFilterByPlatform()
    {
        $this->assertEquals(15, $this->getInteractions(false, null, null, null, origin::PHONE));
        $this->assertEquals(12, $this->getInteractions(false, null, null, null, origin::DESKTOP));
        $this->assertEquals(3, $this->getInteractions(false, null, null, null, origin::TABLET));
        $this->assertEquals(18, $this->getInteractions(false, null, null, null, origin::MOBILE));
    }

    /**
     * Test filter by item.
     */
    public function testFilterByItem()
    {
        $this->assertEquals(16, $this->getInteractions(false, null, null, null, null, '1~it'));
        $this->assertEquals(9, $this->getInteractions(false, null, null, null, null, '2~it'));
    }

    /**
     * Test filter by type.
     */
    public function testFilterByType()
    {
        $this->assertEquals(30, $this->getInteractions(false, null, null, null, null, null, InteractionType::CLICK));
        $this->assertEquals(0, $this->getInteractions(false, null, null, null, null, null, 'another'));
    }

    /**
     * Test filter by index.
     */
    public function testFilterByIndex()
    {
        $this->click('u1', '3~it', new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$appId, self::$anotherIndex);
        $this->click('u1', '3~it', new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$appId, self::$anotherIndex);
        $this->click('u1', '3~it', new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$appId, self::$anotherIndex);

        $interactions = $this->getInteractions(false);
        $this->assertEquals(33, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, self::$appId, self::$index);
        $this->assertEquals(30, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, self::$appId, self::$anotherIndex);
        $this->assertEquals(3, $interactions);
    }

    /**
     * Test filetr by app.
     */
    public function testFilterByApp()
    {
        $this->click('u1', '3~it', new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$index);
        $this->click('u1', '3~it', new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$index);
        $this->click('u1', '2~it', new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$index);
        $this->click('u1', '3~it', new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$anotherIndex);
        $this->click('u1', '3~it', new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$anotherAppId, self::$anotherIndex);

        $interactions = $this->getInteractions(false);
        $this->assertEquals(33, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, self::$appId, self::$index);
        $this->assertEquals(30, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, self::$anotherAppId);
        $this->assertEquals(5, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, self::$anotherAppId, self::$index);
        $this->assertEquals(3, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, self::$anotherAppId, self::$anotherIndex);
        $this->assertEquals(2, $interactions);
    }

    /**
     * Test get top clicks.
     */
    public function testGetTopClicks()
    {
        $this->assertEquals([
            '1~it' => 16,
            '2~it' => 9,
            '3~it' => 6,
            '4~it' => 2,
        ], $this->getTopClicks());

        $this->assertEquals([
            '1~it' => 16,
            '2~it' => 9,
        ], $this->getTopClicks(2));

        $this->assertEquals([
            '1~it' => 6,
            '2~it' => 1,
            '3~it' => 4,
            '4~it' => 2,
        ], $this->getTopClicks(null, null, null, 'u1'));

        $this->assertEquals([
            '1~it' => 11,
            '2~it' => 3,
            '3~it' => 4,
        ], $this->getTopClicks(null, null, null, null, origin::PHONE));

        $this->assertEquals([
            '3~it' => 4,
            '2~it' => 1,
        ], $this->getTopClicks(null, null, null, null, null, self::$anotherAppId));

        $this->assertEquals([
            '3~it' => 2,
        ], $this->getTopClicks(null, null, null, null, null, self::$anotherAppId, self::$anotherIndex));
    }
}
