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
use Apisearch\Server\Domain\Token\TokenLocator;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class InMemoryTokenRepository.
 */
class InMemoryTokenRepository implements TokenRepository, TokenLocator
{
    /**
     * @var array[]
     *
     * Tokens
     */
    private $tokens = [];

    /**
     * Locator is enabled.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }

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
        $appUUIDComposed = $repositoryReference
            ->getAppUUID()
            ->composeUUID();

        if (!isset($this->tokens[$appUUIDComposed])) {
            $this->tokens[$appUUIDComposed] = [];
        }

        $this->tokens[$appUUIDComposed][$token->getTokenUUID()->composeUUID()] = $token;

        return new FulfilledPromise();
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
        $appUUIDComposed = $repositoryReference
            ->getAppUUID()
            ->composeUUID();

        if (!isset($this->tokens[$appUUIDComposed])) {
            return new FulfilledPromise();
        }

        unset($this->tokens[$appUUIDComposed][$tokenUUID->composeUUID()]);

        return new FulfilledPromise();
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
        $appUUIDComposed = $repositoryReference
            ->getAppUUID()
            ->composeUUID();

        return new FulfilledPromise($this->tokens[$appUUIDComposed] ?? []);
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
        $appUUIDComposed = $repositoryReference
            ->getAppUUID()
            ->composeUUID();

        unset($this->tokens[$appUUIDComposed]);

        return new FulfilledPromise();
    }

    /**
     * Get token by uuid.
     *
     * @param AppUUID   $appUUID
     * @param TokenUUID $tokenUUID
     *
     * @return PromiseInterface<Token|null>
     */
    public function getTokenByUUID(
        AppUUID $appUUID,
        TokenUUID $tokenUUID
    ): PromiseInterface {
        $appUUIDComposed = $appUUID->composeUUID();

        return new FulfilledPromise(
            isset($this->tokens[$appUUIDComposed])
                 ? $this->tokens[$appUUIDComposed][$tokenUUID->composeUUID()] ?? null
                 : null
        );
    }
}
