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

namespace Apisearch\Server\Domain\QueryHandler;

use Apisearch\Server\Domain\Query\GetTopSearches;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesFilter;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use React\Promise\PromiseInterface;

/**
 * Class GetTopSearchesHandler.
 */
class GetTopSearchesHandler
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
     * @param GetTopSearches $getTopSearches
     *
     * @return PromiseInterface
     */
    public function handle(GetTopSearches $getTopSearches): PromiseInterface
    {
        return $this
            ->searchesRepository
            ->getTopSearches(
                SearchesFilter::create($getTopSearches->getRepositoryReference())
                    ->from($getTopSearches->getFrom())
                    ->to($getTopSearches->getTo())
                    ->byPlatform($getTopSearches->getPlatform())
                    ->byUser($getTopSearches->getUser())
                    ->excludeWithResults($getTopSearches->withResultsAreExcluded())
                    ->excludeWithoutResults($getTopSearches->withoutResultsAreExcluded()),
                $getTopSearches->getN() ?? 10
            );
    }
}
