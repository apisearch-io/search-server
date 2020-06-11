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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\SearchesRepository;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesFilter;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use DateTime;

/**
 * Class SearchesFilterTest.
 */
class SearchesFilterTest extends BaseUnitTest
{
    /**
     * Test filter by repository reference.
     */
    public function testFilterByRepositoryReference()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = SearchesFilter::create($repositoryReference);
        $this->assertEquals($repositoryReference, $filter->getRepositoryReference());
    }

    /**
     * Test filter by user.
     */
    public function testFilterByUser()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $user = 'user-1';
        $filter = SearchesFilter::create($repositoryReference)->byUser($user);
        $this->assertEquals($user, $filter->getUser());

        $this->assertNull(SearchesFilter::create($repositoryReference)->getUser());
    }

    /**
     * Test filter by from.
     */
    public function testFilterFrom()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $date = new DateTime();
        $filter = SearchesFilter::create($repositoryReference)->from($date);
        $this->assertEquals($date, $filter->getFrom());

        $this->assertNull(SearchesFilter::create($repositoryReference)->getFrom());
    }

    /**
     * Test filter by to.
     */
    public function testFilterTo()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $date = new DateTime();
        $filter = SearchesFilter::create($repositoryReference)->to($date);
        $this->assertEquals($date, $filter->getTo());

        $this->assertNull(SearchesFilter::create($repositoryReference)->getTo());
    }

    /**
     * Test filter by platform.
     */
    public function testFilterByPlatform()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $platform = 'desktop';
        $filter = SearchesFilter::create($repositoryReference)->byPlatform($platform);
        $this->assertEquals($platform, $filter->getPlatform());

        $this->assertNull(SearchesFilter::create($repositoryReference)->getPlatform());
    }

    /**
     * Test filter by not empty results.
     */
    public function testFilterByExcludeWithResults()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = SearchesFilter::create($repositoryReference)->excludeWithResults();
        $this->assertTrue($filter->withResultsAreExcluded());

        $filter = SearchesFilter::create($repositoryReference)->excludeWithResults(false);
        $this->assertFalse($filter->withResultsAreExcluded());

        $filter = SearchesFilter::create($repositoryReference)->excludeWithResults(true);
        $this->assertTrue($filter->withResultsAreExcluded());

        $this->assertFalse(SearchesFilter::create($repositoryReference)->withResultsAreExcluded());
    }

    /**
     * Test filter by empty results.
     */
    public function testFilterByExcludeWithoutResults()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = SearchesFilter::create($repositoryReference)->excludeWithoutResults();
        $this->assertTrue($filter->withoutResultsAreExcluded());

        $filter = SearchesFilter::create($repositoryReference)->excludeWithoutResults(false);
        $this->assertFalse($filter->withoutResultsAreExcluded());

        $filter = SearchesFilter::create($repositoryReference)->excludeWithoutResults(true);
        $this->assertTrue($filter->withoutResultsAreExcluded());

        $this->assertFalse(SearchesFilter::create($repositoryReference)->withoutResultsAreExcluded());
    }

    /**
     * Test filter per day.
     */
    public function testFilterPerDay()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = SearchesFilter::create($repositoryReference)->perDay();
        $this->assertTrue($filter->isPerDay());

        $filter = SearchesFilter::create($repositoryReference)->perDay(true);
        $this->assertTrue($filter->isPerDay());

        $filter = SearchesFilter::create($repositoryReference)->perDay(false);
        $this->assertFalse($filter->isPerDay());

        $this->assertFalse(SearchesFilter::create($repositoryReference)->isPerDay());
    }

    /**
     * Test count.
     */
    public function testCount()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = SearchesFilter::create($repositoryReference)->count(SearchesFilter::UNIQUE_USERS);
        $this->assertEquals(SearchesFilter::UNIQUE_USERS, $filter->getCount());

        $filter = SearchesFilter::create($repositoryReference)->count(SearchesFilter::LINES);
        $this->assertEquals(SearchesFilter::LINES, $filter->getCount());

        $filter = SearchesFilter::create($repositoryReference)->count(null);
        $this->assertEquals(SearchesFilter::LINES, $filter->getCount());

        $this->assertEquals(SearchesFilter::LINES, SearchesFilter::create($repositoryReference)->getCount());
    }
}
