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

namespace Apisearch\Plugin\Elasticsearch\Adapter;

use Apisearch\Plugin\Elasticsearch\Endpoint\DeleteScroll;
use Elastica\Exception\InvalidException;
use Elastica\Query;
use Elastica\Response;
use Elastica\ResultSet\DefaultBuilder;
use Elasticsearch\Endpoints\Scroll;
use Elasticsearch\Endpoints\Search;
use function Drift\React\wait_for_stream_listeners;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use React\Stream\WritableStreamInterface;

/**
 * Class AsyncScroll.
 */
class AsyncScroll extends AsyncAdapter
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @param AsyncClient   $asyncClient
     * @param LoopInterface $loop
     */
    public function __construct(
        AsyncClient $asyncClient,
        LoopInterface $loop
    ) {
        parent::__construct($asyncClient);
        $this->loop = $loop;
    }

    /**
     * Make scroll and write to the stream.
     *
     * @param string $index
     * @param int    $chunkSize
     *
     * @throws InvalidException
     *
     * @return ReadableStreamInterface
     */
    public function scroll(
        string $indexName,
        int $chunkSize = 100
    ): ReadableStreamInterface {
        $stream = new ThroughStream();
        wait_for_stream_listeners($stream, $this->loop, 1, 1)
            ->then(function (WritableStreamInterface $stream) use ($indexName, $chunkSize) {
                $builder = new DefaultBuilder();
                $this->makeAtomicScroll(
                    $stream,
                    $builder,
                    $indexName,
                    $chunkSize,
                    null
                );
            });

        return $stream;
    }

    /**
     * Make scroll request.
     *
     * @param WritableStreamInterface $stream
     * @param DefaultBuilder          $builder
     * @param string                  $index
     * @param int                     $chunkSize
     * @param string|null             $scrollId
     */
    private function makeAtomicScroll(
        WritableStreamInterface $stream,
        DefaultBuilder $builder,
        string $indexName,
        int $chunkSize,
        ?string $scrollId
    ) {
        $query = new Query();
        if (null === $scrollId) {
            $endpoint = new Search();
            $endpoint->setBody([
                'size' => $chunkSize,
            ]);
            $endpoint->setParams(['scroll' => '1m']);
        } else {
            $endpoint = new Scroll();
            $body = ['scroll' => '1m', 'scroll_id' => $scrollId];
            $endpoint->setBody($body);
        }

        $this
            ->getAsyncClient()
            ->requestAsyncEndpoint($endpoint, $indexName)
            ->then(function (Response $data) use ($stream, $builder, $query, $indexName, $chunkSize, $scrollId) {
                $responseSet = $builder->buildResultSet($data, $query);
                $scrollId = $data->getScrollId();

                if (empty($responseSet->getResults())) {
                    $stream->end();

                    $deleteScroll = new DeleteScroll();
                    $body = ['scroll_id' => $scrollId];
                    $deleteScroll->setBody($body);
                    $this
                        ->getAsyncClient()
                        ->requestAsyncEndpoint($deleteScroll);
                } else {
                    $stream->write($responseSet);
                    $this->makeAtomicScroll(
                        $stream,
                        $builder,
                        $indexName,
                        $chunkSize,
                        $scrollId
                    );
                }
            });
    }
}
