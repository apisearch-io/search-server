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
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesFilter;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use DateTime;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class SearchesRepositoryTest.
 */
abstract class SearchesRepositoryTest extends BaseUnitTest
{
    /**
     * @var int
     */
    const DAY_MINUS_INF = '20000101';

    /**
     * @var int
     */
    const DAY_31_12_2019 = '20191231';

    /**
     * @var int
     */
    const DAY_1_1_2020 = '20200101';

    /**
     * @var int
     */
    const DAY_15_1_2020 = '20200115';

    /**
     * @var int
     */
    const DAY_INF = '20303131';

    /**
     * @param LoopInterface $loop
     *
     * @return SearchesRepository
     */
    abstract public function getEmptyRepository(LoopInterface $loop): SearchesRepository;

    /**
     * Seconds sleeping before query.
     *
     * @return int
     */
    public function microsecondsSleepingBeforeQuery(): int
    {
        return 0;
    }

    /**
     * Test empty Repository.
     */
    public function testEmpty()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $this->assertEmpty($this->await($repository->getRegisteredSearches(SearchesFilter::create($repositoryReference)), $loop));
    }

    /**
     * Test total.
     */
    public function testTotal()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);
        $interactions = $repository->getRegisteredSearches(SearchesFilter::create($repositoryReference));
        $this->assertEquals(5, $this->await($interactions, $loop));
    }

    /**
     * Test another repository reference.
     */
    public function testPerRepositoryReference()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop, RepositoryReference::createFromComposed('C_D'));
        $this->addSearch($repository, $loop, RepositoryReference::createFromComposed('C_D'));
        $this->addSearch($repository, $loop, RepositoryReference::createFromComposed('C_D'));
        $this->addSearch($repository, $loop, RepositoryReference::createFromComposed('a_N'));
        $this->addSearch($repository, $loop, RepositoryReference::createFromComposed('a_M'));

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactions = $repository->getRegisteredSearches(SearchesFilter::create($repositoryReference));
        $this->assertEquals(2, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('C_D');
        $interactionFilter = SearchesFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('X_X');
        $interactionFilter = SearchesFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(0, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('*_*');
        $interactionFilter = SearchesFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(7, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('a_*');
        $interactionFilter = SearchesFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(4, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('a_N');
        $interactionFilter = SearchesFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(1, $this->await($interactions, $loop));
    }

    /**
     * Test by user.
     */
    public function testByUser()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop, $repositoryReference, 'user-3');
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop, $repositoryReference, 'user-2');
        $this->addSearch($repository, $loop, $repositoryReference, 'user-3');
        $this->addSearch($repository, $loop, $repositoryReference, 'user-2');
        $this->addSearch($repository, $loop, $repositoryReference, 'user-2');
        $this->addSearch($repository, $loop, $repositoryReference, 'user-10');

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = SearchesFilter::create($repositoryReference)->byUser('user-1');
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(4, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->byUser('user-2');
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->byUser('user-3');
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(2, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->byUser('user-10');
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(1, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->byUser('user-99');
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(0, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->byUser(null);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(10, $this->await($interactions, $loop));
    }

    /**
     * Test by platform.
     */
    public function testByPlatform()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user = 'user-1';
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop, $repositoryReference, $user, '', 0, new Origin('', '', Origin::PHONE));
        $this->addSearch($repository, $loop, $repositoryReference, $user, '', 0, new Origin('', '', Origin::PHONE));
        $this->addSearch($repository, $loop, $repositoryReference, $user, '', 0, new Origin('', '', Origin::TABLET));
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = SearchesFilter::create($repositoryReference)->byPlatform(Origin::DESKTOP);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(4, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->byPlatform(Origin::PHONE);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(2, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->byPlatform(Origin::TABLET);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(1, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->byPlatform(Origin::MOBILE);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->byPlatform(null);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(7, $this->await($interactions, $loop));
    }

    /**
     * Test per day.
     */
    public function testPerDay()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();

        $this->addSearchWhen($repository, $loop, self::DAY_31_12_2019);
        $this->addSearchWhen($repository, $loop, self::DAY_31_12_2019);
        $this->addSearchWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addSearchWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addSearchWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addSearchWhen($repository, $loop, self::DAY_15_1_2020);
        $this->addSearchWhen($repository, $loop, self::DAY_15_1_2020);
        $this->addSearchWhen($repository, $loop, self::DAY_15_1_2020);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = SearchesFilter::create($repositoryReference)->perDay();
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->perDay()->from(DateTime::createFromFormat('Ymd', self::DAY_MINUS_INF));
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)->perDay()->to(DateTime::createFromFormat('Ymd', self::DAY_INF));
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)
            ->perDay()
            ->from(DateTime::createFromFormat('Ymd', self::DAY_MINUS_INF))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_INF));

        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)
            ->perDay(false)
            ->from(DateTime::createFromFormat('Ymd', self::DAY_MINUS_INF))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_INF));

        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals(8, $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)
            ->perDay()
            ->from(DateTime::createFromFormat('Ymd', self::DAY_31_12_2019))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_INF));

        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = SearchesFilter::create($repositoryReference)
            ->perDay()
            ->from(DateTime::createFromFormat('Ymd', self::DAY_31_12_2019))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_15_1_2020));

        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
        ], $this->await($interactions, $loop));
    }

    /**
     * Test unique user id.
     */
    public function testUniqueUserId()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user2 = 'user-2';
        $user3 = 'user-3';
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop, $repositoryReference, $user2, 'x', 0, new Origin('', '', Origin::TABLET));
        $this->addSearch($repository, $loop, $repositoryReference, $user2);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop);
        $this->addSearch($repository, $loop, $repositoryReference, $user3, 'x', 0, new Origin('', '', Origin::TABLET));
        $this->addSearch($repository, $loop, $repositoryReference, $user3);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $searchesFilter = SearchesFilter::create($repositoryReference)->count(SearchesFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredSearches($searchesFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $searchesFilter = SearchesFilter::create($repositoryReference)
            ->byPlatform(Origin::DESKTOP)
            ->count(SearchesFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredSearches($searchesFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $searchesFilter = SearchesFilter::create($repositoryReference)
            ->byPlatform(Origin::TABLET)
            ->count(SearchesFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredSearches($searchesFilter);
        $this->assertEquals(2, $this->await($interactions, $loop));

        $searchesFilter = SearchesFilter::create($repositoryReference)
            ->byPlatform(Origin::ROBOT)
            ->count(SearchesFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredSearches($searchesFilter);
        $this->assertEquals(0, $this->await($interactions, $loop));
    }

    /**
     * Test per day.
     */
    public function testUniqueUserIdPerDay()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user2 = 'user-2';
        $user3 = 'user-3';
        $user4 = 'user-4';

        $this->addSearchWhen($repository, $loop, self::DAY_31_12_2019, $user2);
        $this->addSearchWhen($repository, $loop, self::DAY_31_12_2019, $user3);
        $this->addSearchWhen($repository, $loop, self::DAY_31_12_2019);
        $this->addSearchWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addSearchWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addSearchWhen($repository, $loop, self::DAY_1_1_2020, $user2);
        $this->addSearchWhen($repository, $loop, self::DAY_1_1_2020, $user3);
        $this->addSearchWhen($repository, $loop, self::DAY_15_1_2020, $user2);
        $this->addSearchWhen($repository, $loop, self::DAY_15_1_2020);
        $this->addSearchWhen($repository, $loop, self::DAY_15_1_2020, $user2);
        $this->addSearchWhen($repository, $loop, self::DAY_15_1_2020, $user3);
        $this->addSearchWhen($repository, $loop, self::DAY_15_1_2020, $user4);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = SearchesFilter::create($repositoryReference)->perDay()->count(SearchesFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredSearches($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 3,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 4,
        ], $this->await($interactions, $loop));
    }

    /**
     * Test get top searches.
     */
    public function testGetTopInteractedItems()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user = 'user-1';
        $user2 = 'user-2';
        $this->addSearch($repository, $loop, $repositoryReference, $user, 'Hola', 2);
        $this->addSearch($repository, $loop, $repositoryReference, $user, 'Hola', 2);
        $this->addSearch($repository, $loop, $repositoryReference, $user, 'Hola', 3);
        $this->addSearch($repository, $loop, $repositoryReference, $user2, 'Haha', 0);
        $this->addSearch($repository, $loop, $repositoryReference, $user, 'Haha', 2);
        $this->addSearch($repository, $loop, $repositoryReference, $user, 'Lol', 1);
        $this->addSearch($repository, $loop, $repositoryReference, $user2, 'Lol', 0);
        $this->addSearch($repository, $loop, $repositoryReference, $user, 'Hola', 3);
        $this->addSearch($repository, $loop, $repositoryReference, $user2, 'Lol', 0);
        $this->addSearch($repository, $loop, $repositoryReference, $user2, 'Engonga', 0);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);
        $list = $repository->getTopSearches(SearchesFilter::create($repositoryReference), 10);
        $this->assertEquals([
            'Hola' => 4,
            'Lol' => 3,
            'Haha' => 2,
            'Engonga' => 1,
        ], $this->await($list, $loop));

        $list = $repository->getTopSearches(SearchesFilter::create($repositoryReference)->excludeWithResults(false), 10);
        $this->assertEquals([
            'Hola' => 4,
            'Lol' => 3,
            'Haha' => 2,
            'Engonga' => 1,
        ], $this->await($list, $loop));

        $list = $repository->getTopSearches(SearchesFilter::create($repositoryReference)
            ->excludeWithResults(false)
            ->excludeWithoutResults(false),
            10
        );
        $this->assertEquals([
            'Hola' => 4,
            'Lol' => 3,
            'Haha' => 2,
            'Engonga' => 1,
        ], $this->await($list, $loop));

        $list = $repository->getTopSearches(SearchesFilter::create($repositoryReference)
            ->excludeWithResults(false)
            ->excludeWithoutResults(true),
            10
        );
        $this->assertEquals([
            'Hola' => 4,
            'Lol' => 1,
            'Haha' => 1,
        ], $this->await($list, $loop));

        $list = $repository->getTopSearches(SearchesFilter::create($repositoryReference)
            ->excludeWithResults(true)
            ->excludeWithoutResults(false),
            10
        );
        $this->assertEquals([
            'Lol' => 2,
            'Haha' => 1,
            'Engonga' => 1,
        ], $this->await($list, $loop));

        $list = $repository->getTopSearches(SearchesFilter::create($repositoryReference)
            ->excludeWithResults(true)
            ->excludeWithoutResults(true),
            10
        );
        $this->assertEquals([], $this->await($list, $loop));

        $list = $repository->getTopSearches(SearchesFilter::create($repositoryReference)->byUser('user-1'), 10);
        $this->assertEquals([
            'Hola' => 4,
            'Haha' => 1,
            'Lol' => 1,
        ], $this->await($list, $loop));

        $list = $repository->getTopSearches(SearchesFilter::create($repositoryReference)->byUser('user-1')
            ->excludeWithResults(true)
            ->excludeWithoutResults(false),
            10
        );
        $this->assertEquals([], $this->await($list, $loop));
    }

    /**
     * @return RepositoryReference
     */
    private function getDefaultRepositoryReference(): RepositoryReference
    {
        return RepositoryReference::createFromComposed('a_b');
    }

    /**
     * Add search from time.
     *
     * @param SearchesRepository $repository
     * @param LoopInterface      $loop
     * @param string             $when
     * @param string             $userUUID
     */
    private function addSearchWhen(
        SearchesRepository $repository,
        LoopInterface $loop,
        string $when,
        string $userUUID = 'user-1'
    ) {
        $promise = $repository->registerSearch(
            $this->getDefaultRepositoryReference(),
            $userUUID,
            '',
            0,
            Origin::createEmpty(),
            \DateTime::createFromFormat('Ymd', $when)
        );

        $this->await($promise, $loop);
    }

    /**
     * @param SearchesRepository       $repository
     * @param LoopInterface            $loop
     * @param RepositoryReference|null $repositoryReference
     * @param string                   $userUUID
     * @param string                   $searchText
     * @param bool                     $numberOfResults
     * @param Origin|null              $origin
     */
    private function addSearch(
        SearchesRepository $repository,
        LoopInterface $loop,
        RepositoryReference $repositoryReference = null,
        string $userUUID = 'user-1',
        string $searchText = '',
        int $numberOfResults = 0,
        Origin $origin = null
    ) {
        $promise = $repository->registerSearch(
            $repositoryReference ?? $this->getDefaultRepositoryReference(),
            $userUUID,
            $searchText,
            $numberOfResults,
            $origin ?? new Origin('h1', 'ip1', Origin::DESKTOP),
            $when ?? new DateTime()
        );

        $this->await($promise, $loop);
    }
}
