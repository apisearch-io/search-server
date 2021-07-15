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
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionFilter;
use Apisearch\Server\Domain\Repository\InteractionRepository\TestableInteractionRepository;
use DateTime;

/**
 * Trait InteractionRepositoryTest.
 */
trait InteractionRepositoryTest
{
    /**
     * Load clicks.
     *
     * @return void
     */
    public function testLoadClicks(): void
    {
        $this->expectNotToPerformAssertions();
        $this->click('u1', '3~it', 1, 'context1', new Origin('d.com', '0.0.0.0', Origin::PHONE));
        $this->click('u1', '1~it', 1, null, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', 1, null, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', 2, null, new Origin('d.com', '0.0.0.1', origin::PHONE));
        $this->click('u1', '4~it', 2, null, new Origin('a.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u2', '2~it', 2, null, new Origin('b.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '1~it', 2, null, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u1', '1~it', 2, null, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', 2, 'context1', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '3~it', 2, null, new Origin('d.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '4~it', 1, null, new Origin('a.com', '0.0.0.1', origin::TABLET));
        $this->click('u5', '2~it', 1, null, new Origin('b.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u5', '2~it', 1, null, new Origin('d.com', '0.0.0.1', origin::PHONE));
        $this->click('u1', '1~it', 2, 'context2', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', 1, 'context2', new Origin('b.com', '0.0.0.0', origin::DESKTOP));
        $this->click('u3', '1~it', 2, 'context2', new Origin('d.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u3', '1~it', 2, 'context2', new Origin('a.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u2', '2~it', 2, null, new Origin('b.com', '0.0.0.1', origin::TABLET));
        $this->click('u2', '1~it', 1, null, new Origin('d.com', '0.0.0.0', origin::DESKTOP));
        $this->click('u1', '1~it', 1, null, new Origin('a.com', '0.0.0.1', origin::PHONE));
        $this->click('u2', '1~it', 1, null, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '2~it', 2, 'context1', new Origin('d.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '1~it', 2, null, new Origin('a.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u2', '2~it', 2, null, new Origin('a.com', '0.0.0.1', origin::PHONE));
        $this->click('u4', '1~it', 1, null, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '2~it', 2, 'context2', new Origin('a.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', 1, null, new Origin('a.com', '0.0.0.1', origin::PHONE));
        $this->click('u3', '3~it', 1, 'context1', new Origin('b.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '2~it', 2, null, new Origin('a.com', '0.0.0.0', origin::TABLET));
        $this->click('u2', '2~it', 1, null, new Origin('b.com', '0.0.0.1', origin::DESKTOP));
    }

    /**
     * Test basic usage.
     *
     * @return void
     */
    public function testBasicUsage(): void
    {
        $interactions = $this->getInteractions(false);
        $this->assertEquals(30, $interactions);
    }

    /**
     * Test when.
     *
     * @return void
     */
    public function testWhenFilter(): void
    {
        $this->assertEquals(30, $this->getInteractions(false, (new DateTime())->modify('-1 day')));
        $this->assertEquals(30, $this->getInteractions(false, null, (new DateTime())->modify('+1 day')));
        $this->assertEquals(30, $this->getInteractions(false, (new DateTime())->modify('-1 day'), (new DateTime())->modify('+1 day')));
    }

    /**
     * Test filter by user.
     *
     * @return void
     */
    public function testFilterByUser(): void
    {
        $this->assertEquals(10, $this->getInteractions(false, null, null, 'u1'));
        $this->assertEquals(6, $this->getInteractions(false, null, null, 'u2'));
    }

    /**
     * Test filter by platform.
     *
     * @return void
     */
    public function testFilterByPlatform(): void
    {
        $this->assertEquals(15, $this->getInteractions(false, null, null, null, origin::PHONE));
        $this->assertEquals(12, $this->getInteractions(false, null, null, null, origin::DESKTOP));
        $this->assertEquals(3, $this->getInteractions(false, null, null, null, origin::TABLET));
        $this->assertEquals(18, $this->getInteractions(false, null, null, null, origin::MOBILE));
    }

    /**
     * Test filter by item.
     *
     * @return void
     */
    public function testFilterByItem(): void
    {
        $this->assertEquals(16, $this->getInteractions(false, null, null, null, null, '1~it'));
        $this->assertEquals(9, $this->getInteractions(false, null, null, null, null, '2~it'));
    }

    /**
     * Test filter by type.
     *
     * @return void
     */
    public function testFilterByType(): void
    {
        $this->assertEquals(30, $this->getInteractions(false, null, null, null, null, null, InteractionType::CLICK));
        $this->assertEquals(0, $this->getInteractions(false, null, null, null, null, null, 'another'));
    }

    /**
     * Test filter by index.
     *
     * @return void
     */
    public function testFilterByIndex(): void
    {
        $this->click('u1', '3~it', 2, null, new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$appId, self::$anotherIndex);
        $this->click('u1', '3~it', 2, null, new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$appId, self::$anotherIndex);
        $this->click('u1', '3~it', 2, null, new Origin('d.com', '0.0.0.0', Origin::PHONE), self::$appId, self::$anotherIndex);

        $interactions = $this->getInteractions(false);
        $this->assertEquals(33, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, null, null, self::$appId, self::$index);
        $this->assertEquals(30, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, null, null, self::$appId, self::$anotherIndex);
        $this->assertEquals(3, $interactions);
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

        $interactions = $this->getInteractions(false);
        $this->assertEquals(33, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, null, null, self::$appId, self::$index);
        $this->assertEquals(30, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, null, null, self::$anotherAppId);
        $this->assertEquals(5, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, null, null, self::$anotherAppId, self::$index);
        $this->assertEquals(3, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, null, null, self::$anotherAppId, self::$anotherIndex);
        $this->assertEquals(2, $interactions);
    }

    /**
     * Test get top clicks.
     *
     * @return void
     */
    public function testGetTopClicks(): void
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

    /**
     * Test count unique users.
     *
     * @return void
     */
    public function testCountUniqueUsers(): void
    {
        $interactions = $this->getInteractions(true, null, null, null, null, null, null, InteractionFilter::UNIQUE_USERS);
        $this->assertCount(1, $interactions);
        $this->assertEquals(5, \reset($interactions));

        $interactions = $this->getInteractions(true, null, null, null, origin::DESKTOP, null, null, InteractionFilter::UNIQUE_USERS);
        $this->assertCount(1, $interactions);
        $this->assertEquals(4, \reset($interactions));

        $interactions = $this->getInteractions(false, null, null, null, null, null, null, InteractionFilter::UNIQUE_USERS);
        $this->assertEquals(5, $interactions);

        $interactions = $this->getInteractions(false, null, null, null, origin::DESKTOP, '3~it', null, InteractionFilter::UNIQUE_USERS);
        $this->assertEquals(1, $interactions);
    }

    /**
     * Test interaction.
     *
     * @return void
     */
    public function testPosition(): void
    {
        $interactionRepository = $this->get('apisearch_server.interactions_repository_test');
        if (
            !$interactionRepository instanceof TestableInteractionRepository ||
            !$this instanceof ServiceInteractionRepositoryTest
        ) {
            $this->markTestSkipped('Repository not accessible');
        }

        $interactions = $interactionRepository->getInteractions();

        $this->assertEquals(1, $interactions[0]->getPosition());
        $this->assertEquals(2, $interactions[3]->getPosition());
    }

    /**
     * Test context.
     *
     * @return void
     */
    public function testContext(): void
    {
        $interactions = $this->getInteractions(false, null, null, null, null, null, null, null, 'context1', self::$appId, self::$index);
        $this->assertEquals(4, $interactions);
        $interactions = $this->getInteractions(false, null, null, null, null, '3~it', null, null, 'context1', self::$appId, self::$index);
        $this->assertEquals(2, $interactions);
        $interactions = $this->getInteractions(false, null, null, null, null, null, null, null, 'context2', self::$appId, self::$index);
        $this->assertEquals(5, $interactions);
        $interactions = $this->getInteractions(false, null, null, null, null, null, null, null, 'context3', self::$appId, self::$index);
        $this->assertEquals(0, $interactions);
    }
}
