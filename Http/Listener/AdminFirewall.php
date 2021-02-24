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

namespace Apisearch\Server\Http\Listener;

use Apisearch\Exception\InvalidTokenException;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AdminFirewall.
 */
final class AdminFirewall implements EventSubscriberInterface
{
    private string $godToken;
    private string $healthCheckToken;
    private string $pingToken;

    /**
     * @param string $godToken
     * @param string $healthCheckToken
     * @param string $pingToken
     */
    public function __construct(
        string $godToken,
        string $healthCheckToken,
        string $pingToken
    ) {
        $this->godToken = $godToken;
        $this->healthCheckToken = $healthCheckToken;
        $this->pingToken = $pingToken;
    }

    /**
     * @param RequestEvent $event
     *
     * @return PromiseInterface
     */
    public function checkTokenOnKernelRequest(RequestEvent $event): PromiseInterface
    {
        $request = $event->getRequest();
        $tokenString = RequestHelper::getTokenStringFromRequest($request);
        $firewall = $request->get('firewall');

        if (
            'admin' === $firewall &&
            $tokenString !== $this->godToken
        ) {
            return reject(new InvalidTokenException('Admin permissions required'));
        }

        if (
            'health_check' === $firewall &&
            $tokenString !== $this->healthCheckToken
        ) {
            return reject(new InvalidTokenException('Health Check permissions required'));
        }

        if (
            'ping' === $firewall &&
            $tokenString !== $this->pingToken
        ) {
            return reject(new InvalidTokenException('Ping permissions required'));
        }

        return resolve();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => [
                ['checkTokenOnKernelRequest', 9],
            ],
        ];
    }
}
