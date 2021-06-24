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

namespace Apisearch\Server\Domain\Listener;

use Apisearch\Server\Domain\Event\QueryWasMade;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use Drift\HttpKernel\Event\DomainEventEnvelope;
use React\EventLoop\LoopInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SearchRegister.
 */
class SearchRegister implements EventSubscriberInterface
{
    private SearchesRepository $searchesRepository;
    private LoopInterface $loop;

    /**
     * @param SearchesRepository $searchesRepository
     * @param LoopInterface      $loop
     */
    public function __construct(
        SearchesRepository $searchesRepository,
        LoopInterface $loop
    ) {
        $this->searchesRepository = $searchesRepository;
        $this->loop = $loop;
    }

    /**
     * @param DomainEventEnvelope $domainEventEnvelope
     *
     * @return void
     */
    public function registerSearch(DomainEventEnvelope $domainEventEnvelope)
    {
        /**
         * @var QueryWasMade
         */
        $queryWasMade = $domainEventEnvelope->getDomainEvent();
        $userId = $queryWasMade->getUserId();
        if (\is_null($userId)) {
            return;
        }

        $origin = $queryWasMade->getOrigin();
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        if (empty($queryWasMade->getQueryText())) {
            return;
        }

        $this
            ->loop
            ->futureTick(function () use ($queryWasMade, $userId, $origin, $today) {
                $this
                    ->searchesRepository
                    ->registerSearch(
                        $queryWasMade->getRepositoryReference(),
                        $userId,
                        $queryWasMade->getQueryText(),
                        \count($queryWasMade->getItemsUUID()),
                        $origin,
                        $today
                    );
            });
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            QueryWasMade::class => [
                ['registerSearch', 0],
            ],
        ];
    }
}
