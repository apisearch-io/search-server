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

namespace Apisearch\Plugin\Elasticsearch\Domain\AppRepository;

use Apisearch\Config\Config;
use Apisearch\Exception\ResourceExistsException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\Index;
use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\Elasticsearch\Domain\WithElasticaWrapper;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\AppRepository\IndexRepository as IndexRepositoryInterface;
use Elastica\Exception\ResponseException;
use React\Promise\PromiseInterface;

/**
 * Class IndexRepository.
 */
class IndexRepository extends WithElasticaWrapper implements IndexRepositoryInterface
{
    /**
     * Get indices.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface<Index[]>
     */
    public function getIndices(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->elasticaWrapper
            ->getIndices($repositoryReference)
            ->then(null, function (ResponseException $_) {
                return [];
            });
    }

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
    ): PromiseInterface {
        $newRepositoryReference = $repositoryReference->changeIndex($indexUUID);

        return $this
            ->elasticaWrapper
            ->createIndex(
                $newRepositoryReference,
                $config
            )->then(function ($_) use ($newRepositoryReference, $config) {
                return $this
                    ->elasticaWrapper
                    ->createIndexMapping(
                        $newRepositoryReference,
                        $config
                    );
            });
    }

    /**
     * Delete the index.
     *
     * @param RepositoryReference $repositoryReference
     * @param IndexUUID           $indexUUID
     *
     * @return PromiseInterface
     */
    public function deleteIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID
    ): PromiseInterface {
        return $this
            ->elasticaWrapper
            ->deleteIndex($repositoryReference->changeIndex($indexUUID));
    }

    /**
     * Reset the index.
     *
     * @param RepositoryReference $repositoryReference
     * @param IndexUUID           $indexUUID
     *
     * @return PromiseInterface
     */
    public function resetIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID
    ): PromiseInterface {
        $newRepositoryReference = $repositoryReference->changeIndex($indexUUID);

        return $this
            ->elasticaWrapper
            ->resetIndex($newRepositoryReference);
    }

    /**
     * Configure the index.
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
    ): PromiseInterface {
        $newRepositoryReference = $repositoryReference->changeIndex($indexUUID);

        return $this
            ->elasticaWrapper
            ->configureIndex(
                $newRepositoryReference,
                $config
            );
    }
}
