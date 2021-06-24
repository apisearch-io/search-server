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

namespace Apisearch\Plugin\Tor\Http\Listener;

use Apisearch\Exception\ForbiddenException;
use Apisearch\Plugin\Tor\Domain\Ips;
use Apisearch\Server\Http\RemoteAddr;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class IpCheckOverHTTP implements EventSubscriberInterface
{
    private Ips $ips;

    /**
     * @param Ips $ips
     */
    public function __construct(Ips $ips)
    {
        $this->ips = $ips;
    }

    /**
     * @param RequestEvent $event
     */
    public function checkIp(RequestEvent $event): void
    {
        $remoteAddr = RemoteAddr::getRemoteAddrFromHeaderBag($event->getRequest()->headers);
        if (!\is_null($remoteAddr) && $this->ips->isATorIp($remoteAddr)) {
            throw new ForbiddenException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => [
                ['checkIp', 256],
            ],
        ];
    }
}
