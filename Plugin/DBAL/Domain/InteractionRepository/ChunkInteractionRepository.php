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

namespace Apisearch\Plugin\DBAL\Domain\InteractionRepository;

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\ImperativeEvent\FlushInteractions;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionFilter;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\TemporaryInteractionRepository;
use Clue\React\Mq\Queue;
use DateTime;
use Drift\HttpKernel\AsyncKernelEvents;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ChunkInteractionRepository.
 */
final class ChunkInteractionRepository implements InteractionRepository, EventSubscriberInterface
{
    private TemporaryInteractionRepository $temporaryInteractionRepository;
    private DBALInteractionRepository $persistentInteractionRepository;
    private LoopInterface $loop;

    /**
     * @param TemporaryInteractionRepository $temporaryInteractionRepository
     * @param DBALInteractionRepository      $persistentInteractionRepository
     * @param LoopInterface                  $loop
     * @param int                            $loopPushInterval
     */
    public function __construct(
        TemporaryInteractionRepository $temporaryInteractionRepository,
        DBALInteractionRepository $persistentInteractionRepository,
        LoopInterface $loop,
        int $loopPushInterval
    ) {
        $this->temporaryInteractionRepository = $temporaryInteractionRepository;
        $this->persistentInteractionRepository = $persistentInteractionRepository;
        $this->loop = $loop;
        $loop->addPeriodicTimer($loopPushInterval, function () {
            $this->flush();
        });
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $userUUID
     * @param ItemUUID            $itemUUID
     * @param int                 $position
     * @param Origin              $origin
     * @param string              $type
     * @param DateTime            $when
     *
     * @return PromiseInterface
     */
    public function registerInteraction(
        RepositoryReference $repositoryReference,
        string $userUUID,
        ItemUUID $itemUUID,
        int $position,
        Origin $origin,
        string $type,
        DateTime $when
    ): PromiseInterface {
        return $this
            ->temporaryInteractionRepository
            ->registerInteraction(
                $repositoryReference,
                $userUUID,
                $itemUUID,
                $position,
                $origin,
                $type,
                $when
            );
    }

    /**
     * @param InteractionFilter $filter
     *
     * @return PromiseInterface
     */
    public function getRegisteredInteractions(InteractionFilter $filter): PromiseInterface
    {
        return $this
            ->persistentInteractionRepository
            ->getRegisteredInteractions($filter);
    }

    /**
     * @param InteractionFilter $filter
     * @param int               $n
     *
     * @return PromiseInterface
     */
    public function getTopInteractedItems(
        InteractionFilter $filter,
        int $n
    ): PromiseInterface {
        return $this
            ->persistentInteractionRepository
            ->getTopInteractedItems($filter, $n);
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $interactions = $this
            ->temporaryInteractionRepository
            ->getAndResetInteractions();

        $this->loop->futureTick(function () use ($interactions) {
            return Queue::all(5, $interactions, function ($interaction) {
                /*
                 * @var Interaction $interaction
                 */
                return $this
                    ->persistentInteractionRepository
                    ->registerInteraction(
                        RepositoryReference::createFromComposed("{$interaction->getAppUUID()}_{$interaction->getIndexUUID()}"),
                        $interaction->getUser(),
                        ItemUUID::createByComposedUUID($interaction->getItemUUID()),
                        $interaction->getPosition(),
                        new Origin(
                            $interaction->getHost(),
                            $interaction->getIp(),
                            $interaction->getPlatform()
                        ),
                        $interaction->getType(),
                        $interaction->getWhen()
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
            FlushInteractions::class => 'flush',
            AsyncKernelEvents::SHUTDOWN => 'flush',
        ];
    }
}
