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
use Apisearch\Server\Domain\Exception\ResponseException;
use Elastica\Exception\ClientException;
use Elastica\Request;
use Elastica\Response;
use Elasticsearch\Endpoints\AbstractEndpoint;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\Response as PSR7Response;
use function RingCentral\Psr7\stream_for;

/**
 * Class AsyncClient.
 */
class AsyncClient implements AsyncRequestAccessor
{
    private Browser $browser;
    private string $elasticsearchEndpoint;
    private string $elasticsearchAuthorizationToken;

    /**
     * @param Browser $browser
     * @param string  $elasticsearchEndpoint
     * @param string  $elasticsearchAuthorizationToken
     */
    public function __construct(
        Browser $browser,
        string $elasticsearchEndpoint,
        string $elasticsearchAuthorizationToken
    ) {
        $this->browser = $browser;
        $this->elasticsearchEndpoint = $elasticsearchEndpoint;
        $this->elasticsearchAuthorizationToken = $elasticsearchAuthorizationToken;
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
        $withData = !empty($data);
        if (\is_array($data) && !empty($data)) {
            $data = \json_encode($data);
            $withData = true;
        }

        $fullPath = \sprintf('%s/%s?%s',
            $this->elasticsearchEndpoint,
            \ltrim($path, '/'),
            $this->arrayValuesToQuery($query)
        );

        if (
            false === \strpos($fullPath, 'http://') &&
            false === \strpos($fullPath, 'https://')
        ) {
            $fullPath = "http://$fullPath";
        }

        return $this
            ->browser
            ->request(
                $method,
                $fullPath,
                \array_filter([
                    'Content-Type' => $contentType,
                    'Content-Length' => ($withData ? \strlen($data) : false),
                    'Authorization' => !empty($this->elasticsearchAuthorizationToken)
                        ? "Basic {$this->elasticsearchAuthorizationToken}"
                        : false,
                ]),
                ($withData ? stream_for($data) : '')
            )
            ->then(function (PSR7Response $response) {
                return new Response(
                    (string) ($response->getBody()),
                    $response->getStatusCode()
                );
            })
            ->otherwise(function (\Throwable $exception) use ($path) {
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
                        $this->getErrorText($elasticaResponse),
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
     * @param string|null      $index
     *
     * @return PromiseInterface
     */
    public function requestAsyncEndpoint(
        AbstractEndpoint $endpoint,
        string $index = null
    ): PromiseInterface {
        $cloned = clone $endpoint;
        if (\is_string($index)) {
            $cloned->setIndex($index);
        }

        return $this->requestAsync(
            \ltrim($cloned->getURI(), '/'),
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
            if (\is_bool($value)) {
                $chain[] = "$key=".($value
                    ? 'true'
                    : 'false');
                continue;
            }

            $chain[] = "$key=$value";
        }

        return \implode('&', $chain);
    }

    /**
     * Get error text.
     *
     * @param Response $response
     *
     * @return string
     */
    private function getErrorText(Response $response): string
    {
        $data = $response->getData();
        if (false === $data['errors']) {
            return '';
        }

        if (
            !\array_key_exists('items', $data) ||
            !\is_array($data['items']) ||
            0 === \count($data['items'])
        ) {
            return '';
        }

        foreach ($data['items'] as $item) {
            $action = \reset($item);
            if (
                !\is_array($action) ||
                !\array_key_exists('error', $action) ||
                !\is_array($action['error']) ||
                !\array_key_exists('reason', $action['error'])
            ) {
                continue;
            }

            return \strval($action['error']['reason']);
        }

        return 'Unknown error';
    }
}
