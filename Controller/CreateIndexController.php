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

use Apisearch\Config\Config;
use Apisearch\Exception\InvalidFormatException;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\CreateIndex;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CreateIndexController.
 */
class CreateIndexController extends ControllerWithCommandBus
{
    /**
     * Create an index.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $configAsArray = RequestAccessor::extractRequestContentObject(
            $request,
            '',
            InvalidFormatException::configFormatNotValid($request->getContent()),
            []
        );

        return $this
            ->commandBus
            ->execute(new CreateIndex(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                RequestAccessor::getIndexUUIDFromRequest($request),
                Config::createFromArray($configAsArray)
            ))
            ->then(function () {
                return new JsonResponse(
                    'Index created with given configuration',
                    200
                );
            });
    }
}
