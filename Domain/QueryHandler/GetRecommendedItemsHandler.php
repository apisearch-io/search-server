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

use Apisearch\Result\Result;
use Apisearch\Server\Domain\Query\GetRecommendedItems;
use Apisearch\Server\Domain\Repository\Repository\Repository;
use React\Promise\PromiseInterface;

/**
 * Class GetRecommendedItemsHandler.
 */
class GetRecommendedItemsHandler
{
    private Repository $repository;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param GetRecommendedItems $getRecommendedItems
     *
     * @return PromiseInterface<Result>
     */
    public function handle(GetRecommendedItems $getRecommendedItems): PromiseInterface
    {
        return $this
            ->repository
            ->query(
                $getRecommendedItems->getRepositoryReference(),
                $getRecommendedItems->getQuery()
            );
    }
}
