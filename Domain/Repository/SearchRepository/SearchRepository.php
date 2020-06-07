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

namespace Apisearch\Server\Domain\Repository\SearchRepository;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Event\QueryWasMade;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Interface SearchRepository.
 */
interface SearchRepository
{
    /**
     * @param RepositoryReference $repositoryReference
     * @param QueryWasMade        $query
     * @param DateTime            $when
     *
     * @return PromiseInterface
     */
    public function registerQuery(
        RepositoryReference $repositoryReference,
        QueryWasMade $query,
        DateTime $when
    ): PromiseInterface;
}
