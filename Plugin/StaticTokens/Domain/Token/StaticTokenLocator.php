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

namespace Apisearch\Plugin\StaticTokens\Domain\Token;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Server\Domain\Token\TokenLocator;
use Apisearch\Server\Domain\Token\TokenProvider;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class TokenRedisRepository.
 */
class StaticTokenLocator implements TokenLocator, TokenProvider
{
    /**
     * @var Token[]
     *
     * Tokens
     */
    private $tokens = [];

    /**
     * TokenRedisRepository constructor.
     *
     * @param array[] $tokensAsArray
     */
    public function __construct(array $tokensAsArray)
    {
        $this->tokens = array_values(
            array_map(function (array $tokenAsArray) {
                $tokenAsArray['uuid'] = TokenUUID::createById($tokenAsArray['uuid'])->toArray();
                $tokenAsArray['app_uuid'] = AppUUID::createById($tokenAsArray['app_uuid'])->toArray();
                $tokenAsArray['indices'] = array_map(function (string $indexId) {
                    return IndexUUID::createById($indexId)->toArray();
                }, $tokenAsArray['indices']);
                unset($tokenAsArray['app_id']);
                $tokenAsArray['created_at'] = null;
                $tokenAsArray['updated_at'] = null;
                $tokenAsArray['metadata'] = $tokenAsArray['metadata'] ?? [];
                $tokenAsArray['metadata']['read_only'] = true;

                return Token::createFromArray($tokenAsArray);
            }, $tokensAsArray)
        );
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
        $tokens = array_values(
            array_filter(
                $this->tokens,
                function (Token $token) use ($appUUID, $tokenUUID) {
                    return
                        (
                            empty($appUUID->composeUUID()) ||
                            $token->getAppUUID()->composeUUID() === $appUUID->composeUUID()
                        ) &&
                        $token->getTokenUUID()->composeUUID() === $tokenUUID->composeUUID();
                }
            )
        );

        return new FulfilledPromise(empty($tokens)
            ? null
            : $tokens[0]
        );
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
        return new FulfilledPromise(array_values(
            array_filter(
                $this->tokens,
                function (Token $token) use ($appUUID) {
                    return $token->getAppUUID()->composeUUID() === $appUUID->composeUUID();
                }
            )
        ));
    }
}
