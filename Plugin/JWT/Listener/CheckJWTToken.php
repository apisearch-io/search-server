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

namespace Apisearch\Plugin\JWT\Listener;

use Apisearch\Exception\ForbiddenException;
use Apisearch\Plugin\JWT\Domain\JWTBearerChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class CheckJWTToken.
 */
class CheckJWTToken implements EventSubscriberInterface
{
    private JWTBearerChecker $jwtBearerChecker;
    private array $endpoints;

    /**
     * @param JWTBearerChecker $jwtBearerChecker
     * @param array            $endpoints
     */
    public function __construct(
        JWTBearerChecker $jwtBearerChecker,
        array $endpoints
    ) {
        $this->jwtBearerChecker = $jwtBearerChecker;
        $this->endpoints = $endpoints;
    }

    /**
     * @param RequestEvent $event
     *
     * @return void
     */
    public function checkToken(RequestEvent $event)
    {
        $request = $event->getRequest();
        $tags = $request->attributes->get('tags') ?? [];
        $tags[] = $request->get('_route');

        if (
            !empty($this->endpoints) &&
            empty(\array_intersect($tags, $this->endpoints))
        ) {
            return;
        }

        $headers = $request->headers;
        $authorization = $headers->get('authorization');

        if (\is_null($authorization)) {
            throw new ForbiddenException();
        }

        $jwtPayload = $this
            ->jwtBearerChecker
            ->checkBearer($authorization);

        $request
            ->attributes
            ->set('jwt', $jwtPayload);
    }

    /**
     * @return array|void
     */
    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => ['checkToken', 8],
        ];
    }
}
