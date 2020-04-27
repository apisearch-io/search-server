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

namespace Apisearch\Server\Domain\QueryHandler;

use Apisearch\Server\Domain\Event\IndexWasExported;
use Apisearch\Server\Domain\Query\ExportIndex;
use Apisearch\Server\Domain\WithRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;
use React\Stream\DuplexStreamInterface;

/**
 * Class ExportIndexHandler.
 */
class ExportIndexHandler extends WithRepositoryAndEventPublisher
{
    /**
     * @param ExportIndex $exportIndex
     *
     * @return PromiseInterface<DuplexStreamInterface>
     */
    public function handle(ExportIndex $exportIndex): PromiseInterface
    {
        $repositoryReference = $exportIndex->getRepositoryReference();
        $from = \microtime(true);

        return $this
            ->repository
            ->exportIndex($repositoryReference)
            ->then(function ($stream) use ($repositoryReference, $from) {
                $stream->on('end', function () use ($repositoryReference, $from) {
                    $this
                        ->eventBus
                        ->dispatch(
                            (new IndexWasExported(
                                $repositoryReference->getIndexUUID(),
                                (int) ((\microtime(true) - $from) * 1000)
                            ))
                                ->withRepositoryReference($repositoryReference)
                        );
                });

                return $stream;
            });
    }
}
