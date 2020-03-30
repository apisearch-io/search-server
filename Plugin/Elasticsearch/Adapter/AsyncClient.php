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

use Apisearch\Exception\ConnectionException;
use Apisearch\Plugin\Elasticsearch\Domain\AsyncRequestAccessor;
use Apisearch\Server\Exception\ResponseException;
use Clue\React\Buzz\Browser;
use Elastica\Exception\ClientException;
use Elastica\Request;
use Elastica\Response;
use Elasticsearch\Endpoints\AbstractEndpoint;
use function RingCentral\Psr7\stream_for;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\Request as PSR7Request;
use RingCentral\Psr7\Response as PSR7Response;

/**
 * Class AsyncClient.
 */
class AsyncClient implements AsyncRequestAccessor
{
    /**
     * @var string
     *
     * Elasticsearch endpoint
     */
    private $elasticsearchEndpoint;

    /**
     * AsyncClient constructor.
     *
     * @param LoopInterface $eventLoop
     * @param string        $elasticsearchEndpoint
     */
    public function __construct(
        LoopInterface $eventLoop,
        string $elasticsearchEndpoint
    ) {
        $this->httpClient = new Browser($eventLoop);
        $this->elasticsearchEndpoint = $elasticsearchEndpoint;
    }

    /**
     * Makes calls to the elasticsearch server based on this index.
     *
     * It's possible to make any REST query directly over this method
     *
     * @param string       $path        Path to call
     * @param string       $method      Rest method to use (GET, POST, DELETE, PUT)
     * @param array|string $data        OPTIONAL Arguments as array or pre-encoded string
     * @param array        $query       OPTIONAL Query params
     * @param string       $contentType Content-Type sent with this request
     *
     * @throws ConnectionException|ClientException
     *
     * @return PromiseInterface
     */
    public function requestAsync(
        string $path,
        string $method = Request::GET,
        $data = [],
        array $query = [],
        $contentType = Request::DEFAULT_CONTENT_TYPE
    ): PromiseInterface {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        $fullPath = sprintf('%s/%s?%s',
            $this->elasticsearchEndpoint,
            ltrim($path, '/'),
            $this->arrayValuesToQuery($query)
        );

        if (
            false === strpos($fullPath, 'http://') &&
            false === strpos($fullPath, 'https://')
        ) {
            $fullPath = "http://$fullPath";
        }

        $request = new PSR7Request($method, $fullPath);
        $request = $request->withBody(stream_for($data));
        $request = $request->withHeader('Content-Type', $contentType);
        $request = $request->withHeader('Content-Length', strlen($data));

        return $this
            ->httpClient
            ->send($request)
            ->then(function (PSR7Response $response) {
                return new Response(
                    (string) ($response->getBody()),
                    $response->getStatusCode()
                );
            })
            ->otherwise(function (\Throwable $exception) {
                throw new ResponseException(
                    $exception->getMessage(),
                    $exception->getCode()
                );
            })
            ->then(function (Response $elasticaResponse) {
                $data = $elasticaResponse->getData();
                if (
                    isset($data['errors']) &&
                    true === $data['errors']
                ) {
                    throw new ResponseException(
                        $elasticaResponse->getErrorMessage(),
                        $elasticaResponse->getStatus()
                    );
                }

                return $elasticaResponse;
            });
    }

    /**
     * Makes calls to the elasticsearch server with usage official client Endpoint based on this index.
     *
     * @param AbstractEndpoint $endpoint
     * @param string           $index
     *
     * @return PromiseInterface
     */
    public function requestAsyncEndpoint(
        AbstractEndpoint $endpoint,
        string $index = null
    ): PromiseInterface {
        $cloned = clone $endpoint;
        if (is_string($index)) {
            $cloned->setIndex($index);
        }

        return $this->requestAsync(
            ltrim($cloned->getURI(), '/'),
            $cloned->getMethod(),
            null === $cloned->getBody() ? [] : $cloned->getBody(),
            $cloned->getParams()
        );
    }

    /**
     * Array to query string.
     *
     * @param array $values
     *
     * @return string
     */
    private function arrayValuesToQuery(array $values): string
    {
        $chain = [];
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $chain[] = "$key=".($value
                    ? 'true'
                    : 'false');
                continue;
            }

            $chain[] = "$key=$value";
        }

        return implode('&', $chain);
    }
}
