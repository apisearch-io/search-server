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
use Apisearch\Server\Domain\Exception\FormatterException;
use Apisearch\Server\Domain\Query\ExportIndex;
use Apisearch\Server\Domain\WithRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;
use React\Stream\DuplexStreamInterface;
use React\Stream\ReadableStreamInterface;

/**
 * Class ExportIndexHandler.
 */
class ExportIndexHandler extends WithRepositoryAndEventPublisher
{
    /**
     * @param ExportIndex $exportIndex
     *
     * @return PromiseInterface<DuplexStreamInterface>
     *
     * @throws FormatterException
     */
    public function handle(ExportIndex $exportIndex): PromiseInterface
    {
        $from = \microtime(true);
        $repositoryReference = $exportIndex->getRepositoryReference();
        $ownerToken = $exportIndex->getToken();

        return $this
            ->repository
            ->exportIndex($repositoryReference)
            ->then(function (ReadableStreamInterface $stream) use ($repositoryReference, $from, $ownerToken) {
                $stream->on('end', function () use ($repositoryReference, $from, $stream, $ownerToken) {
                    $this
                        ->eventBus
                        ->dispatch(
                            (new IndexWasExported(
                                $repositoryReference->getIndexUUID(),
                                (int) ((\microtime(true) - $from) * 1000),
                                0
                            ))
                                ->withRepositoryReference($repositoryReference)
                                ->dispatchedBy($ownerToken)
                        );
                });

                return $stream;
            });
    }
}
