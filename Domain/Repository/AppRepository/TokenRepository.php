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
use Apisearch\Server\Domain\Event\TokensWereDeleted;
use Apisearch\Server\Domain\Event\TokenWasDeleted;
use Apisearch\Server\Domain\Event\TokenWasPut;
use Apisearch\Server\Domain\Token\TokenLocator;
use Apisearch\Server\Domain\Token\TokenProvider;
use Drift\HttpKernel\Event\DomainEventEnvelope;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Interface TokenRepository.
 */
abstract class TokenRepository implements TokenLocator, TokenProvider, EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $tokens = [];

    /**
     * Add token.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     *
     * @return PromiseInterface
     */
    abstract public function addToken(
        RepositoryReference $repositoryReference,
        Token $token
    ): PromiseInterface;

    /**
     * Delete token.
     *
     * @param RepositoryReference $repositoryReference
     * @param TokenUUID           $tokenUUID
     *
     * @return PromiseInterface
     */
    abstract public function deleteToken(
        RepositoryReference $repositoryReference,
        TokenUUID $tokenUUID
    ): PromiseInterface;

    /**
     * Delete all tokens.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    abstract public function deleteTokens(RepositoryReference $repositoryReference): PromiseInterface;

    /**
     * Load all tokens.
     *
     * @param DomainEventEnvelope $event
     *
     * @return PromiseInterface
     */
    public function loadAllTokens(DomainEventEnvelope $event): PromiseInterface
    {
        return $this->forceLoadAllTokens();
    }

    /**
     * Force load all tokens.
     *
     * @return PromiseInterface
     */
    public function forceLoadAllTokens(): PromiseInterface
    {
        return $this
            ->findAllTokens()
            ->then(function (array $allTokens) {
                $this->tokens = [];
                foreach ($allTokens as $token) {
                    $appUUIDComposed = $token->getAppUUID()->composeUUID();
                    $tokenUUIDComposed = $token->getTokenUUID()->composeUUID();

                    if (empty($this->tokens[$appUUIDComposed])) {
                        $this->tokens[$appUUIDComposed] = [];
                    }

                    $this->tokens[$appUUIDComposed][$tokenUUIDComposed] = $token;
                }
            });
    }

    /**
     * Find all tokens.
     *
     * @return PromiseInterface
     */
    abstract public function findAllTokens(): PromiseInterface;

    /**
     * Get tokens.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return array
     */
    public function getTokens(RepositoryReference $repositoryReference): array
    {
        return $this->tokens[$repositoryReference->getAppUUID()->composeUUID()] ?? [];
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
        $appUUIDComposed = $appUUID->composeUUID();
        $tokenUUIDComposed = $tokenUUID->composeUUID();

        return resolve(
            !isset($this->tokens[$appUUIDComposed])
                ? null
                : $this->tokens[$appUUIDComposed][$tokenUUIDComposed] ?? null
        );
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
        $appUUIDComposed = $appUUID->composeUUID();

        return resolve($this->tokens[$appUUIDComposed] ?? []);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            TokensWereDeleted::class => [
                ['loadAllTokens', 0],
            ],
            TokenWasPut::class => [
                ['loadAllTokens', 0],
            ],
            TokenWasDeleted::class => [
                ['loadAllTokens', 0],
            ],
        ];
    }
}
