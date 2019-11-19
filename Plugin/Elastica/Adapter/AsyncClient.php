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

namespace Apisearch\Plugin\Elastica\Adapter;

use Apisearch\Exception\ConnectionException;
use Apisearch\Plugin\Elastica\Domain\AsyncRequestAccessor;
use Apisearch\Server\Exception\ResponseException;
use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\ResponseException as ClueResponseException;
use Elastica\Client;
use Elastica\Exception\ClientException;
use Elastica\Exception\PartialShardFailureException;
use Elastica\Index;
use Elastica\Request;
use Elastica\Response;
use Elasticsearch\Endpoints\AbstractEndpoint;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class AsyncClient.
 */
class AsyncClient extends Client implements AsyncRequestAccessor
{
    /**
     * @var Browser
     *
     * React client
     */
    private $httpClient;

    /**
     * AsyncClient constructor.
     *
     * @param LoopInterface $eventLoop
     * @param array         $config
     */
    public function __construct(
        LoopInterface $eventLoop,
        array $config = []
    ) {
        $config['username'] = 'elastic';
        $config['password'] = 'test';
        parent::__construct($config);
        $this->httpClient = new Browser($eventLoop);
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

        $connection = $this->getConnection();
        $fullPath = sprintf('http://%s:%s/%s?%s',
            ($connection->getUsername() === null ? $connection->getHost() : $connection->getUsername(). ':' .$connection->getPassword(). '@' .$connection->getHost()),
            $connection->getPort(),
            $path,
            $this->arrayValuesToQuery($query)
        );

        $request = new Psr7\Request($method, $fullPath);
        $request = $request->withBody(Psr7\stream_for($data));
        $request = $request->withHeader('Content-Type', $contentType);
        $request = $request->withHeader('Content-Length', strlen($data));
        return $this
            ->httpClient
            ->send($request)
            ->then(function (ResponseInterface $response) {
                var_dump((string) $response->getBody());
                return new Response(
                    (string) ($response->getBody()),
                    $response->getStatusCode()
                );
            }, function (\Throwable $exception) {
//                var_dump($exception->getMessage());
                throw new ResponseException(
                    $exception->getMessage(),
                    $exception->getCode()
                );
            })
            ->then(function (Response $elasticaResponse) {

                $data = $elasticaResponse->getData();
                if (
                    isset($data['errors']) &&
                    $data['errors'] === true
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
     * @param Index            $index
     *
     * @return PromiseInterface
     */
    public function requestAsyncEndpoint(
        AbstractEndpoint $endpoint,
        ?Index $index = null
    ): PromiseInterface {
        $cloned = clone $endpoint;
        if ($index instanceof Index) {
            $cloned->setIndex($index->getName());
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
