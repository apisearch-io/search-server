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

namespace Apisearch\Plugin\DBAL\Domain\UsageRepository;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\UsageRepository\TemporaryUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UseLine;
use Clue\React\Mq\Queue;
use DateTime;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class ChunkUsageRepository.
 */
class ChunkUsageRepository implements UsageRepository
{
    /**
     * @var TemporaryUsageRepository
     */
    private $temporaryUsageRepository;

    /**
     * @var UsageRepository
     */
    private $persistentUsageRepository;

    /**
     * @param TemporaryUsageRepository $temporaryUsageRepository
     * @param UsageRepository          $persistentUsageRepository
     * @param LoopInterface            $loop
     * @param int                      $loopPushInterval
     */
    public function __construct(
        TemporaryUsageRepository $temporaryUsageRepository,
        UsageRepository $persistentUsageRepository,
        LoopInterface $loop,
        int $loopPushInterval
    ) {
        $this->temporaryUsageRepository = $temporaryUsageRepository;
        $this->persistentUsageRepository = $persistentUsageRepository;
        $loop->addPeriodicTimer($loopPushInterval, function () {
            $this->flush();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function registerEvent(
        RepositoryReference $repositoryReference,
        string $eventName,
        DateTime $when,
        int $n = 1
    ): PromiseInterface {
        return $this
            ->temporaryUsageRepository
            ->registerEvent(
                $repositoryReference,
                $eventName,
                $when,
                $n
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getRegisteredEvents(
        RepositoryReference $repositoryReference,
        ?string $eventType,
        DateTime $from,
        ?DateTime $to = null,
        bool $perDay = false
    ): PromiseInterface {
        return $this
            ->persistentUsageRepository
            ->getRegisteredEvents(
                $repositoryReference,
                $eventType,
                $from,
                $to,
                $perDay
            );
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $useLines = $this
            ->temporaryUsageRepository
            ->getAndResetUseLines();

        return Queue::all(3, $useLines, function (UseLine $useLine) {
            return $this
                ->persistentUsageRepository
                ->registerEvent(
                    RepositoryReference::createFromComposed("{$useLine->getAppUUID()}_{$useLine->getIndexUUID()}"),
                    $useLine->getEvent(),
                    $useLine->getWhen(),
                    $useLine->getN()
                );
        });
    }
}
