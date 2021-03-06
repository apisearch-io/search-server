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

namespace Apisearch\Server\Domain\Repository\SearchesRepository;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use DateTime;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class EmptySearchesRepository.
 */
class EmptySearchesRepository implements SearchesRepository, TemporarySearchesRepository
{
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
        return resolve();
    }

    /**
     * @param SearchesFilter $filter
     *
     * @return PromiseInterface
     */
    public function getRegisteredSearches(SearchesFilter $filter): PromiseInterface
    {
        $perDay = $filter->isPerDay();

        return resolve($perDay ? [] : 0);
    }

    /**
     * @param SearchesFilter $filter
     * @param int            $n
     *
     * @return PromiseInterface
     */
    public function getTopSearches(
        SearchesFilter $filter,
        int $n
    ): PromiseInterface {
        return resolve([]);
    }

    /**
     * @return array
     */
    public function getAndResetSearches(): array
    {
        return [];
    }
}
