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

namespace Apisearch\Server\Controller\Listener;

use Apisearch\Exception\InvalidTokenException;
use function React\Promise\reject;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AdminFirewall.
 */
class AdminFirewall implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $godToken;

    /**
     * @var string
     */
    private $pingToken;

    /**
     * @param string $godToken
     * @param string $pingToken
     */
    public function __construct(
        string $godToken,
        string $pingToken
    ) {
        $this->godToken = $godToken;
        $this->pingToken = $pingToken;
    }

    /**
     * Validate hand written firewall.
     *
     * @param RequestEvent $event
     *
     * @return PromiseInterface
     */
    public function checkTokenOnKernelRequest(RequestEvent $event): PromiseInterface
    {
        $request = $event->getRequest();
        $tokenString = RequestHelper::getTokenStringFromRequest($request);

        if (
            'admin' === $request->get('firewall') &&
            $tokenString !== $this->godToken
        ) {
            return reject(new InvalidTokenException('Admin permissions required'));
        }

        if (
            'ping' === $request->get('firewall') &&
            $tokenString !== $this->pingToken
        ) {
            return reject(new InvalidTokenException('Admin permissions required'));
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
