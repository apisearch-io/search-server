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

namespace Apisearch\Server\Http;

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Model\Item;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\PatchItems;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PatchItemsController.
 */
final class PatchItemsController extends ControllerWithCommandBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface
     *
     * @throws InvalidFormatException
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $itemsAsArray = RequestAccessor::extractRequestContentObject(
            $request,
            '',
            InvalidFormatException::itemRepresentationNotValid($request->getContent()),
            []
        );

        return $this
            ->execute(new PatchItems(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                \array_map(function (array $object) {
                    return Item::createFromArray($object);
                }, $itemsAsArray)
            ))
            ->then(function () {
                return new JsonResponse('Items patched', 200);
            });
    }
}
