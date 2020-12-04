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
use Apisearch\Server\Domain\Command\PostInteraction;
use Apisearch\Server\Domain\Exception\InvalidInteractionException;
use Apisearch\Server\Domain\Model\InteractionType;
use Apisearch\Server\Domain\Model\UserEncrypt;
use React\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PostInteractionController.
 */
final class PostInteractionController extends ControllerWithCommandBus
{
    /**
     * Add an interaction.
     *
     * @param Request     $request
     * @param string      $interaction
     * @param UserEncrypt $userEncrypt
     *
     * @return Response
     */
    public function __invoke(
        Request $request,
        string $interaction,
        UserEncrypt $userEncrypt
    ): Response {
        $query = $request->query;
        $itemUUID = $request->get('item_id');
        $host = $this->createOriginByRequest($request)->getHost();
        $origin = $this->createOriginByRequest($request);
        $user = $userEncrypt->getUUIDByInput($query->get('user_id'));
        $context = $userEncrypt->getUUIDByInput($query->get('context'));

        if (
            \is_null($user) ||
            !InteractionType::isValid($interaction)
        ) {
            throw InvalidInteractionException::create();
        }

        $this->execute(new PostInteraction(
            RepositoryReference::create(
                RequestAccessor::getAppUUIDFromRequest($request),
                RequestAccessor::getIndexUUIDFromRequest($request)
            ),
            RequestAccessor::getTokenFromRequest($request),
            $userEncrypt->getUUIDByInput($query->get('user_id')),
            ItemUUID::createByComposedUUID($itemUUID),
            \intval($query->get('position', 0)),
            $context,
            $origin,
            $interaction
        ));

        return new Response(204, [
            'Access-Control-Allow-Origin' => 'null' === $host
                ? '*'
                : $host,
        ]);
    }
}
