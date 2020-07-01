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

namespace Apisearch\Server\Domain\Repository;

use Apisearch\Repository\RepositoryReference;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class NoItemsInMemoryRepository.
 */
class NoItemsInMemoryRepository extends InMemoryRepository
{
    /**
     * {@inheritdoc}
     */
    public function addItems(
        RepositoryReference $repositoryReference,
        array $items
    ): PromiseInterface {
        return resolve()
            ->then(function () use ($repositoryReference, $items) {
                $appUUID = $repositoryReference->getAppUUID();
                $appUUIDComposed = $appUUID->composeUUID();
                $indexUUID = $repositoryReference->getIndexUUID();
                $indexUUIDComposed = $indexUUID->composeUUID();
                $this->throwExceptionIfNonExistingIndex($appUUIDComposed, $indexUUIDComposed);
            });
    }
}
