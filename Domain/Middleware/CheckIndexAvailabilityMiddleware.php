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

use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Repository\WithRepositoryReference;
use Apisearch\Server\Domain\Query\ExportIndex;
use Apisearch\Server\Domain\Repository\AppRepository\Repository as AppRepository;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use React\Promise\PromiseInterface;
use function React\Promise\reject;

/**
 * Class CheckIndexAvailabilityMiddleware.
 */
final class CheckIndexAvailabilityMiddleware implements DiscriminableMiddleware
{
    /**
     * @var AppRepository
     */
    private $appRepository;

    /**
     * @param AppRepository $appRepository
     */
    public function __construct(AppRepository $appRepository)
    {
        $this->appRepository = $appRepository;
    }

    /**
     * Execute middleware.
     *
     * @param mixed    $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute(
        $command,
        $next
    ): PromiseInterface {
        /**
         * @var WithRepositoryReference
         */
        $repositoryReference = $command->getRepositoryReference();
        $indexUUID = $repositoryReference->getIndexUUID();

        return $this
            ->appRepository
            ->checkIndex($repositoryReference, $indexUUID)
            ->then(function (bool $isAvailable) use ($next, $command, $indexUUID) {
                return $isAvailable
                    ? $next($command)
                    : reject(ResourceNotAvailableException::indexNotAvailable($indexUUID->composeUUID()));
            });
    }

    /**
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [
            ExportIndex::class,
        ];
    }
}
