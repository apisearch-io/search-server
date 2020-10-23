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

use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignUID;
use Apisearch\Plugin\Campaign\Domain\Query\GetCampaign;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Controller\ControllerWithQueryBus;
use Apisearch\Server\Controller\RequestAccessor;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetCampaignController.
 */
class GetCampaignController extends ControllerWithQueryBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $campaignId = $request->get('campaign_id');

        return $this
            ->ask(new GetCampaign(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                new CampaignUID($campaignId)
            ))
            ->then(function (Campaign $campaign) {
                return new JsonResponse($campaign->toArray());
            });
    }
}
