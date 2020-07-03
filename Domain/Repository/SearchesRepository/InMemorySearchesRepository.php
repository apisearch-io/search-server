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

use Apisearch\Model\AppUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\ResetableRepository;
use DateTime;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class InMemorySearchesRepository.
 */
class InMemorySearchesRepository implements SearchesRepository, TemporarySearchesRepository, ResetableRepository
{
    /**
     * @var Search[]
     */
    private $searches = [];

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
        $this->searches[] = new Search(
            $userUUID,
            $repositoryReference->getAppUUID()->composeUUID(),
            $repositoryReference->getIndexUUID()->composeUUID(),
            $searchText,
            $numberOfResults,
            0 < $numberOfResults,
            $origin->getIp(),
            $origin->getHost(),
            $origin->getPlatform(),
            $when
        );

        return resolve();
    }

    /**
     * @param SearchesFilter $filter
     *
     * @return PromiseInterface
     */
    public function getRegisteredSearches(SearchesFilter $filter): PromiseInterface
    {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve(0);
        }

        $perDay = $filter->isPerDay();
        $count = $filter->getCount();
        $searches = $perDay ? [] : 0;
        $uniqueUsers = [];

        foreach ($this->searches as $search) {
            $whenFormatted = $search->getWhen()->format('Ymd');

            if (!$this->searchIsValidFromFilter($search, $filter)) {
                continue;
            }

            if ($perDay) {
                if (!\array_key_exists($whenFormatted, $searches)) {
                    $searches[$whenFormatted] = 1;
                    $uniqueUsers[$whenFormatted] = [
                        $search->getUser() => true,
                    ];
                } else {
                    ++$searches[$whenFormatted];
                    $uniqueUsers[$whenFormatted][$search->getUser()] = true;
                }
            } else {
                ++$searches;
                $uniqueUsers[$search->getUser()] = true;
            }
        }

        $uniqueUsers = $perDay
            ? \array_map(function (array $day) {
                return \count($day);
            }, $uniqueUsers)
            : \count($uniqueUsers);

        return resolve(SearchesFilter::UNIQUE_USERS === $count
            ? $uniqueUsers
            : $searches
        );
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
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve(0);
        }

        $searches = \array_filter($this->searches, function (Search $search) use ($filter) {
            return $this->searchIsValidFromFilter($search, $filter);
        });

        $searchesMap = [];
        foreach ($searches as $search) {
            $text = $search->getText();
            \array_key_exists($text, $searchesMap)
                ? $searchesMap[$text]++
                : $searchesMap[$text] = 1;
        }

        \arsort($searchesMap);

        return resolve(\array_slice($searchesMap, 0, $n));
    }

    /**
     * Interaction is valid given a filter.
     *
     * @param Search         $search
     * @param SearchesFilter $filter
     *
     * @return bool
     */
    private function searchIsValidFromFilter(
        Search $search,
        SearchesFilter $filter
    ): bool {
        $whenFormatted = $search->getWhen()->format('Ymd');
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        if (!(
            (
                '*' === $appUUID->composeUUID() ||
                $appUUID->composeUUID() === $search->getAppUUID()
            ) &&
            (
                \is_null($indexUUID) ||
                '' === $indexUUID->composeUUID() ||
                '*' === $indexUUID->composeUUID() ||
                $indexUUID->composeUUID() === $search->getIndexUUID()
            )
        )) {
            return false;
        }

        if (
            !\is_null($filter->getUser()) &&
            $search->getUser() !== $filter->getUser()
        ) {
            return false;
        }

        if (
            !\is_null($filter->getPlatform()) &&
            $search->getPlatform() !== $filter->getPlatform() &&
            (
                Origin::MOBILE !== $filter->getPlatform() ||
                !\in_array($search->getPlatform(), [
                    Origin::TABLET,
                    Origin::PHONE,
                ])
            )
        ) {
            return false;
        }

        if (
            !\is_null($filter->getFrom()) &&
            $whenFormatted < $filter->getFrom()->format('Ymd')
        ) {
            return false;
        }
        if (
            !\is_null($filter->getTo()) &&
            $whenFormatted >= $filter->getTo()->format('Ymd')
        ) {
            return false;
        }

        if ($filter->withResultsAreExcluded() && $search->isWithResults()) {
            return false;
        }

        if ($filter->withoutResultsAreExcluded() && !$search->isWithResults()) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getAndResetSearches(): array
    {
        $searches = $this->searches;
        $this->reset();

        return $searches;
    }

    /**
     * @return PromiseInterface
     */
    public function reset(): PromiseInterface
    {
        $this->searches = [];

        return resolve();
    }
}
