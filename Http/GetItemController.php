<?php


namespace Apisearch\Server\Http;


use Apisearch\Exception\InvalidFormatException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Query\Query;
use Apisearch\Query\Query as ModelQuery;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetItemController
 */
final class GetItemController extends ControllerWithQueryBus
{
    /**
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
            ->ask(new Query(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                ModelQuery::createByUUID(ItemUUID::createByComposedUUID($itemComposedUUID))
                    ->disableAggregations()
                    ->disableHighlights()
                    ->disableSuggestions(),
                Origin::createEmpty(),
                '',
                []
            ))
            ->then(function (Result $result) {
                if (empty($result->getItems())) {
                    throw new ResourceNotAvailableException('Item not found');
                }

                return new JsonResponse(
                    $result->getFirstItem()->toArray(),
                    200,
                );
            });
    }
}
