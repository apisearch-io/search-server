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

namespace Apisearch\Plugin\DBAL\Domain\SearchesRepository;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\ImperativeEvent\FlushSearches;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\SearchesRepository\PersistentSearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesFilter;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\TemporarySearchesRepository;
use Clue\React\Mq\Queue;
use DateTime;
use Drift\HttpKernel\AsyncKernelEvents;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ChunkSearchesRepository.
 */
final class ChunkSearchesRepository implements SearchesRepository, EventSubscriberInterface
{
    private TemporarySearchesRepository $temporarySearchesRepository;
    private PersistentSearchesRepository $persistentSearchesRepository;
    private LoopInterface $loop;

    /**
     * @param TemporarySearchesRepository  $temporarySearchesRepository
     * @param PersistentSearchesRepository $persistentSearchesRepository
     * @param LoopInterface                $loop
     */
    public function __construct(
        TemporarySearchesRepository $temporarySearchesRepository,
        PersistentSearchesRepository $persistentSearchesRepository,
        LoopInterface $loop
    ) {
        $this->temporarySearchesRepository = $temporarySearchesRepository;
        $this->persistentSearchesRepository = $persistentSearchesRepository;
        $this->loop = $loop;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $userUUID
     * @param string              $searchText
     * @param int                 $numberOfResults
     * @param Origin              $origin
     * @param DateTime            $when
     *
     * @return PromiseInterface
     */
    public function registerSearch(
        RepositoryReference $repositoryReference,
        string $userUUID,
        string $searchText,
        int $numberOfResults,
        Origin $origin,
        DateTime $when
    ): PromiseInterface {
        return $this
            ->temporarySearchesRepository
            ->registerSearch(
                $repositoryReference,
                $userUUID,
                $searchText,
                $numberOfResults,
                $origin,
                $when
            );
    }

    /**
     * @param SearchesFilter $filter
     *
     * @return PromiseInterface
     */
    public function getRegisteredSearches(SearchesFilter $filter): PromiseInterface
    {
        return $this
            ->persistentSearchesRepository
            ->getRegisteredSearches($filter);
    }

    /**
     * @param SearchesFilter $filter
     * @param int            $n
     *
     * @return PromiseInterface
     */
    public function getTopSearches(SearchesFilter $filter, int $n): PromiseInterface
    {
        return $this
            ->persistentSearchesRepository
            ->getTopSearches($filter, $n);
    }

    /**
     * Flush.
     *
     * @return PromiseInterface
     */
    public function flush(): PromiseInterface
    {
        $searches = $this
            ->temporarySearchesRepository
            ->getAndResetSearches();

        $finished = new Deferred();

        $this->loop->futureTick(function () use ($searches, $finished) {
            return
                Queue::all(5, $searches, function ($search) {
                    return $this
                        ->persistentSearchesRepository
                        ->registerSearch(
                            RepositoryReference::createFromComposed("{$search->getAppUUID()}_{$search->getIndexUUID()}"),
                            $search->getUser(),
                            $search->getText(),
                            $search->getNumberOfResults(),
                            new Origin(
                                $search->getHost(),
                                $search->getIp(),
                                $search->getPlatform()
                            ),
                            $search->getWhen()
                        );
                })
                ->then(function () use ($finished) {
                    $finished->resolve();
                });
        });

        return $finished->promise();
    }

    /**
     * @return array|void
     */
    public static function getSubscribedEvents()
    {
        return [
            FlushSearches::class => 'flush',
            AsyncKernelEvents::SHUTDOWN => 'flush',
        ];
    }
}
