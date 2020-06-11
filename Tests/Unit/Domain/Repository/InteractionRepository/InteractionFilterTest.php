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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\InteractionRepository;

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionFilter;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use DateTime;

/**
 * Class InteractionFilterTest.
 */
class InteractionFilterTest extends BaseUnitTest
{
    /**
     * Test filter by repository reference.
     */
    public function testFilterByRepositoryReference()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = InteractionFilter::create($repositoryReference);
        $this->assertEquals($repositoryReference, $filter->getRepositoryReference());
    }

    /**
     * Test filter by user.
     */
    public function testFilterByUser()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $user = 'user-1';
        $filter = InteractionFilter::create($repositoryReference)->byUser($user);
        $this->assertEquals($user, $filter->getUser());

        $this->assertNull(InteractionFilter::create($repositoryReference)->getUser());
    }

    /**
     * Test filter by item.
     */
    public function testFilterByItem()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $item = ItemUUID::createByComposedUUID('1~product');
        $filter = InteractionFilter::create($repositoryReference)->byItem($item);
        $this->assertEquals($item, $filter->getItemUUID());

        $this->assertNull(InteractionFilter::create($repositoryReference)->getItemUUID());
    }

    /**
     * Test filter by from.
     */
    public function testFilterFrom()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $date = new DateTime();
        $filter = InteractionFilter::create($repositoryReference)->from($date);
        $this->assertEquals($date, $filter->getFrom());

        $this->assertNull(InteractionFilter::create($repositoryReference)->getFrom());
    }

    /**
     * Test filter by to.
     */
    public function testFilterTo()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $date = new DateTime();
        $filter = InteractionFilter::create($repositoryReference)->to($date);
        $this->assertEquals($date, $filter->getTo());

        $this->assertNull(InteractionFilter::create($repositoryReference)->getTo());
    }

    /**
     * Test filter by platform.
     */
    public function testFilterByPlatform()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $platform = 'desktop';
        $filter = InteractionFilter::create($repositoryReference)->byPlatform($platform);
        $this->assertEquals($platform, $filter->getPlatform());

        $this->assertNull(InteractionFilter::create($repositoryReference)->getPlatform());
    }

    /**
     * Test filter by type.
     */
    public function testFilterByType()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $type = 'cli';
        $filter = InteractionFilter::create($repositoryReference)->byType($type);
        $this->assertEquals($type, $filter->getType());

        $this->assertNull(InteractionFilter::create($repositoryReference)->getType());
    }

    /**
     * Test filter per day.
     */
    public function testFilterPerDay()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = InteractionFilter::create($repositoryReference)->perDay();
        $this->assertTrue($filter->isPerDay());

        $filter = InteractionFilter::create($repositoryReference)->perDay(true);
        $this->assertTrue($filter->isPerDay());

        $filter = InteractionFilter::create($repositoryReference)->perDay(false);
        $this->assertFalse($filter->isPerDay());

        $this->assertFalse(InteractionFilter::create($repositoryReference)->isPerDay());
    }

    /**
     * Test count.
     */
    public function testCount()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = InteractionFilter::create($repositoryReference)->count(InteractionFilter::UNIQUE_USERS);
        $this->assertEquals(InteractionFilter::UNIQUE_USERS, $filter->getCount());

        $filter = InteractionFilter::create($repositoryReference)->count(InteractionFilter::LINES);
        $this->assertEquals(InteractionFilter::LINES, $filter->getCount());

        $filter = InteractionFilter::create($repositoryReference)->count(null);
        $this->assertEquals(InteractionFilter::LINES, $filter->getCount());

        $this->assertEquals(InteractionFilter::LINES, InteractionFilter::create($repositoryReference)->getCount());
    }
}
