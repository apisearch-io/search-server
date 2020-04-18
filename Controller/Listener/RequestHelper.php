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

namespace Apisearch\Server\Controller\Listener;

use Apisearch\Exception\InvalidTokenException;
use Apisearch\Http\Http;
use Apisearch\Model\Token;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RequestHelper.
 */
class RequestHelper
{
    /**
     * Get token string from request.
     *
     * @param Request $request
     *
     * @return string
     *
     * @throws InvalidTokenException
     */
    public static function getTokenStringFromRequest(Request $request): string
    {
        $query = $request->query;
        $headers = $request->headers;
        $token = $headers->get(
            Http::TOKEN_ID_HEADER,
            $query->get(
                Http::TOKEN_FIELD,
                ''
            )
        );

        if (\is_null($token)) {
            throw InvalidTokenException::createInvalidTokenPermissions('');
        }

        return $token instanceof Token
            ? $token->getTokenUUID()->composeUUID()
            : $token;
    }
}
