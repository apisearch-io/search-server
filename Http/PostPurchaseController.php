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

namespace Apisearch\Server\Http;

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\PostPurchase;
use Apisearch\Server\Domain\Exception\InvalidPurchaseException;
use Apisearch\Server\Domain\Model\UserEncrypt;
use React\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PostPurchaseController.
 */
final class PostPurchaseController extends ControllerWithCommandBus
{
    /**
     * Add an interaction.
     *
     * @param Request     $request
     * @param UserEncrypt $userEncrypt
     *
     * @return Response
     */
    public function __invoke(
        Request $request,
        UserEncrypt $userEncrypt
    ): Response {
        $query = $request->query;
        $itemsUUID = $request->get('items_id');
        $itemsUUIDAsArray = \explode(',', $itemsUUID);
        $itemsUUIDAsArray = \array_map('trim', $itemsUUIDAsArray);
        $user = $userEncrypt->getUUIDByInput($query->get('user_id'));

        if (
            \is_null($user) ||
            empty($itemsUUIDAsArray)
        ) {
            throw InvalidPurchaseException::create();
        }

        $this->execute(new PostPurchase(
            RepositoryReference::create(
                RequestAccessor::getAppUUIDFromRequest($request),
                RequestAccessor::getIndexUUIDFromRequest($request)
            ),
            RequestAccessor::getTokenFromRequest($request),
            $userEncrypt->getUUIDByInput($query->get('user_id')),
            \array_map(fn (string $itemUUID) => ItemUUID::createByComposedUUID($itemUUID), $itemsUUIDAsArray),
        ));

        return new Response(204);
    }
}
