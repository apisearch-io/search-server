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

namespace Apisearch\Plugin\Campaign\Controller;

use Apisearch\Plugin\Campaign\Domain\Model\Campaigns;
use Apisearch\Plugin\Campaign\Domain\Query\GetCampaigns;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Controller\ControllerWithQueryBus;
use Apisearch\Server\Controller\RequestAccessor;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetCampaignsController.
 */
class GetCampaignsController extends ControllerWithQueryBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function __invoke(Request $request): PromiseInterface
    {
        return $this
            ->ask(new GetCampaigns(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request)
            ))
            ->then(function (Campaigns $campaigns) {
                return new JsonResponse($campaigns->toArray());
            });
    }
}
