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

use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\ResetableRepository;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class EmptyTokenRepository.
 */
class EmptyTokenRepository extends TokenRepository implements ResetableRepository
{
    /**
     * Add token.
     *
     * @param Token               $token
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function putToken(
        RepositoryReference $repositoryReference,
        Token $token
    ): PromiseInterface {

        return resolve();
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

        return resolve();
    }
    /**
     * Get tokens.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return Token[]
     */
    public function getTokens(RepositoryReference $repositoryReference): array
    {
        return [];
    }

    /**
     * Get token by uuid.
     *
     * @param AppUUID   $appUUID
     * @param TokenUUID $tokenUUID
     *
     * @return PromiseInterface
     */
    public function getTokenByUUID(
        AppUUID $appUUID,
        TokenUUID $tokenUUID
    ): PromiseInterface {

        return resolve();
    }

    /**
     * Get tokens by AppUUID.
     *
     * @param AppUUID $appUUID
     *
     * @return PromiseInterface
     */
    public function getTokensByAppUUID(AppUUID $appUUID): PromiseInterface
    {
        return resolve();
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
        return resolve();
    }

    /**
     * Force load all tokens.
     *
     * @return PromiseInterface
     */
    public function forceLoadAllTokens(): PromiseInterface
    {
        return resolve();
    }

    /**
     * {@inheritdoc}
     */
    public function findAllTokens(): PromiseInterface
    {
        return resolve([]);
    }

    /**
     * @return PromiseInterface
     */
    public function reset(): PromiseInterface
    {
        return resolve();
    }

    /**
     * Locator is enabled.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
