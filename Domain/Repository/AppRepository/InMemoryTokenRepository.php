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
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class InMemoryTokenRepository.
 */
class InMemoryTokenRepository extends TokenRepository
{
    /**
     * @var array
     *
     * Stored tokens
     */
    private $storedTokens = [];

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
        $appUUIDComposed = $repositoryReference
            ->getAppUUID()
            ->composeUUID();

        if (!isset($this->storedTokens[$appUUIDComposed])) {
            $this->storedTokens[$appUUIDComposed] = [];
        }

        $this->storedTokens[$appUUIDComposed][$token->getTokenUUID()->composeUUID()] = $token;

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
        $appUUIDComposed = $repositoryReference
            ->getAppUUID()
            ->composeUUID();

        if (!isset($this->storedTokens[$appUUIDComposed])) {
            return resolve();
        }

        unset($this->storedTokens[$appUUIDComposed][$tokenUUID->composeUUID()]);

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
        $appUUIDComposed = $repositoryReference
            ->getAppUUID()
            ->composeUUID();

        unset($this->storedTokens[$appUUIDComposed]);

        return resolve();
    }

    /**
     * {@inheritdoc}
     */
    public function findAllTokens(): PromiseInterface
    {
        $allTokens = [];
        foreach ($this->storedTokens as $_ => $tokens) {
            $allTokens = \array_merge(
                $allTokens,
                $tokens
            );
        }

        return resolve($allTokens);
    }

    /**
     * Flush all tokens.
     */
    public function truncate()
    {
        $this->storedTokens = [];
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
