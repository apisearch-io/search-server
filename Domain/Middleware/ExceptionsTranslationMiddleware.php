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

namespace Apisearch\Server\Domain\Middleware;

use Exception;
use React\Promise\PromiseInterface;
use Throwable;

/**
 * Class ExceptionsTranslationMiddleware.
 */
final class ExceptionsTranslationMiddleware
{
    private array $translations = [
        'index_not_found' => 'Index was not found',
        'index_exists' => 'Index already exists',
    ];

    /**
     * @param object   $command
     * @param callable $next
     *
     * @return PromiseInterface
     *
     * @throws Throwable
     */
    public function execute($command, callable $next): PromiseInterface
    {
        return $next($command)
            ->otherwise(function (Exception $exception) use ($command) {
                $message = $exception->getMessage();
                $message = $this->translations[$message] ?? $message;
                $exceptionType = \get_class($exception);

                throw new $exceptionType(
                    $message,
                    $exception->getCode(),
                    $exception
                );
            });
    }
}
