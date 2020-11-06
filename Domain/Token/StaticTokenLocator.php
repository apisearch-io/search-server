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

use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Server\Domain\Endpoints;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class StaticTokenLocator.
 */
class StaticTokenLocator implements TokenLocator, TokenProvider
{
    private string $godToken;
    private string $readonlyToken;
    private string $healthCheckToken;
    private string $pingToken;

    /**
     * @param string $godToken
     * @param string $readonlyToken
     * @param string $healthCheckToken
     * @param string $pingToken
     */
    public function __construct(
        string $godToken,
        string $readonlyToken,
        string $healthCheckToken,
        string $pingToken
    ) {
        $this->godToken = $godToken;
        $this->readonlyToken = $readonlyToken;
        $this->healthCheckToken = $healthCheckToken;
        $this->pingToken = $pingToken;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * @param AppUUID   $appUUID
     * @param TokenUUID $tokenUUID
     *
     * @return PromiseInterface<Token|null>
     */
    public function getTokenByUUID(
        AppUUID $appUUID,
        TokenUUID $tokenUUID
    ): PromiseInterface {
        if (
            !empty($this->godToken) &&
            $tokenUUID->composeUUID() === $this->godToken
        ) {
            return resolve($this->createGodToken($appUUID));
        }

        if (
            !empty($this->readonlyToken) &&
            $tokenUUID->composeUUID() === $this->readonlyToken
        ) {
            return resolve($this->createReadOnlyToken($appUUID));
        }

        if (
            !empty($this->healthCheckToken) &&
            $tokenUUID->composeUUID() === $this->healthCheckToken
        ) {
            return resolve($this->createHealthCheckToken($appUUID));
        }

        if (
            !empty($this->pingToken) &&
            $tokenUUID->composeUUID() === $this->pingToken
        ) {
            return resolve($this->createPingToken($appUUID));
        }

        return resolve();
    }

    /**
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
     * @param AppUUID $appUUID
     *
     * @return Token
     */
    private function createHealthCheckToken(AppUUID $appUUID): Token
    {
        return new Token(
            TokenUUID::createById($this->healthCheckToken),
            $appUUID,
            [],
            [
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
     * Create ping token instance.
     *
     * @param AppUUID $appUUID
     *
     * @return Token
     */
    private function createPingToken(AppUUID $appUUID): Token
    {
        return new Token(
            TokenUUID::createById($this->pingToken),
            $appUUID,
            [],
            [
                'ping', // Ping
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
            $tokens[] = $this->createPingToken($appUUID);
        }

        return resolve($tokens);
    }
}
