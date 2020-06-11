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

use Apisearch\Server\Domain\Query\GetSearches;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesFilter;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use React\Promise\PromiseInterface;

/**
 * Class GetSearchesHandler.
 */
class GetSearchesHandler
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
     * @param GetSearches $getSearches
     *
     * @return PromiseInterface
     */
    public function handle(GetSearches $getSearches): PromiseInterface
    {
        return $this
            ->searchesRepository
            ->getRegisteredSearches(
                SearchesFilter::create($getSearches->getRepositoryReference())
                    ->perDay($getSearches->isPerDay())
                    ->from($getSearches->getFrom())
                    ->to($getSearches->getTo())
                    ->byUser($getSearches->getUser())
                    ->byPlatform($getSearches->getPlatform())
                    ->excludeWithResults($getSearches->withResultsAreExcluded())
                    ->excludeWithoutResults($getSearches->withoutResultsAreExcluded())
                    ->count($getSearches->getCount())
            );
    }
}
