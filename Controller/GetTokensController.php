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

namespace Apisearch\Server\Controller;

use Apisearch\Model\Token;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\GetTokens;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetTokensController.
 */
class GetTokensController extends ControllerWithBus
{
    /**
     * Get tokens.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        return $this
            ->commandBus
            ->handle(new GetTokens(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request)
            ))
            ->then(function (array $tokens) {
                return new JsonResponse(
                    array_map(function (Token $token) {
                        return $token->toArray();
                    }, $tokens),
                    200,
                    [
                        'Access-Control-Allow-Origin' => '*',
                    ]
                );
            });
    }
}
