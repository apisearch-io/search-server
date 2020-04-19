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

use Apisearch\Server\Domain\Query\CheckIndex;
use Apisearch\Server\Domain\WithAppRepository;
use React\Promise\PromiseInterface;

/**
 * Class CheckIndexHandler.
 */
class CheckIndexHandler extends WithAppRepository
{
    /**
     * @param CheckIndex $checkIndex
     *
     * @return PromiseInterface<bool>
     */
    public function handle(CheckIndex $checkIndex): PromiseInterface
    {
        return $this
            ->appRepository
            ->checkIndex(
                $checkIndex->getRepositoryReference(),
                $checkIndex->getIndexUUID()
            );
    }
}
