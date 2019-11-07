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
use Apisearch\Exception\TransportableException;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Token\TokenProviders;
use React\Promise\PromiseInterface;

/**
 * Class Repository.
 */
final class Repository
{
    /**
     * @var IndexRepository
     *
     * Index Repository
     */
    private $indexRepository;

    /**
     * @var TokenRepository
     *
     * Token repository
     */
    private $tokenRepository;

    /**
     * @var TokenProviders
     *
     * Token providers
     */
    private $tokenProviders;

    /**
     * Repository constructor.
     *
     * @param IndexRepository $indexRepository
     * @param TokenRepository $tokenRepository
     * @param TokenProviders  $tokenProviders
     */
    public function __construct(
        IndexRepository $indexRepository,
        TokenRepository $tokenRepository,
        TokenProviders $tokenProviders
    ) {
        $this->indexRepository = $indexRepository;
        $this->tokenRepository = $tokenRepository;
        $this->tokenProviders = $tokenProviders;
    }

    /**
     * Add token.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     *
     * @return PromiseInterface
     */
    public function addToken(
        RepositoryReference $repositoryReference,
        Token $token
    ): PromiseInterface {
        return $this
            ->tokenRepository
            ->addToken(
                $repositoryReference,
                $token
            );
    }

    /**
     * Delete token.
     *
     * @param RepositoryReference $repositoryReference
     * @param TokenUUID           $tokenUUID
     *
     * @return PromiseInterface
     */
    public function deleteToken(
        RepositoryReference $repositoryReference,
        TokenUUID $tokenUUID
    ): PromiseInterface {
        return $this
            ->tokenRepository
            ->deleteToken(
                $repositoryReference,
                $tokenUUID
            );
    }

    /**
     * Get tokens.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface<Token[]>
     */
    public function getTokens(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->tokenProviders
            ->getTokensByAppUUID($repositoryReference->getAppUUID());
    }

    /**
     * Delete all tokens.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function deleteTokens(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->tokenRepository
            ->deleteTokens($repositoryReference);
    }

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
            ->indexRepository
            ->getIndices($repositoryReference);
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
        return $this
            ->indexRepository
            ->createIndex(
                $repositoryReference,
                $indexUUID,
                $config
            );
    }

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
    ): PromiseInterface {
        return $this
            ->indexRepository
            ->deleteIndex(
                $repositoryReference,
                $indexUUID
            );
    }

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
    ): PromiseInterface {
        return $this
            ->indexRepository
            ->resetIndex(
                $repositoryReference,
                $indexUUID
            );
    }

    /**
     * Checks the index.
     *
     * @param RepositoryReference $repositoryReference
     * @param IndexUUID           $indexUUID
     *
     * @return PromiseInterface
     */
    public function checkIndex(
        RepositoryReference $repositoryReference,
        IndexUUID $indexUUID
    ): PromiseInterface {
        return $this
            ->indexRepository
            ->getIndices($repositoryReference)
            ->then(function (array $indices) use ($indexUUID) {
                foreach ($indices as $index) {
                    if (
                        $index->getUUID()->composeUUID() == $indexUUID->composeUUID() &&
                        $index->isOK()
                    ) {
                        return true;
                    }
                }

                return false;
            }, function (TransportableException $_) {
                return false;
            });
    }

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
    ): PromiseInterface {
        return $this
            ->indexRepository
            ->configureIndex(
                $repositoryReference,
                $indexUUID,
                $config
            );
    }
}
