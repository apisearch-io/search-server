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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\UsageRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use function Clue\React\Block\await;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class UsageRepositoryTest.
 */
abstract class UsageRepositoryTest extends TestCase
{
    /**
     * @var int
     */
    const DAY_MINUS_INF = 20000101;

    /**
     * @var int
     */
    const DAY_31_12_2019 = 20191231;

    /**
     * @var int
     */
    const DAY_1_1_2020 = 20200101;

    /**
     * @var int
     */
    const DAY_15_1_2020 = 20200115;

    /**
     * @var int
     */
    const DAY_1_2_2020 = 20200201;

    /**
     * @var int
     */
    const DAY_15_2_2020 = 20200215;

    /**
     * @var int
     */
    const DAY_1_3_2020 = 20200301;

    /**
     * @var int
     */
    const DAY_1_4_2020 = 20200401;

    /**
     * @var int
     */
    const DAY_1_5_2020 = 20200501;

    /**
     * @var int
     */
    const DAY_INF = 20303131;

    /**
     * Get empty repository.
     *
     * @param LoopInterface $loop
     *
     * @return UsageRepository
     */
    abstract public function getEmptyRepository(LoopInterface $loop): UsageRepository;

    /**
     * Seconds sleeping before query.
     *
     * @return int
     */
    public function secondsSleepingBeforeQuery(): int
    {
        return 0;
    }

    /**
     * Test.
     */
    public function testUsage()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $this->setUpEnvironment($repository, $loop);

        foreach ($this->getResults() as $result) {
            $this->assertEquals(
                $result[5],
                await($repository->getRegisteredEvents(
                    RepositoryReference::createFromComposed("{$result[1]}_{$result[2]}"),
                    $result[0],
                    \DateTime::createFromFormat('Ymd', \strval($result[3])),
                    \is_null($result[4])
                        ? null
                        : \DateTime::createFromFormat('Ymd', \strval($result[4]))
                ), $loop)
            );
        }
    }

    /**
     * Setup environment.
     *
     * @param UsageRepository $repository
     * @param LoopInterface   $loop
     */
    protected function setUpEnvironment(
        UsageRepository $repository,
        LoopInterface $loop
    ) {
        $this->saveEvents($repository, $loop, $this->getEvents());
    }

    /**
     * @return array
     */
    private function getEvents(): array
    {
        return [
            ['q', 'a1', 'i1', static::DAY_1_1_2020, 20],
            ['q', 'a1', 'i1', static::DAY_1_1_2020, 43],
            ['q', 'a1', 'i1', static::DAY_15_1_2020, 12],
            ['q', 'a2', 'i2', static::DAY_15_1_2020, 23],
            ['q', 'a1', 'i1', static::DAY_1_2_2020, 34],
            ['q', 'a1', 'i1', static::DAY_1_2_2020, 45],
            ['q', 'a2', 'i2', static::DAY_1_2_2020, 23],
            ['q', 'a1', 'i1', static::DAY_15_2_2020, 54],
            ['q', 'a1', 'i1', static::DAY_15_2_2020, 23],
            ['q', 'a2', 'i2', static::DAY_1_4_2020, 34],
            ['q', 'a1', 'i1', static::DAY_1_5_2020, 45],
            ['q', 'a1', 'i1', static::DAY_1_5_2020, 67],
            ['q', 'a2', 'i2', static::DAY_1_5_2020, 23],
            ['q', 'a3', 'i3', static::DAY_1_5_2020, 1],
            ['q', 'a1', 'i1', static::DAY_1_5_2020, 34],
            ['q', 'a1', 'i1', static::DAY_1_5_2020, 45],
            ['q', 'a1', 'i1', static::DAY_1_5_2020, 12],
            ['q', 'a2', 'i2', static::DAY_1_5_2020, 54],

            ['ii', 'a1', 'i1', static::DAY_15_1_2020, 23],
            ['ii', 'a1', 'i1', static::DAY_15_1_2020, 443],
            ['ii', 'a2', 'i3', static::DAY_15_1_2020, 12],
            ['ii', 'a2', 'i3', static::DAY_1_1_2020, 43],
            ['ii', 'a1', 'i1', static::DAY_1_2_2020, 64],
            ['ii', 'a1', 'i1', static::DAY_1_2_2020, 25],
            ['ii', 'a2', 'i2', static::DAY_15_2_2020, 253],
            ['ii', 'a1', 'i3', static::DAY_15_2_2020, 523],
            ['ii', 'a1', 'i1', static::DAY_1_3_2020, 3],
            ['ii', 'a2', 'i3', static::DAY_1_4_2020, 5],
            ['ii', 'a2', 'i3', static::DAY_1_5_2020, 77],
            ['ii', 'a1', 'i1', static::DAY_1_5_2020, 22],
            ['ii', 'a1', 'i3', static::DAY_1_5_2020, 88],
            ['ii', 'a2', 'i3', static::DAY_1_5_2020, 33],
            ['ii', 'a1', 'i3', static::DAY_1_5_2020, 88],
            ['ii', 'a1', 'i3', static::DAY_1_5_2020, 2],
            ['ii', 'a2', 'i1', static::DAY_1_5_2020, 7],
            ['ii', 'a2', 'i1', static::DAY_1_5_2020, 44],

            ['x', 'a9', 'i1', static::DAY_1_1_2020, 1],
        ];
    }

    /**
     * @return array
     */
    private function getResults(): array
    {
        return [
            [null, 'a1', null, static::DAY_31_12_2019, null, ['q' => 434, 'ii' => 1281]],
            [null, 'a1', '', static::DAY_31_12_2019, null, ['q' => 434, 'ii' => 1281]],
            [null, 'a1', '*', static::DAY_31_12_2019, null, ['q' => 434, 'ii' => 1281]],
            [null, 'a1', null, static::DAY_MINUS_INF, static::DAY_INF, ['q' => 434, 'ii' => 1281]],
            ['q', 'a1', null, static::DAY_31_12_2019, null, ['q' => 434]],
            ['ii', 'a1', null, static::DAY_31_12_2019, null, ['ii' => 1281]],
            ['ñ', 'a1', null, static::DAY_31_12_2019, null, []],

            [null, 'a1', 'i1', static::DAY_31_12_2019, null, ['q' => 434, 'ii' => 580]],
            [null, 'a1', 'i3', static::DAY_31_12_2019, null, ['ii' => 701]],

            [null, 'a1', null, static::DAY_1_2_2020, static::DAY_1_4_2020, ['q' => 156, 'ii' => 615]],
            ['q', 'a1', null, static::DAY_1_2_2020, static::DAY_1_4_2020, ['q' => 156]],
            ['ii', 'a1', null, static::DAY_1_2_2020, static::DAY_1_4_2020, ['ii' => 615]],

            [null, 'a1', null, static::DAY_INF, null, []],
            [null, 'a1', null, static::DAY_INF, static::DAY_INF + 10000, []],
            [null, 'a1', null, static::DAY_MINUS_INF, static::DAY_31_12_2019, []],
            [null, null, null, static::DAY_31_12_2019, null, []],

            [null, 'a3', null, static::DAY_31_12_2019, null, ['q' => 1]],

            [null, 'a1', null, static::DAY_15_1_2020 - 1, static::DAY_15_1_2020 + 1, ['q' => 12, 'ii' => 466]],
            [null, 'a1', null, static::DAY_15_1_2020, static::DAY_15_1_2020 + 1, ['q' => 12, 'ii' => 466]],
            [null, 'a1', null, static::DAY_15_1_2020 - 2, static::DAY_15_1_2020 - 1, []],

            [null, '*', '*', static::DAY_MINUS_INF, static::DAY_INF, ['q' => 592, 'ii' => 1755, 'x' => 1]],
            [null, '*', '*', static::DAY_1_2_2020, static::DAY_INF, ['q' => 494, 'ii' => 1234]],
            [null, '*', '*', static::DAY_MINUS_INF, static::DAY_1_2_2020, ['q' => 98, 'ii' => 521, 'x' => 1]],
        ];
    }

    /**
     * Test per day.
     */
    public function testPerDay()
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $this->saveEvents($repository, $loop, $this->getEventsPerDay());

        foreach ($this->getResultsPerDay() as $result) {
            $this->assertEquals(
                $result[5],
                await($repository->getRegisteredEvents(
                    RepositoryReference::createFromComposed("{$result[1]}_{$result[2]}"),
                    $result[0],
                    \DateTime::createFromFormat('Ymd', \strval($result[3])),
                    \is_null($result[4])
                        ? null
                        : \DateTime::createFromFormat('Ymd', \strval($result[4])),
                    true
                ), $loop)
            );
        }
    }

    /**
     * @return array
     */
    private function getEventsPerDay(): array
    {
        return [
            ['q', 'a1', 'i1', static::DAY_1_1_2020 + 1, 20],
            ['q', 'a1', 'i1', static::DAY_1_1_2020 + 1, 43],
            ['ii', 'a1', 'i1', static::DAY_1_1_2020 + 1, 22],
            ['ii', 'a1', 'i2', static::DAY_1_1_2020 + 1, 23],
            ['q', 'a1', 'i2', static::DAY_1_1_2020 + 1, 4],
            ['q', 'a2', 'i2', static::DAY_1_1_2020 + 1, 4],
            ['q', 'a1', 'i1', static::DAY_1_1_2020 + 2, 20],
            ['q', 'a1', 'i1', static::DAY_1_1_2020 + 2, 25],
            ['ii', 'a1', 'i2', static::DAY_1_1_2020 + 2, 11],
            ['q', 'a1', 'i1', static::DAY_1_1_2020 + 4, 1],
            ['q', 'a1', 'i1', static::DAY_1_1_2020 + 4, 2],
            ['q', 'a1', 'i1', static::DAY_1_1_2020 + 4, 100],
            ['q', 'a2', 'i2', static::DAY_1_1_2020 + 4, 89],
            ['ii', 'a1', 'i2', static::DAY_1_1_2020 + 20, 89],
        ];
    }

    /**
     * @return array
     */
    private function getResultsPerDay(): array
    {
        return [
            ['q', 'a1', null, static::DAY_1_1_2020, static::DAY_1_1_2020 + 31, [
                static::DAY_1_1_2020 + 1 => ['q' => 67],
                static::DAY_1_1_2020 + 2 * 1 => ['q' => 45],
                static::DAY_1_1_2020 + 4 * 1 => ['q' => 103],
            ]],
            [null, 'a1', null, static::DAY_1_1_2020, static::DAY_1_1_2020 + 31, [
                static::DAY_1_1_2020 + 1 => ['q' => 67, 'ii' => 45],
                static::DAY_1_1_2020 + 2 => ['q' => 45, 'ii' => 11],
                static::DAY_1_1_2020 + 4 => ['q' => 103],
                static::DAY_1_1_2020 + 20 => ['ii' => 89],
            ]],
            [null, 'a1', 'i2', static::DAY_1_1_2020, static::DAY_1_1_2020 + 31, [
                static::DAY_1_1_2020 + 1 => ['q' => 4, 'ii' => 23],
                static::DAY_1_1_2020 + 2 => ['ii' => 11],
                static::DAY_1_1_2020 + 20 => ['ii' => 89],
            ]],
            [null, 'a1', 'i2', static::DAY_1_1_2020, null, [
                static::DAY_1_1_2020 + 1 => ['q' => 4, 'ii' => 23],
                static::DAY_1_1_2020 + 2 => ['ii' => 11],
                static::DAY_1_1_2020 + 20 => ['ii' => 89],
            ]],
            [null, 'a1', 'i2', static::DAY_MINUS_INF, static::DAY_INF, [
                static::DAY_1_1_2020 + 1 => ['q' => 4, 'ii' => 23],
                static::DAY_1_1_2020 + 2 => ['ii' => 11],
                static::DAY_1_1_2020 + 20 => ['ii' => 89],
            ]],
        ];
    }

    /**
     * Save events.
     *
     * @param UsageRepository $repository
     * @param LoopInterface   $loop
     * @param array           $events
     */
    private function saveEvents(
        UsageRepository $repository,
        LoopInterface $loop,
        array $events
    ) {
        foreach ($events as $event) {
            for ($i = 0; $i < $event[4]; ++$i) {
                $repositoryReference = \is_null($event[2])
                    ? RepositoryReference::create(
                        AppUUID::createById($event[1])
                    )
                    : RepositoryReference::create(
                        AppUUID::createById($event[1]),
                        IndexUUID::createById($event[2])
                    );

                await($repository->registerEvent(
                    $repositoryReference,
                    $event[0],
                    \DateTime::createFromFormat('Ymd', \strval($event[3]))
                ), $loop);
            }
        }

        $seconds = $this->secondsSleepingBeforeQuery();
        if ($seconds > 0) {
            await(\Drift\React\sleep($seconds, $loop), $loop);
        }
    }
}
