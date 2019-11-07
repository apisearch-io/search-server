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

namespace Apisearch\Server\Domain\Token;

use Apisearch\Http\Endpoints;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class StaticTokenLocator.
 */
class StaticTokenLocator implements TokenLocator, TokenProvider
{
    /**
     * @var string
     *
     * God token
     */
    private $godToken;

    /**
     * @var string
     *
     * Readonly token
     */
    private $readonlyToken;

    /**
     * @var string
     *
     * Ping token
     */
    private $pingToken;

    /**
     * TokenValidator constructor.
     *
     * @param string $godToken
     * @param string $readonlyToken
     * @param string $pingToken
     */
    public function __construct(
        string $godToken,
        string $readonlyToken,
        string $pingToken
    ) {
        $this->godToken = $godToken;
        $this->readonlyToken = $readonlyToken;
        $this->pingToken = $pingToken;
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
        if ($tokenUUID->composeUUID() === $this->godToken) {
            return new FulfilledPromise($this->createGodToken($appUUID));
        }

        if (
            !empty($this->readonlyToken) &&
            $tokenUUID->composeUUID() === $this->readonlyToken
        ) {
            return new FulfilledPromise($this->createReadOnlyToken($appUUID));
        }

        if (
            !empty($this->pingToken) &&
            $tokenUUID->composeUUID() === $this->pingToken
        ) {
            return new FulfilledPromise($this->createPingToken());
        }

        return new FulfilledPromise();
    }

    /**
     * Create god token instance.
     *
     * @param AppUUID $appUUID
     *
     * @return Token
     */
    private function createGodToken(AppUUID $appUUID): Token
    {
        return new Token(
            TokenUUID::createById($this->godToken),
            $appUUID,
            [],
            [],
            [],
            Token::NO_CACHE,
            [
                'read_only' => true,
            ]
        );
    }

    /**
     * Create read only token instance.
     *
     * @param AppUUID $appUUID
     *
     * @return Token
     */
    private function createReadOnlyToken(AppUUID $appUUID): Token
    {
        return new Token(
            TokenUUID::createById($this->readonlyToken),
            $appUUID,
            [],
            Endpoints::queryOnly(),
            [],
            Token::DEFAULT_TTL,
            [
                'read_only' => true,
            ]
        );
    }

    /**
     * Create ping token instance.
     *
     * @return Token
     */
    private function createPingToken(): Token
    {
        return new Token(
            TokenUUID::createById($this->pingToken),
            AppUUID::createById(''),
            [],
            [
                'ping', // Ping
                'check_health', // Check health
            ],
            [],
            Token::NO_CACHE,
            [
                'read_only' => true,
            ]
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
        $tokens = [$this->createGodToken($appUUID)];

        if (!empty($this->readonlyToken)) {
            $tokens[] = $this->createReadOnlyToken($appUUID);
        }

        if (!empty($this->pingToken)) {
            $tokens[] = $this->createPingToken();
        }

        return new FulfilledPromise($tokens);
    }
}
