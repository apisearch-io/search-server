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
use Apisearch\Server\Domain\Query\GetSimilarItems;
use Apisearch\Server\Domain\Repository\Repository\Repository;
use React\Promise\PromiseInterface;

/**
 * Class GetSimilarItemsHandler.
 */
class GetSimilarItemsHandler
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
     * @param GetSimilarItems $getSimilarItems
     *
     * @return PromiseInterface<Result>
     */
    public function handle(GetSimilarItems $getSimilarItems): PromiseInterface
    {
        return $this
            ->repository
            ->querySimilar(
                $getSimilarItems->getRepositoryReference(),
                $getSimilarItems->getQuery(),
                $getSimilarItems->getItemsUUID()
            );
    }
}
