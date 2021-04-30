<?php


namespace Apisearch\Server\Http;


use Apisearch\Exception\InvalidFormatException;
use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\DeleteItems;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DeleteItemController
 */
final class DeleteItemController extends ControllerWithCommandBus
{
    /**
     * Delete items.
     *
     * @param Request $request
     * @param string $itemComposedUUID
     *
     * @return PromiseInterface
     *
     * @throws InvalidFormatException
     */
    public function __invoke(
        Request $request,
        string $itemComposedUUID
    ): PromiseInterface
    {
        return $this
            ->execute(new DeleteItems(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                [
                    ItemUUID::createByComposedUUID($itemComposedUUID)
                ]
            ))
            ->then(function () {
                return new JsonResponse('Item deleted', 200);
            });
    }
}
