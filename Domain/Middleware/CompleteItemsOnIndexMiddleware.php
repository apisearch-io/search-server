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

use Apisearch\Server\Domain\Command\IndexItems;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use React\Promise\PromiseInterface;

/**
 * Class CompleteItemsOnIndexMiddleware.
 */
class CompleteItemsOnIndexMiddleware implements DiscriminableMiddleware
{
    /**
     * Execute middleware.
     *
     * @param IndexItems $command
     * @param callable   $next
     *
     * @return PromiseInterface
     */
    public function execute(
        IndexItems $command,
        $next
    ): PromiseInterface {
        foreach ($command->getItems() as $item) {
            $exactMatchingMetadata = $item->getExactMatchingMetadata();
            $item->addIndexedMetadata('exact_matching_metadata', $exactMatchingMetadata);
        }

        return $next($command);
    }

    /**
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [
            IndexItems::class,
        ];
    }
}
