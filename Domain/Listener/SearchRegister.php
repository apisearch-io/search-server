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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SearchRegister.
 */
class SearchRegister implements EventSubscriberInterface
{
    /**
     * @var SearchesRepository
     */
    private $searchesRepository;

    /**
     * @param SearchesRepository $searchesRepository
     */
    public function __construct(SearchesRepository $searchesRepository)
    {
        $this->searchesRepository = $searchesRepository;
    }

    /**
     * @param DomainEventEnvelope $domainEventEnvelope
     */
    public function registerSearch(DomainEventEnvelope $domainEventEnvelope)
    {
        /**
         * @var QueryWasMade
         */
        $queryWasMade = $domainEventEnvelope->getDomainEvent();
        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        $this
            ->searchesRepository
            ->registerSearch(
                $queryWasMade->getRepositoryReference(),
                $queryWasMade->getUser()
                    ? $queryWasMade->getUser()->getId()
                    : '',
                $queryWasMade->getQueryText(),
                \count($queryWasMade->getItemsUUID()),
                $queryWasMade->getOrigin(),
                $today
            );
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
