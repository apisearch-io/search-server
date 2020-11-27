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
use Apisearch\Server\Domain\Repository\LogRepository\LogFilter;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use DateTime;

/**
 * Class LogFilterTest.
 */
class LogFilterTest extends BaseUnitTest
{
    /**
     * Test filter by repository reference.
     */
    public function testFilterByRepositoryReference()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = LogFilter::create($repositoryReference);
        $this->assertEquals($repositoryReference, $filter->getRepositoryReference());
    }

    /**
     * Test filter by from.
     */
    public function testFilterFrom()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $date = new DateTime();
        $filter = LogFilter::create($repositoryReference)->from($date);
        $this->assertEquals($date, $filter->getFrom());

        $this->assertNull(LogFilter::create($repositoryReference)->getFrom());
    }

    /**
     * Test filter by to.
     */
    public function testFilterTo()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $date = new DateTime();
        $filter = LogFilter::create($repositoryReference)->to($date);
        $this->assertEquals($date, $filter->getTo());

        $this->assertNull(LogFilter::create($repositoryReference)->getTo());
    }

    /**
     * Test filter from types.
     */
    public function testFilterFromTypes()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = LogFilter::create($repositoryReference);
        $this->assertEquals([], $filter->getTypes());

        $filter = LogFilter::create($repositoryReference)->fromTypes([]);
        $this->assertEquals([], $filter->getTypes());

        $filter = LogFilter::create($repositoryReference)->fromTypes(['type1', 'type2']);
        $this->assertEquals(['type1', 'type2'], $filter->getTypes());
    }

    /**
     * Test filter pagination.
     */
    public function testPagination()
    {
        $repositoryReference = RepositoryReference::createFromComposed('a_b');
        $filter = LogFilter::create($repositoryReference);
        $this->assertEquals([], $filter->getPagination());

        $filter = LogFilter::create($repositoryReference)->paginate(0, 0);
        $this->assertEquals([], $filter->getPagination());

        $filter = LogFilter::create($repositoryReference)->paginate(0, 1);
        $this->assertEquals([], $filter->getPagination());

        $filter = LogFilter::create($repositoryReference)->paginate(1, 0);
        $this->assertEquals([], $filter->getPagination());

        $filter = LogFilter::create($repositoryReference)->paginate(1, 1);
        $this->assertEquals([1, 1], $filter->getPagination());

        $filter = LogFilter::create($repositoryReference)->paginate(3, 10);
        $this->assertEquals([3, 10], $filter->getPagination());
    }
}
