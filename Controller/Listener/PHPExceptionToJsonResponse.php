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
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PHPExceptionToJsonResponse.
 */
class PHPExceptionToJsonResponse
{
    /**
     * When controller gets exception.
     *
     * @param GetResponseForExceptionEvent $event
     *
     * @return PromiseInterface
     */
    public function onKernelException(GetResponseForExceptionEvent $event): PromiseInterface
    {
        return (new FulfilledPromise($event))
            ->then(function (GetResponseForExceptionEvent $event) {
                $exception = $event->getException();

                if ($exception instanceof Exception) {
                    $exception = $this->toOwnException($exception);
                }

                $exceptionErrorCode = $exception instanceof TransportableException
                    ? $exception::getTransportableHTTPError()
                    : 500;

                $event->stopPropagation();
                $event->setResponse(new JsonResponse([
                    'message' => $exception->getMessage(),
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
            preg_match('~No route found for "(.*)"~', $exception->getMessage(), $match);

            return ResourceNotAvailableException::routeNotAvailable($match[1] ?? $exception->getMessage());
        }

        return $exception;
    }
}
