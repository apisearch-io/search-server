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

/**
 * Class TokenValidators.
 */
class TokenValidators
{
    /**
     * @var TokenValidator[]
     *
     * Token validator
     */
    private $tokenValidators = [];

    /**
     * Add token validator.
     *
     * @param TokenValidator $tokenValidator
     *
     * @return void
     */
    public function addTokenValidator(TokenValidator $tokenValidator): void
    {
        $this->tokenValidators[] = $tokenValidator;
    }

    /**
     * Get TokenValidators.
     *
     * @return TokenValidator[]
     */
    public function getTokenValidators(): array
    {
        return $this->tokenValidators;
    }
}
