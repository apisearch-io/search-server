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

/**
 * Interface TemporarySearchesRepository.
 */
interface TemporarySearchesRepository extends SearchesRepository
{
    /**
     * Get stored searches and reset.
     *
     * @return Search[]
     */
    public function getAndResetSearches(): array;
}
