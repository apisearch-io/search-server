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

namespace Apisearch\Server\Domain\Middleware\ComplexFields;

use Apisearch\Model\Item;
use Apisearch\Server\Domain\Query\ExportIndex;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use function Drift\React\wait_for_stream_listeners;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

/**
 * Class ExportComplexFieldsMiddleware.
 */
class ExportComplexFieldsMiddleware extends ComplexFieldsMiddleware implements DiscriminableMiddleware
{
    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        $repositoryReference = $command->getRepositoryReference();
        $complexFields = $this
            ->metadataRepository
            ->get($repositoryReference, static::COMPLEX_FIELDS_METADATA);

        if (empty($complexFields)) {
            return $next($command);
        }

        return $next($command)
            ->then(function (ReadableStreamInterface $stream) use ($complexFields) {
                $responseStream = new ThroughStream(function (Item $item) use ($complexFields) {
                    $itemCopy = Item::createFromArray($item->toArray());
                    $this->exportComplexFieldsItem($itemCopy, $complexFields);

                    return $itemCopy;
                });

                wait_for_stream_listeners($responseStream, $this->loop, 1, 1)
                    ->then(function (WritableStreamInterface $responseStream) use ($stream, $complexFields) {
                        $stream->pipe($responseStream);
                        Util::forwardEvents($responseStream, $stream, ['close']);
                    });

                return $responseStream;
            });
    }

    /**
     * Only handle.
     *
     * @return string[]
     */
    public function onlyHandle(): array
    {
        return [
            ExportIndex::class,
        ];
    }
}
