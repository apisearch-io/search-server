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
use Apisearch\Repository\RepositoryReference;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class InMemoryConfigRepository.
 */
class InMemoryConfigRepository extends ConfigRepository
{
    /**
     * @var array
     */
    private $storedConfigs = [];

    /**
     * Put config
     *
     * @param RepositoryReference $repositoryReference
     * @param Config $config
     *
     * @return PromiseInterface
     */
    public function putConfig(
        RepositoryReference $repositoryReference,
        Config $config
    ): PromiseInterface {
        $repositoryReferenceComposed = $repositoryReference->compose();
        $this->storedConfigs[$repositoryReferenceComposed] = $config;

        return resolve();
    }

    /**
     * Delete config
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function deleteConfig(RepositoryReference $repositoryReference): PromiseInterface
    {
        $repositoryReferenceComposed = $repositoryReference->compose();
        unset($this->storedConfigs[$repositoryReferenceComposed]);

        return resolve();
    }

    /**
     * Find all config.
     *
     * @return PromiseInterface
     */
    public function findAllConfigs(): PromiseInterface
    {
        return resolve($this->storedConfigs);
    }

    /**
     * Flush all tokens.
     */
    public function truncate()
    {
        $this->storedConfigs = [];
    }
}
