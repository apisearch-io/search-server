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

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\DeleteIndex;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DeleteIndexController.
 */
class DeleteIndexController extends ControllerWithCommandBus
{
    /**
     * Delete the index.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $indexUUID = RequestAccessor::getIndexUUIDFromRequest($request);

        return $this
            ->commandBus
            ->execute(new DeleteIndex(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    $indexUUID
                ),
                RequestAccessor::getTokenFromRequest($request),
                $indexUUID
            ))
            ->then(function () {
                return new JsonResponse('Index deleted', $this->created());
            });
    }
}
