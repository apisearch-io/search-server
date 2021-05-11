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

use Apisearch\Exception\TransportableException;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PHPExceptionToJsonResponse.
 */
final class PHPExceptionToJsonResponse implements EventSubscriberInterface
{
    /**
     * @param ExceptionEvent $event
     *
     * @return PromiseInterface
     */
    public function onKernelException(ExceptionEvent $event): PromiseInterface
    {
        return resolve($event)
            ->then(function (ExceptionEvent $event) {
                $throwable = $event->getThrowable();

                $responseMessage = $serverMessage = $throwable->getMessage();
                $exceptionErrorCode = $throwable instanceof TransportableException
                    ? $throwable::getTransportableHTTPError()
                    : 500;

                if ($throwable instanceof NotFoundHttpException) {
                    $serverMessage = 'Route not found';
                    $exceptionErrorCode = 404;
                }

                $response = new JsonResponse([
                    'message' => $responseMessage,
                    'code' => $exceptionErrorCode,
                ], $exceptionErrorCode);

                $response->headers->set('x-server-message', $serverMessage);
                $event->setResponse($response);
                $event->stopPropagation();
            });
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ExceptionEvent::class => [
                ['onKernelException', 1],
            ],
        ];
    }
}
