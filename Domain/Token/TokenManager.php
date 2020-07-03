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

use Apisearch\Exception\InvalidTokenException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class TokenManager.
 */
class TokenManager
{
    /**
     * @var TokenLocators
     *
     * Token locators
     */
    private $tokenLocators;

    /**
     * @var TokenValidators
     *
     * Token validators
     */
    private $tokenValidators;

    /**
     * TokenManager constructor.
     *
     * @param TokenLocators   $tokenLocators
     * @param TokenValidators $tokenValidators
     */
    public function __construct(
        TokenLocators $tokenLocators,
        TokenValidators $tokenValidators
    ) {
        $this->tokenLocators = $tokenLocators;
        $this->tokenValidators = $tokenValidators;
    }

    /**
     * Find and validate token given basic fields.
     *
     * If is valid, return valid Token
     *
     * @param AppUUID   $appUUID
     * @param IndexUUID $indexUUID
     * @param TokenUUID $tokenUUID
     * @param string    $referrer
     * @param string    $routeName
     * @param string[]  $routeTags
     *
     * @return PromiseInterface<Token>
     */
    public function checkToken(
        AppUUID $appUUID,
        IndexUUID $indexUUID,
        TokenUUID $tokenUUID,
        string $referrer,
        string $routeName,
        array $routeTags
    ): PromiseInterface {
        return $this
            ->locateTokenByUUID($appUUID, $tokenUUID)
            ->then(function ($token) use ($appUUID, $tokenUUID, $indexUUID, $referrer, $routeName, $routeTags) {
                return $this
                    ->isTokenValid(
                        $token,
                        $appUUID,
                        $indexUUID,
                        $referrer,
                        $routeName,
                        $routeTags
                    )
                    ->then(function (bool $isValid) use ($tokenUUID, $token) {
                        if (!$isValid) {
                            throw InvalidTokenException::createInvalidTokenPermissions($tokenUUID->composeUUID());
                        }

                        return $token;
                    });
            });
    }

    /**
     * Locate token by UUID.
     *
     * @param AppUUID   $appUUID
     * @param TokenUUID $tokenUUID
     *
     * @return PromiseInterface<Token|null>
     */
    private function locateTokenByUUID(
        AppUUID $appUUID,
        TokenUUID $tokenUUID
    ): PromiseInterface {
        return $this
            ->tokenLocators
            ->getValidTokenLocators()
            ->then(function (array $tokenLocators) use ($appUUID, $tokenUUID) {
                $promise = resolve();

                foreach ($tokenLocators as $tokenLocator) {
                    $promise = $promise
                        ->then(function ($token) use ($tokenLocator, $appUUID, $tokenUUID) {
                            if ($token instanceof Token) {
                                return $token;
                            }

                            return $tokenLocator
                                ->getTokenByUUID(
                                    $appUUID,
                                    $tokenUUID
                                );
                        });
                }

                return $promise;
            });
    }

    /**
     * Validate token given basic fields.
     *
     * If is valid, return valid Token
     *
     * @param Token|null $token
     * @param AppUUID    $appUUID
     * @param IndexUUID  $indexUUID
     * @param string     $referrer
     * @param string     $routeName
     * @param string[]   $routeTags
     *
     * @return PromiseInterface<bool>
     */
    public function isTokenValid(
        ?Token $token,
        AppUUID $appUUID,
        IndexUUID $indexUUID,
        string $referrer,
        string $routeName,
        array $routeTags
    ): PromiseInterface {
        if (\is_null($token)) {
            return resolve(false);
        }

        $tokenValidators = $this
            ->tokenValidators
            ->getTokenValidators();

        $promise = resolve(true);

        foreach ($tokenValidators as $tokenValidator) {
            $promise = $promise->then(function (bool $isValid) use ($token, $appUUID, $indexUUID, $referrer, $routeName, $routeTags, $tokenValidator) {
                if (!$isValid) {
                    return false;
                }

                return $tokenValidator->isTokenValid(
                    $token,
                    $appUUID,
                    $indexUUID,
                    $referrer,
                    $routeName,
                    $routeTags
                );
            });
        }

        return $promise;
    }
}
