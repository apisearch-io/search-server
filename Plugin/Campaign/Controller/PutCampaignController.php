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

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Plugin\Campaign\Domain\Command\PutCampaign;
use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Controller\ControllerWithCommandBus;
use Apisearch\Server\Controller\RequestAccessor;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PutCampaignController.
 */
class PutCampaignController extends ControllerWithCommandBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $campaignId = $request->get('campaign_id');
        $campaignAsArray = RequestAccessor::extractRequestContentObject(
            $request,
            '',
            InvalidFormatException::configFormatNotValid($request->getContent()),
            []
        );

        $campaignAsArray['uid'] = (new CampaignUID($campaignId))->composeUID();

        return $this
            ->execute(new PutCampaign(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                Campaign::createFromArray($campaignAsArray)
            ))
            ->then(function () {
                return new Response();
            });
    }
}
