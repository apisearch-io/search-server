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

namespace Apisearch\Plugin\RedisStorage\Domain\Token;

use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Plugin\Redis\Domain\RedisWrapper;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\AppRepository\TokenRepository;
use Apisearch\Server\Domain\Token\TokenLocator;
use Apisearch\Server\Domain\Token\TokenProvider;
use React\Promise\PromiseInterface;

/**
 * Class TokenRedisRepository.
 */
class TokenRedisRepository implements TokenRepository, TokenLocator, TokenProvider
{
    /**
     * Redis hast id.
     *
     * @var string
     */
    const REDIS_KEY = 'apisearch_tokens';

    /**
     * @var RedisWrapper
     *
     * Redis wrapper
     */
    private $redisWrapper;

    /**
     * @var bool
     *
     * Enabled
     */
    private $enabled;

    /**
     * TokenRedisRepository constructor.
     *
     * @param RedisWrapper $redisWrapper
     * @param bool         $enabled
     */
    public function __construct(
        RedisWrapper $redisWrapper,
        bool $enabled
    ) {
        $this->redisWrapper = $redisWrapper;
        $this->enabled = $enabled;
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
            ->redisWrapper
            ->getClient()
            ->hSet(
                $this->composeRedisKey($repositoryReference->getAppUUID()),
                $token->getTokenUUID()->composeUUID(),
                json_encode($token->toArray())
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
            ->redisWrapper
            ->getClient()
            ->hDel(
                $this->composeRedisKey($repositoryReference->getAppUUID()),
                $tokenUUID->composeUUID()
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
        return $this->getTokensByAppUUID($repositoryReference->getAppUUID());
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
            ->redisWrapper
            ->getClient()
            ->del($this->composeRedisKey($repositoryReference->getAppUUID()));
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
        return $this
            ->redisWrapper
            ->getClient()
            ->hGet(
                $this->composeRedisKey($appUUID),
                $tokenUUID->composeUUID()
            )
            ->then(function ($token) {
                return is_string($token)
                    ? Token::createFromArray(json_decode($token, true))
                    : null;
            });
    }

    /**
     * Get tokens by AppUUID.
     *
     * @param AppUUID $appUUID
     *
     * @return PromiseInterface<Token[]>
     */
    public function getTokensByAppUUID(AppUUID $appUUID): PromiseInterface
    {
        return $this
            ->redisWrapper
            ->getClient()
            ->hGetAll($this->composeRedisKey($appUUID))
            ->then(function (array $tokens) {
                $tokens = array_filter($tokens, function (int $key) {
                    return 1 === ($key % 2);
                }, ARRAY_FILTER_USE_KEY);

                return array_map(function (string $token) {
                    return Token::createFromArray(json_decode($token, true));
                }, $tokens);
            });
    }

    /**
     * Locator is enabled.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->enabled;
    }

    /**
     * Get composed redis key.
     *
     * @param AppUUID $appUUID
     *
     * @return string
     */
    private function composeRedisKey(AppUUID $appUUID): string
    {
        return $appUUID->composeUUID().'~~'.self::REDIS_KEY;
    }
}
