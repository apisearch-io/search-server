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

use Apisearch\Plugin\Campaign\Domain\Command\DeleteCampaigns;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Controller\ControllerWithCommandBus;
use Apisearch\Server\Controller\RequestAccessor;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DeleteCampaignsController.
 */
class DeleteCampaignsController extends ControllerWithCommandBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function __invoke(Request $request): PromiseInterface
    {
        return $this
            ->execute(new DeleteCampaigns(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request)
            ))
            ->then(function () {
                return new Response();
            });
    }
}
