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

namespace Apisearch\Server\Domain\Repository\AppRepository;

use Apisearch\Config\Config;
use Apisearch\Exception\ResourceExistsException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use React\Promise\PromiseInterface;

/**
 * Interface IndexRepository.
 */
interface IndexRepository
{
    /**
     * Get indices.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface<Index[]>
     */
    public function getIndices(RepositoryReference $repositoryReference): PromiseInterface;

    /**
     * Create an index.
     *
     * @param RepositoryReference $repositoryReference
     * @param IndexUUID           $indexUUID
     * @param Config              $config
     *
     * @return PromiseInterface
     *
     * @throws ResourceExistsException
     */
    public function createIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID,
        Config $config
    ): PromiseInterface;

    /**
     * Config the index.
     *
     * @param RepositoryReference $repositoryReference
     * @param IndexUUID           $indexUUID
     * @param Config              $config
     *
     * @return PromiseInterface
     *
     * @throws ResourceNotAvailableException
     */
    public function configureIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID,
        Config $config
    ): PromiseInterface;

    /**
     * Delete an index.
     *
     * @param RepositoryReference $repositoryReference
     * @param IndexUUID           $indexUUID
     *
     * @return PromiseInterface
     *
     * @throws ResourceNotAvailableException
     */
    public function deleteIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID
    ): PromiseInterface;

    /**
     * Reset the index.
     *
     * @param RepositoryReference $repositoryReference
     * @param IndexUUID           $indexUUID
     *
     * @return PromiseInterface
     *
     * @throws ResourceNotAvailableException
     */
    public function resetIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID
    ): PromiseInterface;
}
