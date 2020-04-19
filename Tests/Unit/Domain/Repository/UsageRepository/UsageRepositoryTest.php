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
     * @var string
     */
    const TIMESTAMP_1_1_2020 = 1577836800;

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
        $events = $this->getEvents();
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);

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
                    \DateTime::createFromFormat('U', \strval(self::TIMESTAMP_1_1_2020 + $event[3]))
                ), $loop);
            }
        }

        $seconds = $this->secondsSleepingBeforeQuery();
        if ($seconds > 0) {
            await(\Drift\React\sleep($seconds, $loop), $loop);
        }

        foreach ($this->getResults() as $result) {
            $this->assertEquals(
                $result[5],
                await($repository->getRegisteredEvents(
                    RepositoryReference::createFromComposed("{$result[1]}_{$result[2]}"),
                    $result[0],
                    \DateTime::createFromFormat('U', \strval(self::TIMESTAMP_1_1_2020 + $result[3])),
                    \is_null($result[4])
                        ? null
                        : \DateTime::createFromFormat('U', \strval(self::TIMESTAMP_1_1_2020 + $result[4]))
                ), $loop)
            );
        }
    }

    /**
     * @return array
     */
    private function getEvents(): array
    {
        return [
            ['q', 'a1', 'i1', 1, 20],
            ['q', 'a1', 'i1', 1, 43],
            ['q', 'a1', 'i1', 1, 12],
            ['q', 'a2', 'i2', 1, 23],
            ['q', 'a1', 'i1', 2, 34],
            ['q', 'a1', 'i1', 2, 45],
            ['q', 'a2', 'i2', 2, 23],
            ['q', 'a1', 'i1', 2, 54],
            ['q', 'a1', 'i1', 2, 23],
            ['q', 'a2', 'i2', 4, 34],
            ['q', 'a1', 'i1', 5, 45],
            ['q', 'a1', 'i1', 5, 67],
            ['q', 'a2', 'i2', 5, 23],
            ['q', 'a3', 'i3', 5, 1],
            ['q', 'a1', 'i1', 5, 34],
            ['q', 'a1', 'i1', 5, 45],
            ['q', 'a1', 'i1', 5, 12],
            ['q', 'a2', 'i2', 5, 54],

            ['ii', 'a1', 'i1', 1, 23],
            ['ii', 'a1', 'i1', 1, 443],
            ['ii', 'a2', 'i3', 1, 12],
            ['ii', 'a2', 'i3', 1, 43],
            ['ii', 'a1', 'i1', 2, 64],
            ['ii', 'a1', 'i1', 2, 25],
            ['ii', 'a2', 'i2', 2, 253],
            ['ii', 'a1', 'i3', 2, 523],
            ['ii', 'a1', 'i1', 3, 3],
            ['ii', 'a2', 'i3', 4, 5],
            ['ii', 'a2', 'i3', 5, 77],
            ['ii', 'a1', 'i1', 5, 22],
            ['ii', 'a1', 'i3', 5, 88],
            ['ii', 'a2', 'i3', 5, 33],
            ['ii', 'a1', 'i3', 5, 88],
            ['ii', 'a1', 'i3', 5, 2],
            ['ii', 'a2', 'i1', 5, 7],
            ['ii', 'a2', 'i1', 5, 44],

            ['x', 'a9', 'i1', 1, 1],
        ];
    }

    /**
     * @return array
     */
    private function getResults(): array
    {
        return [
            [null, 'a1', null, 0, null, ['q' => 434, 'ii' => 1281]],
            [null, 'a1', '', 0, null, ['q' => 434, 'ii' => 1281]],
            [null, 'a1', '*', 0, null, ['q' => 434, 'ii' => 1281]],
            [null, 'a1', null, -10, 10, ['q' => 434, 'ii' => 1281]],
            ['q', 'a1', null, 0, null, ['q' => 434]],
            ['ii', 'a1', null, 0, null, ['ii' => 1281]],
            ['ñ', 'a1', null, 0, null, []],

            [null, 'a1', 'i1', 0, null, ['q' => 434, 'ii' => 580]],
            [null, 'a1', 'i3', 0, null, ['ii' => 701]],

            [null, 'a1', null, 2, 4, ['q' => 156, 'ii' => 615]],
            ['q', 'a1', null, 2, 4, ['q' => 156]],
            ['ii', 'a1', null, 2, 4, ['ii' => 615]],

            [null, 'a1', null, 6, null, []],
            [null, 'a1', null, 6, 10, []],
            [null, 'a1', null, -10, 0, []],
            [null, null, null, 0, null, []],

            [null, 'a3', null, 0, null, ['q' => 1]],
        ];
    }
}
