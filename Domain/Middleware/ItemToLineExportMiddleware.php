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

use Apisearch\Server\Domain\Format\FormatTransformers;
use Apisearch\Server\Domain\Query\ExportIndex;
use Apisearch\Server\Domain\Stream\StreamFormatTransformer;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use function Drift\React\wait_for_stream_listeners;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

/**
 * Class ItemToLineExportMiddleware.
 */
class ItemToLineExportMiddleware implements DiscriminableMiddleware
{
    private FormatTransformers $formatTransformers;
    private LoopInterface $loop;

    /**
     * @param FormatTransformers $formatTransformers
     * @param LoopInterface      $loop
     */
    public function __construct(
        FormatTransformers $formatTransformers,
        LoopInterface $loop
    ) {
        $this->formatTransformers = $formatTransformers;
        $this->loop = $loop;
    }

    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        $format = $command->getFormat();

        $formatTransformer = $this
            ->formatTransformers
            ->getFormatterByName(empty($format)
                ? 'source'
                : $format
            );

        return $next($command)
            ->then(function (ReadableStreamInterface $stream) use ($formatTransformer) {
                $responseStream = new ThroughStream();

                wait_for_stream_listeners($responseStream, $this->loop, 1, 1)
                    ->then(function (WritableStreamInterface $responseStream) use ($stream, $formatTransformer) {
                        $responseStream->write($formatTransformer->getHeaderLine()."\n");
                        $streamFormatTransformer = new StreamFormatTransformer($responseStream, $formatTransformer);

                        $stream->pipe($streamFormatTransformer);
                        Util::forwardEvents($streamFormatTransformer, $stream, ['close']);
                    });

                return $responseStream;
            });
    }

    /**
     * @return string[]
     */
    public function onlyHandle(): array
    {
        return [
            ExportIndex::class,
        ];
    }
}
