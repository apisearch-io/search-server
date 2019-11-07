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

use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class DummyTokenRepository.
 */
class DummyTokenRepository implements TokenRepository
{
    /**
     * Add token.
     *
     * @param Token               $token
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function addToken(
        RepositoryReference $repositoryReference,
        Token $token
    ): PromiseInterface {
        return new FulfilledPromise(null);
    }

    /**
     * Delete token.
     *
     * @param TokenUUID           $tokenUUID
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function deleteToken(
        RepositoryReference $repositoryReference,
        TokenUUID $tokenUUID
    ): PromiseInterface {
        return new FulfilledPromise(null);
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
        return new FulfilledPromise([]);
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
        return new FulfilledPromise(null);
    }
}
