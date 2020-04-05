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

use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\DeleteToken;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DeleteTokenController.
 */
class DeleteTokenController extends ControllerWithCommandBus
{
    /**
     * Delete a token.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $tokenUUID = TokenUUID::createById($request->get('token_id', ''));

        return $this
            ->commandBus
            ->execute(new DeleteToken(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                $tokenUUID
            ))
            ->then(function () {
                return new JsonResponse('Token deleted', 200);
            });
    }
}
