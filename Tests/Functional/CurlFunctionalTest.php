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

namespace Apisearch\Server\Tests\Functional;

use Apisearch\Exception\ConnectionException;
use Apisearch\Http\Http;
use Apisearch\Model\Item;
use Apisearch\Model\Token;
use Symfony\Component\Routing\Route;

/**
 * Class CurlFunctionalTest.
 */
abstract class CurlFunctionalTest extends HttpFunctionalTest
{
    /**
     * @return bool
     */
    protected static function needsServer(): bool
    {
        return true;
    }

    /**
     * Export index.
     *
     * @param string $format
     * @param bool   $closeImmediately
     * @param string $appId
     * @param string $index
     * @param Token  $token
     *
     * @return Item[]
     */
    public function exportIndex(
        string $format,
        bool $closeImmediately = false,
        string $appId = null,
        string $index = null,
        Token $token = null
    ): array {
        $content = $this->makeStreamCall(
            'v1_export_index',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            [
                'format' => $format,
            ],
            $closeImmediately
        );

        if ($closeImmediately) {
            return [];
        }

        $rows = \explode("\n", $content['body']['message']);
        $rows = \array_filter($rows, function ($row) {
            return !empty($row);
        });

        return $rows;
    }

    /**
     * @param string       $routeName
     * @param array        $routeParameters
     * @param Token|null   $token
     * @param array|string $body
     * @param array        $queryParameters
     * @param array        $headers
     *
     * @return array
     */
    protected static function request(
        string $routeName,
        array $routeParameters = [],
        ?Token $token = null,
        $body = [],
        array $queryParameters = [],
        array $headers = []
    ): array {
        return self::makeCurl(
            $routeName,
            $routeParameters,
            $token,
            $body,
            $queryParameters,
            $headers
        );
    }

    /**
     * Make a curl execution.
     *
     * @param string       $routeName
     * @param array        $routeParameters
     * @param Token|null   $token
     * @param array|string $body
     * @param array        $queryParameters
     * @param array        $headers
     *
     * @return array
     */
    protected static function makeCurl(
        string $routeName,
        array $routeParameters = [],
        ?Token $token = null,
        $body = [],
        array $queryParameters = [],
        array $headers = []
    ): array {
        /**
         * @var Route
         */
        $routeName = 'apisearch_'.$routeName;
        $router = self::getStatic('router');
        $route = $router
            ->getRouteCollection()
            ->get($routeName);

        $routePath = $route
            ? $router->generate($routeName, $routeParameters)
            : '/not-found';

        $headers[] = Http::TOKEN_ID_HEADER.': '.($token
            ? $token->getTokenUUID()->composeUUID()
            : self::getParameterStatic('apisearch_server.god_token'));

        $method = $route instanceof Route
            ? $route->getMethods()[0]
            : 'GET';

        $ch = \curl_init();
        \curl_setopt($ch, CURLOPT_URL, \sprintf('http://127.0.0.1:'.static::HTTP_TEST_SERVICE_PORT.'%s?%s',
            $routePath,
            \http_build_query($queryParameters)
        ));
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        \curl_setopt($ch, CURLOPT_HEADER, 1);
        $body = \is_string($body)
            ? $body
            : \json_encode($body);

        if (!empty($body)) {
            \curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: '.\strlen($body);
        }

        \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = \curl_exec($ch);

        if (false === $response) {
            throw new ConnectionException('Apisearch returned an internal error code [500]');
        }

        $headerSize = \curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseHeadersAsString = \substr($response, 0, $headerSize);
        $content = \substr($response, $headerSize);

        $responseCode = \curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $contentLength = \curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        \curl_close($ch);
        if (false !== \array_search('Accept-Encoding: gzip', $headers)) {
            $content = \gzdecode($content);
        }
        if (false !== \array_search('Accept-Encoding: deflate', $headers)) {
            $content = \gzinflate($content);
        }

        $responseHeaders = [];
        $responseHeadersLines = \explode("\r\n", $responseHeadersAsString);
        foreach ($responseHeadersLines as $line) {
            $parts = \explode(':', $line, 2);
            if (1 === \count($parts)) {
                continue;
            }

            $responseHeaders[$parts[0]] = \trim($parts[1]);
        }
        $result = [
            'code' => $responseCode,
            'body' => \json_decode($content, true) ?? $content,
            'length' => $contentLength,
            'headers' => $responseHeaders,
        ];

        if (\is_string($result['body'])) {
            $result['body'] = ['message' => $result['body']];
        }

        self::throwTransportableExceptionIfNeeded($result);

        self::$lastResponse = $result;

        return $result;
    }

    /**
     * Make stream call.
     *
     * @param string     $routeName
     * @param array      $routeParameters
     * @param Token|null $token
     * @param array      $queryParameters
     * @param bool       $closeImmediately
     *
     * @return array
     */
    protected function makeStreamCall(
        string $routeName,
        array $routeParameters = [],
        ?Token $token = null,
        array $queryParameters = [],
        bool $closeImmediately = false
    ): array {
        /**
         * @var Route
         */
        $routeName = 'apisearch_'.$routeName;
        $router = self::getStatic('router');
        $route = $router
            ->getRouteCollection()
            ->get($routeName);

        $routePath = $route
            ? $router->generate($routeName, $routeParameters)
            : '/not-found';

        $queryParameters[Http::TOKEN_FIELD] = ($token
                ? $token->getTokenUUID()->composeUUID()
                : self::getParameterStatic('apisearch_server.god_token'));

        $url = \sprintf('http://127.0.0.1:'.static::HTTP_TEST_SERVICE_PORT.'%s?%s',
            $routePath,
            \http_build_query($queryParameters)
        );

        $stream = \fopen($url, 'r');
        if ($closeImmediately) {
            \usleep(1000);
            \fclose($stream);

            return [];
        }

        \ob_flush();
        $contents = \stream_get_contents($stream);
        \ob_flush();

        \fclose($stream);
        $headers = $http_response_header;
        $codeParts = \explode(' ', $headers[0]);

        $result = [
            'code' => (int) $codeParts[1],
            'body' => \json_decode($contents, true) ?? $contents,
        ];
        if (\is_string($result['body'])) {
            $result['body'] = ['message' => $result['body']];
        }

        self::throwTransportableExceptionIfNeeded($result);

        return $result;
    }
}
