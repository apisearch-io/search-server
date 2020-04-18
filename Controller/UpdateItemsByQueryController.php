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
use Apisearch\Http\Http;
use Apisearch\Model\Changes;
use Apisearch\Query\Query as QueryModel;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\UpdateItems;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UpdateItemsByQueryController.
 */
class UpdateItemsByQueryController extends ControllerWithCommandBus
{
    /**
     * Update items.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     *
     * @throws InvalidFormatException
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $queryAsArray = RequestAccessor::extractRequestContentObject(
            $request,
            Http::QUERY_FIELD,
            InvalidFormatException::queryFormatNotValid($request->getContent())
        );

        $changesAsArray = RequestAccessor::extractRequestContentObject(
            $request,
            Http::CHANGES_FIELD,
            InvalidFormatException::changesFormatNotValid($request->getContent())
        );

        return $this
            ->execute(new UpdateItems(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                QueryModel::createFromArray($queryAsArray),
                Changes::createFromArray($changesAsArray)
            ))
            ->then(function () {
                return new JsonResponse('Items updated', 200);
            });
    }
}
