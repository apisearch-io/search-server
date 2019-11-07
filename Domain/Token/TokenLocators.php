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

use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class TokenLocators.
 */
class TokenLocators
{
    /**
     * @var TokenLocator[]
     *
     * Token locators
     */
    private $tokenLocators = [];

    /**
     * Add token locator.
     *
     * @param TokenLocator $tokenLocator
     */
    public function addTokenLocator(TokenLocator $tokenLocator)
    {
        $this->tokenLocators[] = $tokenLocator;
    }

    /**
     * Get valid token locators.
     *
     * @return PromiseInterface<TokenLocator[]>
     */
    public function getValidTokenLocators(): PromiseInterface
    {
        $promise = new FulfilledPromise([]);
        foreach ($this->tokenLocators as $tokenLocator) {
            $promise = $promise->then(function (array $tokenLocators) use ($tokenLocator) {
                if ($tokenLocator->isValid()) {
                    $tokenLocators[] = $tokenLocator;
                }

                return $tokenLocators;
            });
        }

        return $promise;
    }
}
