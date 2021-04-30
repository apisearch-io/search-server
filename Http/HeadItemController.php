<?php


namespace Apisearch\Server\Http;


use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query as ModelQuery;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Query\Ping;
use Apisearch\Server\Domain\Query\Query;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HeadItemController
 */
final class HeadItemController extends ControllerWithQueryBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface
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
                    ->disableResults()
                    ->disableSuggestions(),
                Origin::createEmpty(),
                '',
                []
            ))
            ->then(function (Result $result) {
                if (empty($result->getItems())) {
                    throw new ResourceNotAvailableException('Item not found');
                }

                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
            });
    }
}
