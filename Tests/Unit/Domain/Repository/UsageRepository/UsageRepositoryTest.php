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
    const TIMESTAMP_MINUS_INF = 1291161600;

    /**
     * @var int
     */
    const TIMESTAMP_31_12_2019 = 1577750400;

    /**
     * @var int
     */
    const TIMESTAMP_1_1_2020 = 1577836800;

    /**
     * @var int
     */
    const TIMESTAMP_15_1_2020 = 1579046400;

    /**
     * @var int
     */
    const TIMESTAMP_1_2_2020 = 1580515200;

    /**
     * @var int
     */
    const TIMESTAMP_15_2_2020 = 1581724800;

    /**
     * @var int
     */
    const TIMESTAMP_1_3_2020 = 1583020800;

    /**
     * @var int
     */
    const TIMESTAMP_1_4_2020 = 1585699200;

    /**
     * @var int
     */
    const TIMESTAMP_1_5_2020 = 1588291200;

    /**
     * @var int
     */
    const TIMESTAMP_INF = 1991161600;

    /**
     * @var int
     */
    const DAY = 86400;

    /**
     * @var int
     */
    const HALF_DAY = self::DAY / 2;

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
                    \DateTime::createFromFormat('U', \strval($result[3])),
                    \is_null($result[4])
                        ? null
                        : \DateTime::createFromFormat('U', \strval($result[4]))
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
            ['q', 'a1', 'i1', static::TIMESTAMP_1_1_2020, 20],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_1_2020, 43],
            ['q', 'a1', 'i1', static::TIMESTAMP_15_1_2020 + self::HALF_DAY, 12],
            ['q', 'a2', 'i2', static::TIMESTAMP_15_1_2020, 23],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_2_2020, 34],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_2_2020, 45],
            ['q', 'a2', 'i2', static::TIMESTAMP_1_2_2020, 23],
            ['q', 'a1', 'i1', static::TIMESTAMP_15_2_2020 + self::HALF_DAY, 54],
            ['q', 'a1', 'i1', static::TIMESTAMP_15_2_2020, 23],
            ['q', 'a2', 'i2', static::TIMESTAMP_1_4_2020, 34],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_5_2020, 45],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_5_2020, 67],
            ['q', 'a2', 'i2', static::TIMESTAMP_1_5_2020, 23],
            ['q', 'a3', 'i3', static::TIMESTAMP_1_5_2020 + self::HALF_DAY, 1],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_5_2020, 34],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_5_2020, 45],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_5_2020, 12],
            ['q', 'a2', 'i2', static::TIMESTAMP_1_5_2020, 54],

            ['ii', 'a1', 'i1', static::TIMESTAMP_15_1_2020, 23],
            ['ii', 'a1', 'i1', static::TIMESTAMP_15_1_2020, 443],
            ['ii', 'a2', 'i3', static::TIMESTAMP_15_1_2020 + self::HALF_DAY, 12],
            ['ii', 'a2', 'i3', static::TIMESTAMP_1_1_2020, 43],
            ['ii', 'a1', 'i1', static::TIMESTAMP_1_2_2020, 64],
            ['ii', 'a1', 'i1', static::TIMESTAMP_1_2_2020, 25],
            ['ii', 'a2', 'i2', static::TIMESTAMP_15_2_2020, 253],
            ['ii', 'a1', 'i3', static::TIMESTAMP_15_2_2020, 523],
            ['ii', 'a1', 'i1', static::TIMESTAMP_1_3_2020 + self::HALF_DAY, 3],
            ['ii', 'a2', 'i3', static::TIMESTAMP_1_4_2020, 5],
            ['ii', 'a2', 'i3', static::TIMESTAMP_1_5_2020, 77],
            ['ii', 'a1', 'i1', static::TIMESTAMP_1_5_2020, 22],
            ['ii', 'a1', 'i3', static::TIMESTAMP_1_5_2020, 88],
            ['ii', 'a2', 'i3', static::TIMESTAMP_1_5_2020, 33],
            ['ii', 'a1', 'i3', static::TIMESTAMP_1_5_2020 + self::HALF_DAY, 88],
            ['ii', 'a1', 'i3', static::TIMESTAMP_1_5_2020, 2],
            ['ii', 'a2', 'i1', static::TIMESTAMP_1_5_2020, 7],
            ['ii', 'a2', 'i1', static::TIMESTAMP_1_5_2020 + self::HALF_DAY, 44],

            ['x', 'a9', 'i1', static::TIMESTAMP_1_1_2020, 1],
        ];
    }

    /**
     * @return array
     */
    private function getResults(): array
    {
        return [
            [null, 'a1', null, static::TIMESTAMP_31_12_2019, null, ['q' => 434, 'ii' => 1281]],
            [null, 'a1', '', static::TIMESTAMP_31_12_2019, null, ['q' => 434, 'ii' => 1281]],
            [null, 'a1', '*', static::TIMESTAMP_31_12_2019, null, ['q' => 434, 'ii' => 1281]],
            [null, 'a1', null, static::TIMESTAMP_MINUS_INF, static::TIMESTAMP_INF, ['q' => 434, 'ii' => 1281]],
            ['q', 'a1', null, static::TIMESTAMP_31_12_2019, null, ['q' => 434]],
            ['ii', 'a1', null, static::TIMESTAMP_31_12_2019, null, ['ii' => 1281]],
            ['ñ', 'a1', null, static::TIMESTAMP_31_12_2019, null, []],

            [null, 'a1', 'i1', static::TIMESTAMP_31_12_2019, null, ['q' => 434, 'ii' => 580]],
            [null, 'a1', 'i3', static::TIMESTAMP_31_12_2019, null, ['ii' => 701]],

            [null, 'a1', null, static::TIMESTAMP_1_2_2020, static::TIMESTAMP_1_4_2020, ['q' => 156, 'ii' => 615]],
            ['q', 'a1', null, static::TIMESTAMP_1_2_2020, static::TIMESTAMP_1_4_2020, ['q' => 156]],
            ['ii', 'a1', null, static::TIMESTAMP_1_2_2020, static::TIMESTAMP_1_4_2020, ['ii' => 615]],

            [null, 'a1', null, static::TIMESTAMP_INF, null, []],
            [null, 'a1', null, static::TIMESTAMP_INF, static::TIMESTAMP_INF + 10000, []],
            [null, 'a1', null, static::TIMESTAMP_MINUS_INF, static::TIMESTAMP_31_12_2019, []],
            [null, null, null, static::TIMESTAMP_31_12_2019, null, []],

            [null, 'a3', null, static::TIMESTAMP_31_12_2019, null, ['q' => 1]],

            [null, 'a1', null, static::TIMESTAMP_15_1_2020 - static::DAY, static::TIMESTAMP_15_1_2020 + self::DAY, ['q' => 12, 'ii' => 466]],
            [null, 'a1', null, static::TIMESTAMP_15_1_2020, static::TIMESTAMP_15_1_2020 + 1, ['q' => 12, 'ii' => 466]],
            [null, 'a1', null, static::TIMESTAMP_15_1_2020 - static::DAY - static::DAY, static::TIMESTAMP_15_1_2020 - self::DAY, []],
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
                    \DateTime::createFromFormat('U', \strval($result[3])),
                    \is_null($result[4])
                        ? null
                        : \DateTime::createFromFormat('U', \strval($result[4])),
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
            ['q', 'a1', 'i1', static::TIMESTAMP_1_1_2020 + static::DAY, 20],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_1_2020 + static::DAY, 43],
            ['ii', 'a1', 'i1', static::TIMESTAMP_1_1_2020 + static::DAY, 22],
            ['ii', 'a1', 'i2', static::TIMESTAMP_1_1_2020 + static::DAY, 23],
            ['q', 'a1', 'i2', static::TIMESTAMP_1_1_2020 + static::DAY, 4],
            ['q', 'a2', 'i2', static::TIMESTAMP_1_1_2020 + static::DAY, 4],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_1_2020 + 2 * static::DAY, 20],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_1_2020 + 2 * static::DAY, 25],
            ['ii', 'a1', 'i2', static::TIMESTAMP_1_1_2020 + 2 * static::DAY, 11],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_1_2020 + 4 * static::DAY, 1],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_1_2020 + 4 * static::DAY, 2],
            ['q', 'a1', 'i1', static::TIMESTAMP_1_1_2020 + 4 * static::DAY, 100],
            ['q', 'a2', 'i2', static::TIMESTAMP_1_1_2020 + 4 * static::DAY, 89],
            ['ii', 'a1', 'i2', static::TIMESTAMP_1_1_2020 + 20 * static::DAY, 89],
        ];
    }

    /**
     * @return array
     */
    private function getResultsPerDay(): array
    {
        return [
            ['q', 'a1', null, static::TIMESTAMP_1_1_2020, static::TIMESTAMP_1_1_2020 + 31 * self::DAY, [
                static::TIMESTAMP_1_1_2020 + static::DAY => ['q' => 67],
                static::TIMESTAMP_1_1_2020 + 2 * static::DAY => ['q' => 45],
                static::TIMESTAMP_1_1_2020 + 4 * static::DAY => ['q' => 103],
            ]],
            [null, 'a1', null, static::TIMESTAMP_1_1_2020, static::TIMESTAMP_1_1_2020 + 31 * self::DAY, [
                static::TIMESTAMP_1_1_2020 + static::DAY => ['q' => 67, 'ii' => 45],
                static::TIMESTAMP_1_1_2020 + 2 * static::DAY => ['q' => 45, 'ii' => 11],
                static::TIMESTAMP_1_1_2020 + 4 * static::DAY => ['q' => 103],
                static::TIMESTAMP_1_1_2020 + 20 * static::DAY => ['ii' => 89],
            ]],
            [null, 'a1', 'i2', static::TIMESTAMP_1_1_2020, static::TIMESTAMP_1_1_2020 + 31 * self::DAY, [
                static::TIMESTAMP_1_1_2020 + static::DAY => ['q' => 4, 'ii' => 23],
                static::TIMESTAMP_1_1_2020 + 2 * static::DAY => ['ii' => 11],
                static::TIMESTAMP_1_1_2020 + 20 * static::DAY => ['ii' => 89],
            ]],
            [null, 'a1', 'i2', static::TIMESTAMP_1_1_2020, null, [
                static::TIMESTAMP_1_1_2020 + static::DAY => ['q' => 4, 'ii' => 23],
                static::TIMESTAMP_1_1_2020 + 2 * static::DAY => ['ii' => 11],
                static::TIMESTAMP_1_1_2020 + 20 * static::DAY => ['ii' => 89],
            ]],
            [null, 'a1', 'i2', static::TIMESTAMP_MINUS_INF, static::TIMESTAMP_INF, [
                static::TIMESTAMP_1_1_2020 + static::DAY => ['q' => 4, 'ii' => 23],
                static::TIMESTAMP_1_1_2020 + 2 * static::DAY => ['ii' => 11],
                static::TIMESTAMP_1_1_2020 + 20 * static::DAY => ['ii' => 89],
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
                    \DateTime::createFromFormat('U', \strval($event[3]))
                ), $loop);
            }
        }

        $seconds = $this->secondsSleepingBeforeQuery();
        if ($seconds > 0) {
            await(\Drift\React\sleep($seconds, $loop), $loop);
        }
    }
}
