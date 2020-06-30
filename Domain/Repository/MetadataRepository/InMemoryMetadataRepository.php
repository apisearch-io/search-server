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

namespace Apisearch\Server\Domain\Repository\MetadataRepository;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\ResetableRepository;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class InMemoryMetadataRepository.
 */
class InMemoryMetadataRepository extends MetadataRepository implements ResetableRepository
{
    /**
     * @var array
     */
    protected $storedMetadata = [];

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $key
     * @param mixed               $value
     *
     * @return PromiseInterface
     */
    public function set(
        RepositoryReference $repositoryReference,
        string $key,
        $value
    ): PromiseInterface {
        $composedRepositoryReference = $repositoryReference->compose();
        if (!\array_key_exists($composedRepositoryReference, $this->storedMetadata)) {
            $this->storedMetadata[$composedRepositoryReference] = [];
        }

        $this->storedMetadata[$composedRepositoryReference][$key] = $value;

        return resolve();
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $key
     *
     * @return PromiseInterface
     */
    public function delete(RepositoryReference $repositoryReference, string $key): PromiseInterface
    {
        $composedRepositoryReference = $repositoryReference->compose();

        if (
            \array_key_exists($composedRepositoryReference, $this->storedMetadata) &&
            \array_key_exists($key, $this->storedMetadata[$composedRepositoryReference])
        ) {
            unset($this->storedMetadata[$composedRepositoryReference][$key]);
        }

        return resolve();
    }

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function findMetadata(RepositoryReference $repositoryReference): PromiseInterface
    {
        $composedRepositoryReference = $repositoryReference->compose();

        return resolve($this->storedMetadata[$composedRepositoryReference] ?? []);
    }

    /**
     * Find all metadata from repository reference.
     *
     * @return PromiseInterface
     */
    public function findAllMetadata(): PromiseInterface
    {
        return resolve($this->storedMetadata);
    }

    /**
     * @param PromiseInterface
     */
    public function reset(): PromiseInterface
    {
        $this->storedMetadata = [];

        return $this->forceLoadAllMetadata();
    }
}
