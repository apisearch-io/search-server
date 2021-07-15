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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\PurchaseRepository;

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\PurchaseRepository\Purchase;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseFilter;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseRepository;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use DateTime;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class PurchaseRepositoryTest.
 */
abstract class PurchaseRepositoryTest extends BaseUnitTest
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
     * @return PurchaseRepository
     */
    abstract public function getEmptyRepository(LoopInterface $loop): PurchaseRepository;

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

        $this->assertEquals([], $this->await($repository->getRegisteredPurchases(PurchaseFilter::create($repositoryReference)), $loop));
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
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($repositoryReference));
        $this->assertCount(5, $this->await($purchases, $loop));
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
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop, RepositoryReference::createFromComposed('C_D'));
        $this->addPurchase($repository, $loop, RepositoryReference::createFromComposed('C_D'));
        $this->addPurchase($repository, $loop, RepositoryReference::createFromComposed('C_D'));
        $this->addPurchase($repository, $loop, RepositoryReference::createFromComposed('a_N'));
        $this->addPurchase($repository, $loop, RepositoryReference::createFromComposed('a_M'));

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($repositoryReference));
        $this->assertCount(2, $this->await($purchases, $loop));
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($repositoryReference)->count(PurchaseFilter::LINES));
        $this->assertEquals(2, $this->await($purchases, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('C_D');
        $purchaseFilter = PurchaseFilter::create($anotherRepositoryReference);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(3, $this->await($purchases, $loop));
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($anotherRepositoryReference)->count(PurchaseFilter::LINES));
        $this->assertEquals(3, $this->await($purchases, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('X_X');
        $purchaseFilter = PurchaseFilter::create($anotherRepositoryReference);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(0, $this->await($purchases, $loop));
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($anotherRepositoryReference)->count(PurchaseFilter::LINES));
        $this->assertEquals(0, $this->await($purchases, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('*_*');
        $purchaseFilter = PurchaseFilter::create($anotherRepositoryReference);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(7, $this->await($purchases, $loop));
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($anotherRepositoryReference)->count(PurchaseFilter::LINES));
        $this->assertEquals(7, $this->await($purchases, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('a_*');
        $purchaseFilter = PurchaseFilter::create($anotherRepositoryReference);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(4, $this->await($purchases, $loop));
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($anotherRepositoryReference)->count(PurchaseFilter::LINES));
        $this->assertEquals(4, $this->await($purchases, $loop));

        $anotherRepositoryReference = RepositoryReference::createFromComposed('a_N');
        $purchaseFilter = PurchaseFilter::create($anotherRepositoryReference);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(1, $this->await($purchases, $loop));
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($anotherRepositoryReference)->count(PurchaseFilter::LINES));
        $this->assertEquals(1, $this->await($purchases, $loop));
    }

    public function testCombination1()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->addPurchase($repository, $loop, null, 'user-1', ['1~p', '2~p']);

        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($repositoryReference));
        $this->assertCount(1, $this->await($purchases, $loop));
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($repositoryReference)->byItem(ItemUUID::createByComposedUUID('1~p')));
        $this->assertCount(1, $this->await($purchases, $loop));
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($repositoryReference)->count(PurchaseFilter::LINES));
        $this->assertEquals(1, $this->await($purchases, $loop));
        $purchases = $repository->getRegisteredPurchases(PurchaseFilter::create($repositoryReference)->count(PurchaseFilter::LINES)->byItem(ItemUUID::createByComposedUUID('1~p')));
        $this->assertEquals(1, $this->await($purchases, $loop));
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
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop, $repositoryReference, 'user-3');
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop, $repositoryReference, 'user-2');
        $this->addPurchase($repository, $loop, $repositoryReference, 'user-3');
        $this->addPurchase($repository, $loop, $repositoryReference, 'user-2');
        $this->addPurchase($repository, $loop, $repositoryReference, 'user-2');
        $this->addPurchase($repository, $loop, $repositoryReference, 'user-10');

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byUser('user-1');
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(4, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byUser('user-2');
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(3, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byUser('user-3');
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(2, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byUser('user-10');
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(1, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byUser('user-99');
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(0, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byUser('user-99')->count(PurchaseFilter::LINES);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals(0, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byUser(null);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(10, $this->await($purchases, $loop));
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
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop, $repositoryReference, $user, ['2~p']);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop, $repositoryReference, $user, ['2~p', '10~l']);
        $this->addPurchase($repository, $loop, $repositoryReference, $user, ['3~p']);
        $this->addPurchase($repository, $loop, $repositoryReference, $user, ['11~p']);
        $this->addPurchase($repository, $loop, $repositoryReference, $user, ['2~p']);
        $this->addPurchase($repository, $loop, $repositoryReference, $user, ['2~p', '10~l', '10~l']);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop, $repositoryReference, $user, ['3~p']);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byItem(ItemUUID::createByComposedUUID('1~p'));
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(3, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byItem(ItemUUID::createByComposedUUID('2~p'));
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(4, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byItem(null);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(10, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->byItem(ItemUUID::createByComposedUUID('10~l'));
        $purchases = $this->await($repository->getRegisteredPurchases($purchaseFilter), $loop);
        $this->assertCount(2, $purchases);

        /**
         * @var Purchase[]
         */
        $firstPurchase = $purchases[0];
        $this->assertEquals($repositoryReference->getAppUUID()->composeUUID(), $firstPurchase->getAppUUID());
        $this->assertEquals($repositoryReference->getIndexUUID()->composeUUID(), $firstPurchase->getIndexUUID());
        $this->assertEquals($user, $firstPurchase->getUser());
        $this->assertInstanceOf(DateTime::class, $firstPurchase->getWhen());
        $firstPurchaseItems = $firstPurchase->getPurchaseItems()->getItems();
        $this->assertCount(2, $firstPurchaseItems);
        $this->assertEquals('2~p', $firstPurchaseItems[0]->getItemUUID()->composeUUID());
        $this->assertEquals('10~l', $firstPurchaseItems[1]->getItemUUID()->composeUUID());

        $firstPurchase = $purchases[1];
        $this->assertEquals($repositoryReference->getAppUUID()->composeUUID(), $firstPurchase->getAppUUID());
        $this->assertEquals($repositoryReference->getIndexUUID()->composeUUID(), $firstPurchase->getIndexUUID());
        $this->assertEquals($user, $firstPurchase->getUser());
        $this->assertInstanceOf(DateTime::class, $firstPurchase->getWhen());
        $firstPurchaseItems = $firstPurchase->getPurchaseItems()->getItems();
        $this->assertCount(3, $firstPurchaseItems);
        $this->assertEquals('2~p', $firstPurchaseItems[0]->getItemUUID()->composeUUID());
        $this->assertEquals('10~l', $firstPurchaseItems[1]->getItemUUID()->composeUUID());
        $this->assertEquals('10~l', $firstPurchaseItems[2]->getItemUUID()->composeUUID());
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

        $this->addPurchaseWhen($repository, $loop, self::DAY_31_12_2019);
        $this->addPurchaseWhen($repository, $loop, self::DAY_31_12_2019);
        $this->addPurchaseWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addPurchaseWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addPurchaseWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addPurchaseWhen($repository, $loop, self::DAY_15_1_2020);
        $this->addPurchaseWhen($repository, $loop, self::DAY_15_1_2020);
        $this->addPurchaseWhen($repository, $loop, self::DAY_15_1_2020, 'user~1', ['1~p', '999~o']);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->perDay();
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->perDay()->from(DateTime::createFromFormat('Ymd', self::DAY_MINUS_INF));
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->perDay()->to(DateTime::createFromFormat('Ymd', self::DAY_INF));
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)
            ->perDay()
            ->from(DateTime::createFromFormat('Ymd', self::DAY_MINUS_INF))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_INF));

        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)
            ->perDay(false)
            ->from(DateTime::createFromFormat('Ymd', self::DAY_MINUS_INF))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_INF));

        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertCount(8, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)
            ->perDay()
            ->from(DateTime::createFromFormat('Ymd', self::DAY_31_12_2019))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_INF));

        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 3,
        ], $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)
            ->perDay()
            ->from(DateTime::createFromFormat('Ymd', self::DAY_31_12_2019))
            ->to(DateTime::createFromFormat('Ymd', self::DAY_15_1_2020));

        $purchases = $this->await($repository->getRegisteredPurchases($purchaseFilter), $loop);
        $this->assertEquals([
            self::DAY_31_12_2019 => 2,
            self::DAY_1_1_2020 => 3,
        ], $purchases);
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
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop, $repositoryReference, $user2, [$itemUUID]);
        $this->addPurchase($repository, $loop, $repositoryReference, $user2);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop);
        $this->addPurchase($repository, $loop, $repositoryReference, $user3, [$itemUUID]);
        $this->addPurchase($repository, $loop, $repositoryReference, $user3);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->count(PurchaseFilter::UNIQUE_USERS);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals(3, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)
            ->byItem(ItemUUID::createByComposedUUID('1~p'))
            ->count(PurchaseFilter::UNIQUE_USERS);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals(3, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)
            ->byItem(ItemUUID::createByComposedUUID('2~p'))
            ->count(PurchaseFilter::UNIQUE_USERS);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals(2, $this->await($purchases, $loop));

        $purchaseFilter = PurchaseFilter::create($repositoryReference)
            ->byItem(ItemUUID::createByComposedUUID('10~p'))
            ->count(PurchaseFilter::UNIQUE_USERS);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals(0, $this->await($purchases, $loop));
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

        $this->addPurchaseWhen($repository, $loop, self::DAY_31_12_2019, $user2);
        $this->addPurchaseWhen($repository, $loop, self::DAY_31_12_2019, $user3);
        $this->addPurchaseWhen($repository, $loop, self::DAY_31_12_2019);
        $this->addPurchaseWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addPurchaseWhen($repository, $loop, self::DAY_1_1_2020);
        $this->addPurchaseWhen($repository, $loop, self::DAY_1_1_2020, $user2);
        $this->addPurchaseWhen($repository, $loop, self::DAY_1_1_2020, $user3);
        $this->addPurchaseWhen($repository, $loop, self::DAY_15_1_2020, $user2);
        $this->addPurchaseWhen($repository, $loop, self::DAY_15_1_2020);
        $this->addPurchaseWhen($repository, $loop, self::DAY_15_1_2020, $user2);
        $this->addPurchaseWhen($repository, $loop, self::DAY_15_1_2020, $user3);
        $this->addPurchaseWhen($repository, $loop, self::DAY_15_1_2020, $user4);

        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $purchaseFilter = PurchaseFilter::create($repositoryReference)->perDay()->count(PurchaseFilter::UNIQUE_USERS);
        $purchases = $repository->getRegisteredPurchases($purchaseFilter);
        $this->assertEquals([
            self::DAY_31_12_2019 => 3,
            self::DAY_1_1_2020 => 3,
            self::DAY_15_1_2020 => 4,
        ], $this->await($purchases, $loop));
    }

    /**
     * @return RepositoryReference
     */
    protected function getDefaultRepositoryReference(): RepositoryReference
    {
        return RepositoryReference::createFromComposed('a_b');
    }

    /**
     * Add purchase from time.
     *
     * @param PurchaseRepository $repository
     * @param LoopInterface      $loop
     * @param string             $when
     * @param string             $userUUID
     * @param string[]           $itemsUUID
     *
     * @return void
     */
    private function addPurchaseWhen(
        PurchaseRepository $repository,
        LoopInterface $loop,
        string $when,
        string $userUUID = 'user-1',
        array $itemsUUID = ['1~p']
    ): void {
        $promise = $repository->registerPurchase(
            $repositoryReference ?? $this->getDefaultRepositoryReference(),
            $userUUID,
            \DateTime::createFromFormat('Ymd', $when),
            \array_map(fn (string $itemUUIDComposed) => ItemUUID::createByComposedUUID($itemUUIDComposed), $itemsUUID)
        );

        $this->await($promise, $loop);
    }

    /**
     * @param PurchaseRepository       $repository
     * @param LoopInterface            $loop
     * @param RepositoryReference|null $repositoryReference
     * @param string                   $userUUID
     * @param string[]                 $itemsUUID
     *
     * @return void
     */
    protected function addPurchase(
        PurchaseRepository $repository,
        LoopInterface $loop,
        RepositoryReference $repositoryReference = null,
        string $userUUID = 'user-1',
        array $itemsUUID = ['1~p']
    ): void {
        $promise = $repository->registerPurchase(
            $repositoryReference ?? $this->getDefaultRepositoryReference(),
            $userUUID,
            new DateTime(),
            \array_map(fn (string $itemUUIDComposed) => ItemUUID::createByComposedUUID($itemUUIDComposed), $itemsUUID)
        );

        $this->await($promise, $loop);
    }
}
