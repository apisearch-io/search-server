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

namespace Apisearch\Plugin\Security\Domain\Token;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Server\Domain\Token\TokenValidator;
use Carbon\Carbon;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class SecondsValidTokenValidator.
 */
class SecondsValidTokenValidator implements TokenValidator
{
    /**
     * Validate token given basic fields.
     *
     * If is valid, return valid Token
     *
     * @param AppUUID   $appUUID
     * @param IndexUUID $indexUUID
     * @param Token     $token
     * @param string    $referrer
     * @param string    $routeName
     * @param string[]  $routeTags
     *
     * @return PromiseInterface<bool>
     */
    public function isTokenValid(
        Token $token,
        AppUUID $appUUID,
        IndexUUID $indexUUID,
        string $referrer,
        string $routeName,
        array $routeTags
    ): PromiseInterface {
        $secondsValid = $token->getMetadataValue('seconds_valid', 0);

        return resolve(
            0 === $secondsValid ||
            $token->getUpdatedAt() + $secondsValid >= Carbon::now('UTC')->timestamp
        );
    }
}
