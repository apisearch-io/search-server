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

use Apisearch\Server\Domain\Query\GetIndices;
use Apisearch\Server\Domain\WithAppRepository;
use React\Promise\PromiseInterface;

/**
 * Class GetIndicesHandler.
 */
class GetIndicesHandler extends WithAppRepository
{
    /**
     * Get indices handler method.
     *
     * @param GetIndices $getIndices
     *
     * @return PromiseInterface<Index[]>
     */
    public function handle(GetIndices $getIndices): PromiseInterface
    {
        return $this
            ->appRepository
            ->getIndices($getIndices->getRepositoryReference());
    }
}
