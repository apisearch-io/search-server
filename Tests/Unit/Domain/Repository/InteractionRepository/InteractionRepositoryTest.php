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
use Apisearch\Server\Domain\Model\InteractionType;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionFilter;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use DateTime;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class InteractionRepositoryTest.
 */
abstract class InteractionRepositoryTest extends BaseUnitTest
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
     * @return InteractionRepository
     */
    abstract public function getEmptyRepository(LoopInterface $loop): InteractionRepository;

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
     *
     * @return void
     */
    public function testEmpty(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $this->assertEmpty($this->await($repository->getRegisteredInteractions(InteractionFilter::create($repositoryReference)), $loop));
    }

    /**
     * Test total.
     *
     * @return void
     */
    public function testTotal(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);
        $interactions = $repository->getRegisteredInteractions(InteractionFilter::create($repositoryReference));
        $this->assertEquals(5, $this->await($interactions, $loop));
    }

    /**
     * Test another repository reference.
     *
     * @return void
     */
    public function testPerRepositoryReference(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, RepositoryReference::createFromComposed('C_D'));
        $this->addInteraction($repository, $loop, RepositoryReference::createFromComposed('C_D'));
        $this->addInteraction($repository, $loop, RepositoryReference::createFromComposed('C_D'));
        $this->addInteraction($repository, $loop, RepositoryReference::createFromComposed('a_N'));
        $this->addInteraction($repository, $loop, RepositoryReference::createFromComposed('a_M'));

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactions = $repository->getRegisteredInteractions(InteractionFilter::create($repositoryReference));
        $this->assertEquals(2, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('C_D');
        $interactionFilter = InteractionFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('X_X');
        $interactionFilter = InteractionFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(0, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('*_*');
        $interactionFilter = InteractionFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(7, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('a_*');
        $interactionFilter = InteractionFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(4, $this->await($interactions, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('a_N');
        $interactionFilter = InteractionFilter::create($anotherRepositoryReference);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(1, $this->await($interactions, $loop));
    }

    /**
     * Test by user.
     *
     * @return void
     */
    public function testByUser(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, 'user-3');
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, 'user-2');
        $this->addInteraction($repository, $loop, $repositoryReference, 'user-3');
        $this->addInteraction($repository, $loop, $repositoryReference, 'user-2');
        $this->addInteraction($repository, $loop, $repositoryReference, 'user-2');
        $this->addInteraction($repository, $loop, $repositoryReference, 'user-10');

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = InteractionFilter::create($repositoryReference)->byUser('user-1');
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(4, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byUser('user-2');
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byUser('user-3');
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(2, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byUser('user-10');
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(1, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byUser('user-99');
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(0, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byUser(null);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(10, $this->await($interactions, $loop));
    }

    /**
     * test by item.
     *
     * @return void
     */
    public function testByItem(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user = 'user-1';
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p');
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '3~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '11~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p');
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '3~p');

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = InteractionFilter::create($repositoryReference)->byItem(ItemUUID::createByComposedUUID('1~p'));
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byItem(ItemUUID::createByComposedUUID('2~p'));
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(4, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byItem(null);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(10, $this->await($interactions, $loop));
    }

    /**
     * Test by platform.
     *
     * @return void
     */
    public function testByPlatform(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user = 'user-1';
        $itemUUID = '1~p';
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, $user, $itemUUID, new Origin('', '', Origin::PHONE));
        $this->addInteraction($repository, $loop, $repositoryReference, $user, $itemUUID, new Origin('', '', Origin::PHONE));
        $this->addInteraction($repository, $loop, $repositoryReference, $user, $itemUUID, new Origin('', '', Origin::TABLET));
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = InteractionFilter::create($repositoryReference)->byPlatform(Origin::DESKTOP);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(4, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byPlatform(Origin::PHONE);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(2, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byPlatform(Origin::TABLET);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(1, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byPlatform(Origin::MOBILE);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byPlatform(null);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(7, $this->await($interactions, $loop));
    }

    /**
     * Test by type.
     *
     * @return void
     */
    public function testByType(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user = 'user-1';
        $itemUUID = '1~p';
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, $user, $itemUUID, Origin::createEmpty(), 'cli');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, $itemUUID, Origin::createEmpty(), 'another');
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, $user, $itemUUID, Origin::createEmpty(), 'another');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, $itemUUID, Origin::createEmpty(), 'another');

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = InteractionFilter::create($repositoryReference)->byType(InteractionType::CLICK);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(5, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byType('another');
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->byType(null);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(8, $this->await($interactions, $loop));
    }

    /**
     * Test per day.
     *
     * @return void
     */
    public function testPerDay(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();

        $this->addInteractionWhen($repository, $loop, self::DAY_31_12_2019);
        $this->addInteractionWhen($repository, $loop, self::DAY_31_12_2019);
        $this->addInteractionWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addInteractionWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addInteractionWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addInteractionWhen($repository, $loop, self::DAY_15_1_2020);
        $this->addInteractionWhen($repository, $loop, self::DAY_15_1_2020);
        $this->addInteractionWhen($repository, $loop, self::DAY_15_1_2020);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = InteractionFilter::create($repositoryReference)->perDay();
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->perDay()->from(DateTime::createFromFormat('Ymd', self::DAY_MINUS_INF));
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)->perDay()->to(DateTime::createFromFormat('Ymd', self::DAY_INF));
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)
            ->perDay()
            ->from(DateTime::createFromFormat('Ymd', self::DAY_MINUS_INF))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_INF));

        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)
            ->perDay(false)
            ->from(DateTime::createFromFormat('Ymd', self::DAY_MINUS_INF))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_INF));

        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(8, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)
            ->perDay()
            ->from(DateTime::createFromFormat('Ymd', self::DAY_31_12_2019))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_INF));

        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)
            ->perDay()
            ->from(DateTime::createFromFormat('Ymd', self::DAY_31_12_2019))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_15_1_2020));

        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
        ], $this->await($interactions, $loop));
    }

    /**
     * Test unique user id.
     *
     * @return void
     */
    public function testUniqueUserId(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user2 = 'user-2';
        $user3 = 'user-3';
        $itemUUID = '2~p';
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, $user2, $itemUUID);
        $this->addInteraction($repository, $loop, $repositoryReference, $user2);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop);
        $this->addInteraction($repository, $loop, $repositoryReference, $user3, $itemUUID);
        $this->addInteraction($repository, $loop, $repositoryReference, $user3);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = InteractionFilter::create($repositoryReference)->count(InteractionFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)
            ->byItem(ItemUUID::createByComposedUUID('1~p'))
            ->count(InteractionFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(3, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)
            ->byItem(ItemUUID::createByComposedUUID('2~p'))
            ->count(InteractionFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(2, $this->await($interactions, $loop));

        $interactionFilter = InteractionFilter::create($repositoryReference)
            ->byItem(ItemUUID::createByComposedUUID('10~p'))
            ->count(InteractionFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals(0, $this->await($interactions, $loop));
    }

    /**
     * Test per day.
     *
     * @return void
     */
    public function testUniqueUserIdPerDay(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user2 = 'user-2';
        $user3 = 'user-3';
        $user4 = 'user-4';

        $this->addInteractionWhen($repository, $loop, self::DAY_31_12_2019, $user2);
        $this->addInteractionWhen($repository, $loop, self::DAY_31_12_2019, $user3);
        $this->addInteractionWhen($repository, $loop, self::DAY_31_12_2019);
        $this->addInteractionWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addInteractionWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addInteractionWhen($repository, $loop, self::DAY_1_1_2020, $user2);
        $this->addInteractionWhen($repository, $loop, self::DAY_1_1_2020, $user3);
        $this->addInteractionWhen($repository, $loop, self::DAY_15_1_2020, $user2);
        $this->addInteractionWhen($repository, $loop, self::DAY_15_1_2020);
        $this->addInteractionWhen($repository, $loop, self::DAY_15_1_2020, $user2);
        $this->addInteractionWhen($repository, $loop, self::DAY_15_1_2020, $user3);
        $this->addInteractionWhen($repository, $loop, self::DAY_15_1_2020, $user4);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $interactionFilter = InteractionFilter::create($repositoryReference)->perDay()->count(InteractionFilter::UNIQUE_USERS);
        $interactions = $repository->getRegisteredInteractions($interactionFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 3,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 4,
        ], $this->await($interactions, $loop));
    }

    /**
     * Test get top interacted items.
     *
     * @return void
     */
    public function testGetTopInteractedItems(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user = 'user-1';
        $user2 = 'user-2';

        $this->addInteraction($repository, $loop, $repositoryReference, $user, '1~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user2, '1~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '3~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user2, '4~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '3~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '1~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '1~p');
        $this->addInteraction($repository, $loop, $repositoryReference, $user2, '1~p');

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);
        $list = $repository->getTopInteractedItems(InteractionFilter::create($repositoryReference), 10);
        $this->assertEquals([
            '1~p' => 5,
            '2~p' => 3,
            '3~p' => 2,
            '4~p' => 1,
        ], $this->await($list, $loop));

        $list = $repository->getTopInteractedItems(InteractionFilter::create($repositoryReference), 2);
        $this->assertEquals([
            '1~p' => 5,
            '2~p' => 3,
        ], $this->await($list, $loop));

        $list = $repository->getTopInteractedItems(InteractionFilter::create($repositoryReference)->byUser('user-2'), 2);
        $this->assertEquals([
            '1~p' => 2,
            '4~p' => 1,
        ], $this->await($list, $loop));
    }

    public function testInteractionContext(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $user = 'user-1';

        $this->addInteraction($repository, $loop, $repositoryReference, $user, '1~p', Origin::createEmpty(), 'cli', 'con1');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '1~p', Origin::createEmpty(), 'cli', 'con2');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p', Origin::createEmpty(), 'cli', 'con1');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p', Origin::createEmpty(), 'cli', 'con2');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '2~p', Origin::createEmpty(), 'cli');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '3~p', Origin::createEmpty(), 'cli', 'con1');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '4~p', Origin::createEmpty(), 'cli', 'con2');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '3~p', Origin::createEmpty(), 'cli');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '1~p', Origin::createEmpty(), 'cli', 'con1');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '1~p', Origin::createEmpty(), 'cli', 'con1');
        $this->addInteraction($repository, $loop, $repositoryReference, $user, '1~p', Origin::createEmpty(), 'cli', 'con2');

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);
        $list = $repository->getTopInteractedItems(InteractionFilter::create($repositoryReference)->fromContext('con1'), 10);
        $this->assertEquals([
            '1~p' => 3,
            '2~p' => 1,
            '3~p' => 1,
        ], $this->await($list, $loop));

        $list = $repository->getRegisteredInteractions(InteractionFilter::create($repositoryReference)->fromContext('con1'));
        $this->assertEquals(5, $this->await($list, $loop));

        $list = $repository->getTopInteractedItems(InteractionFilter::create($repositoryReference)->fromContext('con2'), 10);
        $this->assertEquals([
            '1~p' => 2,
            '2~p' => 1,
            '4~p' => 1,
        ], $this->await($list, $loop));

        $list = $repository->getRegisteredInteractions(InteractionFilter::create($repositoryReference)->fromContext('con2'));
        $this->assertEquals(4, $this->await($list, $loop));
    }

    /**
     * @return RepositoryReference
     */
    protected function getDefaultRepositoryReference(): RepositoryReference
    {
        return RepositoryReference::createFromComposed('a_b');
    }

    /**
     * Add interaction from time.
     *
     * @param InteractionRepository $repository
     * @param LoopInterface         $loop
     * @param string                $when
     * @param string                $userUUID
     *
     * @return void
     */
    private function addInteractionWhen(
        InteractionRepository $repository,
        LoopInterface $loop,
        string $when,
        string $userUUID = 'user-1'
    ): void {
        $promise = $repository->registerInteraction(
            $this->getDefaultRepositoryReference(),
            $userUUID,
            ItemUUID::createByComposedUUID('1~p'),
            10,
            null,
            Origin::createEmpty(),
            InteractionType::CLICK,
            \DateTime::createFromFormat('Ymd', $when)
        );

        $this->await($promise, $loop);
    }

    /**
     * @param InteractionRepository    $repository
     * @param LoopInterface            $loop
     * @param RepositoryReference|null $repositoryReference
     * @param string                   $userUUID
     * @param string                   $itemUUID
     * @param Origin|null              $origin
     * @param string                   $type
     * @param string|null              $context
     *
     * @return void
     */
    protected function addInteraction(
        InteractionRepository $repository,
        LoopInterface $loop,
        RepositoryReference $repositoryReference = null,
        string $userUUID = 'user-1',
        string $itemUUID = '1~p',
        Origin $origin = null,
        string $type = InteractionType::CLICK,
        ?string $context = null
    ): void {
        $promise = $repository->registerInteraction(
            $repositoryReference ?? $this->getDefaultRepositoryReference(),
            $userUUID,
            ItemUUID::createByComposedUUID($itemUUID),
            10,
            $context,
            $origin ?? new Origin('h1', 'ip1', Origin::DESKTOP),
            $type,
            new DateTime()
        );

        $this->await($promise, $loop);
    }
}
