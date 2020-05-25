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

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\DeleteItemsByQuery;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DeleteItemsByQueryController.
 */
class DeleteItemsByQueryController extends ControllerWithCommandBus
{
    /**
     * Delete items.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     *
     * @throws InvalidFormatException
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $queryModel = RequestAccessor::extractQuery($request);

        return $this
            ->execute(new DeleteItemsByQuery(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                $queryModel
            ))
            ->then(function () {
                return new JsonResponse('Items deleted by query', 200);
            });
    }
}
