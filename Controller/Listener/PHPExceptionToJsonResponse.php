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

use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Exception\TransportableException;
use Exception;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PHPExceptionToJsonResponse.
 */
class PHPExceptionToJsonResponse implements EventSubscriberInterface
{
    /**
     * When controller gets exception.
     *
     * @param ExceptionEvent $event
     *
     * @return PromiseInterface
     */
    public function onKernelException(ExceptionEvent $event): PromiseInterface
    {
        return resolve($event)
            ->then(function (ExceptionEvent $event) {
                $throwable = $event->getThrowable();

                if ($throwable instanceof Exception) {
                    $throwable = $this->toOwnException($throwable);
                }

                $exceptionErrorCode = $throwable instanceof TransportableException
                    ? $throwable::getTransportableHTTPError()
                    : 500;

                $event->stopPropagation();
                $event->setResponse(new JsonResponse([
                    'message' => $throwable->getMessage(),
                    'code' => $exceptionErrorCode,
                ], $exceptionErrorCode));
            });
    }

    /**
     * To own exceptions.
     *
     * @param Exception $exception
     *
     * @return Exception
     */
    private function toOwnException(Exception $exception): Exception
    {
        if ($exception instanceof NotFoundHttpException) {
            \preg_match('~No route found for "(.*)"~', $exception->getMessage(), $match);

            return ResourceNotAvailableException::routeNotAvailable($match[1] ?? $exception->getMessage());
        }

        return $exception;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ExceptionEvent::class => [
                ['onKernelException', 0],
            ],
        ];
    }
}
