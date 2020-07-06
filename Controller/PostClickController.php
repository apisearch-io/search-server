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

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\PostClick;
use React\Http\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PutClickController.
 */
class PostClickController extends ControllerWithCommandBus
{
    /**
     * Add an interaction.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        $query = $request->query;
        $itemUUID = $request->get('item_id');

        $this->execute(new PostClick(
            RepositoryReference::create(
                RequestAccessor::getAppUUIDFromRequest($request),
                RequestAccessor::getIndexUUIDFromRequest($request)
            ),
            RequestAccessor::getTokenFromRequest($request),
            $query->get('user_id'),
            ItemUUID::createByComposedUUID($itemUUID),
            \intval($query->get('position', 0)),
            $this->createOriginByRequest($request)
        ));

        return new Response(200);
    }
}
