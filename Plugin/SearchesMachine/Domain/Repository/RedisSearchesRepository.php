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

namespace Apisearch\Plugin\SearchesMachine\Domain\Repository;

use Apisearch\Plugin\DBAL\Domain\SearchesRepository\DBALSearchesRepository;
use Apisearch\Plugin\SearchesMachine\Domain\SearchTransformer;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\SearchesRepository\PersistentSearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesFilter;
use Clue\React\Redis\Client;
use DateTime;
use React\Promise\PromiseInterface;

class RedisSearchesRepository implements PersistentSearchesRepository
{
    private DBALSearchesRepository $originalSearchesRepository;
    private Client $redisClient;
    private string $redisKey;

    /**
     * @param DBALSearchesRepository $originalSearchesRepository
     * @param Client                 $redisClient
     * @param string                 $redisKey
     */
    public function __construct(
        DBALSearchesRepository $originalSearchesRepository,
        Client $redisClient,
        string $redisKey
    ) {
        $this->originalSearchesRepository = $originalSearchesRepository;
        $this->redisClient = $redisClient;
        $this->redisKey = $redisKey;
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
            ->redisClient
            ->rPush($this->redisKey, SearchTransformer::toString(
                $repositoryReference,
                $userUUID,
                $searchText,
                $numberOfResults,
                $origin,
                $when
            ));
    }

    /**
     * @param SearchesFilter $filter
     *
     * @return PromiseInterface
     */
    public function getRegisteredSearches(SearchesFilter $filter): PromiseInterface
    {
        return $this
            ->originalSearchesRepository
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
            ->originalSearchesRepository
            ->getTopSearches($filter, $n);
    }
}
