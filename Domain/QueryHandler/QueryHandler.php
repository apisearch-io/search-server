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

use Apisearch\Model\Item;
use Apisearch\Query\Query as ModelQuery;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Event\QueryWasMade;
use Apisearch\Server\Domain\Query\Query;
use Apisearch\Server\Domain\WithRepositoryAndEventPublisher;
use Ramsey\Uuid\Uuid;
use React\Promise\PromiseInterface;

/**
 * Class QueryHandler.
 */
class QueryHandler extends WithRepositoryAndEventPublisher
{
    /**
     * @param Query $query
     *
     * @return PromiseInterface<Result>
     */
    public function handle(Query $query): PromiseInterface
    {
        $repositoryReference = $query->getRepositoryReference();
        $searchQuery = $query->getQuery();
        $ownerToken = $query->getToken();
        $this->assignUUIDIfNeeded($query->getQuery());
        $from = \microtime(true);

        return $this
            ->repository
            ->query(
                $repositoryReference,
                $searchQuery
            )
            ->then(function (Result $result) use ($from, $repositoryReference, $searchQuery, $query, $ownerToken) {
                return $this
                    ->eventBus
                    ->dispatch(
                        (new QueryWasMade(
                            $searchQuery->getQueryText(),
                            $searchQuery->getSize(),
                            \array_map(function (Item $item) {
                                return $item->getUUID();
                            }, $result->getItems()),
                            $query->getUserId(),
                            \json_encode($query->getQuery()->toArray()),
                            $query->getOrigin(),
                            $query->getParameters(),
                            (int) ((\microtime(true) - $from) * 1000)
                        ))
                            ->withRepositoryReference($repositoryReference)
                            ->dispatchedBy($ownerToken)
                    )
                    ->then(function () use ($result) {
                        return $result;
                    });
            });
    }

    /**
     * Add UUID into query if needed.
     *
     * @param ModelQuery $query
     *
     * @return void
     */
    private function assignUUIDIfNeeded(ModelQuery $query): void
    {
        if (empty($query->getUUID())) {
            $query->identifyWith(Uuid::uuid4()->toString());
        }

        foreach ($query->getSubqueries() as $subquery) {
            $this->assignUUIDIfNeeded($subquery);
        }
    }
}
