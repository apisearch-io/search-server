<?php


namespace Apisearch\Server\Http;


use Apisearch\Exception\InvalidFormatException;
use Apisearch\Model\Item;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\PatchItems;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class PatchItemController extends ControllerWithCommandBus
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
        $itemAsArray = RequestAccessor::extractRequestContentObject(
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
                [
                    Item::createFromArray($itemAsArray)
                ]
            ))
            ->then(function () {
                return new JsonResponse('Item patched', 200);
            });
    }
}
