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
use Apisearch\Server\Domain\ImperativeEvent\FlushUsageLines;
use Apisearch\Server\Domain\Repository\UsageRepository\TemporaryUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Clue\React\Mq\Queue;
use DateTime;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ChunkUsageRepository.
 */
class ChunkUsageRepository implements UsageRepository, EventSubscriberInterface
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
     * @var LoopInterface
     */
    private $loop;

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
        $this->loop = $loop;
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

        $this->loop->futureTick(function () use ($useLines) {
            return Queue::all(1, $useLines, function ($useLine) {
                return $this
                    ->persistentUsageRepository
                    ->registerEvent(
                        RepositoryReference::createFromComposed("{$useLine->getAppUUID()}_{$useLine->getIndexUUID()}"),
                        $useLine->getEvent(),
                        $useLine->getWhen(),
                        $useLine->getN()
                    );
            });
        });
    }

    /**
     * @return array|void
     */
    public static function getSubscribedEvents()
    {
        return [
            FlushUsageLines::class => [
                ['flush', 0],
            ],
        ];
    }
}
