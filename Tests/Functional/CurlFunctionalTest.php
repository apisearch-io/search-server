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

use Apisearch\Config\Config;
use Apisearch\Exception\InvalidFormatException;
use Apisearch\Http\Http;
use Apisearch\Http\HttpResponsesToException;
use Apisearch\Model\Changes;
use Apisearch\Model\Index;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query as QueryModel;
use Apisearch\Result\Result;
use Apisearch\User\Interaction;
use DateTime;
use Symfony\Component\Routing\Route;

/**
 * Class CurlFunctionalTest.
 */
abstract class CurlFunctionalTest extends ApisearchServerBundleFunctionalTest
{
    use HttpResponsesToException;

    /**
     * @var array
     *
     * Last response
     */
    protected static $lastResponse = [];

    /**
     * @return bool
     */
    protected static function needsServer(): bool
    {
        return true;
    }

    /**
     * Query using the bus.
     *
     * @param QueryModel $query
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     * @param array      $parameters
     * @param array      $headers
     *
     * @return Result
     */
    public function query(
        QueryModel $query,
        string $appId = null,
        string $index = null,
        Token $token = null,
        array $parameters = [],
        array $headers = []
    ): Result {
        $response = self::makeCurl(
            'v1_query',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            $query->toArray(),
            $parameters,
            $headers
        );

        self::$lastResponse = $response;

        return Result::createFromArray($response['body']);
    }

    /**
     * Preflight CORS query.
     *
     * @param string $origin
     * @param string $appId
     * @param string $index
     *
     * @return string
     */
    public function getCORSPermissions(
        string $origin,
        string $appId = null,
        string $index = null
    ): string {
        if ('*' === $index) {
            $response = self::makeCurl(
                'v1_query_all_indices_preflight',
                [
                    'app_id' => $appId ?? static::$appId,
                ],
                null, [], [],
                ['Origin:'.$origin]
            );
        } else {
            $response = self::makeCurl(
                'v1_query_preflight',
                [
                    'app_id' => $appId ?? static::$appId,
                    'index_id' => $index ?? static::$index,
                ],
                null, [], [],
                ['Origin:'.$origin]
            );
        }

        self::$lastResponse = $response;

        return $response['headers']['access-control-allow-origin'] ?? '';
    }

    /**
     * Delete using the bus.
     *
     * @param ItemUUID[] $itemsUUID
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     */
    public function deleteItems(
        array $itemsUUID,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_delete_items',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            \array_map(function (ItemUUID $itemUUID) {
                return $itemUUID->toArray();
            }, $itemsUUID)
        );
    }

    /**
     * Add items using the bus.
     *
     * @param Item[] $items
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    public static function indexItems(
        array $items,
        ?string $appId = null,
        ?string $index = null,
        ?Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_put_items',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            \array_map(function (Item $item) {
                return $item->toArray();
            }, $items)
        );
    }

    /**
     * Update using the bus.
     *
     * @param QueryModel $query
     * @param Changes    $changes
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     */
    public function updateItems(
        QueryModel $query,
        Changes $changes,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_update_items_by_query',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            [
                'query' => $query->toArray(),
                'changes' => $changes->toArray(),
            ]
        );
    }

    /**
     * Reset index using the bus.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    public function resetIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_reset_index',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token
        );
    }

    /**
     * @param string|null $appId
     * @param Token       $token
     *
     * @return Index[]
     */
    public function getIndices(
        string $appId = null,
        Token $token = null
    ): array {
        $response = self::makeCurl(
            'v1_get_indices',
            [
                'app_id' => $appId ?? static::$appId,
            ],
            $token,
            []
        );

        $indices = [];
        $body = $response['body'];
        foreach ($body as $item) {
            $indices[] = Index::createFromArray($item);
        }
        self::$lastResponse = $response;

        return $indices;
    }

    /**
     * Create index using the bus.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     * @param Config $config
     */
    public static function createIndex(
        string $appId = null,
        string $index = null,
        Token $token = null,
        Config $config = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_create_index',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            \is_null($config)
                ? []
                : $config->toArray()
        );
    }

    /**
     * Configure index using the bus.
     *
     * @param Config $config
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    public function configureIndex(
        Config $config,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_configure_index',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            $config->toArray()
        );
    }

    /**
     * Check index.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     *
     * @return bool
     */
    public function checkIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    ): bool {
        try {
            $response = self::makeCurl(
                'v1_check_index',
                [
                    'app_id' => $appId ?? static::$appId,
                    'index_id' => $index ?? static::$index,
                ],
                $token,
                []
            );
            self::$lastResponse = $response;
        } catch (InvalidFormatException $exception) {
            return false;
        }

        return
            200 <= $response['code'] &&
            $response['code'] < 300;
    }

    /**
     * Delete index using the bus.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    public static function deleteIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_delete_index',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token
        );
    }

    /**
     * Add token.
     *
     * @param Token  $newToken
     * @param string $appId
     * @param Token  $token
     */
    public static function putToken(
        Token $newToken,
        string $appId = null,
        Token $token = null
    ) {
        $newTokenAsArray = $newToken->toArray();
        unset($newTokenAsArray['uuid']);

        self::$lastResponse = self::makeCurl(
            'v1_put_token',
            [
                'app_id' => $appId ?? static::$appId,
                'token_id' => $newToken->getTokenUUID()->composeUUID(),
            ],
            $token,
            $newTokenAsArray
        );
    }

    /**
     * Delete token.
     *
     * @param TokenUUID $tokenUUID
     * @param string    $appId
     * @param Token     $token
     */
    public static function deleteToken(
        TokenUUID $tokenUUID,
        string $appId = null,
        Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_delete_token',
            [
                'app_id' => $appId ?? static::$appId,
                'token' => $tokenUUID->composeUUID(),
            ],
            $token
        );
    }

    /**
     * Get tokens.
     *
     * @param string $appId
     * @param Token  $token
     *
     * @return Token[]
     */
    public static function getTokens(
        string $appId = null,
        Token $token = null
    ) {
        $response = self::makeCurl(
            'v1_get_tokens',
            [
                'app_id' => $appId ?? static::$appId,
            ],
            $token
        );
        self::$lastResponse = $response;

        return \array_map(function (array $tokenAsArray) {
            return Token::createFromArray($tokenAsArray);
        }, $response['body']);
    }

    /**
     * Delete token.
     *
     * @param string $appId
     * @param Token  $token
     */
    public static function deleteTokens(
        string $appId,
        Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_delete_tokens',
            [
                'app_id' => $appId ?? static::$appId,
            ],
            $token
        );
    }

    /**
     * @param string|null   $appId
     * @param Token|null    $token
     * @param string|null   $indexId
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $event
     * @param bool|null     $perDay
     *
     * @return array
     */
    public function getUsage(
        string $appId = null,
        ?Token $token = null,
        ?string $indexId = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $event = null,
        ?bool $perDay = false
    ): array {
        $routeParameters = ['app_id' => $appId ?? static::$appId];
        if ($indexId) {
            $routeParameters['index_id'] = $indexId;
        }

        $response = self::makeCurl(
            'v1_get_'.($indexId ? 'index_' : '').'usage'.($perDay ? '_per_day' : ''),
            $routeParameters,
            $token,
            [],
            \array_filter([
                'from' => (\is_null($from) ? false : $from->format('Ymd')),
                'to' => (\is_null($to) ? false : $to->format('Ymd')),
                'event' => $event ?? false,
            ])
        );
        self::$lastResponse = $response;

        return $response['body'];
    }

    /**
     * Add interaction.
     *
     * @param Interaction $interaction
     * @param string      $appId
     * @param Token       $token
     */
    public function addInteraction(
        Interaction $interaction,
        string $appId = null,
        Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_post_interaction',
            [
                'app_id' => $appId ?? static::$appId,
            ],
            $token,
            $interaction->toArray()
        );
    }

    /**
     * Ping.
     *
     * @param Token $token
     *
     * @return bool
     */
    public function ping(Token $token = null): bool
    {
        return false;
    }

    /**
     * Check health.
     *
     * @param Token $token
     *
     * @return array
     */
    public function checkHealth(Token $token = null): array
    {
        return [];
    }

    /**
     * Configure environment.
     */
    public static function configureEnvironment()
    {
        // Pass
    }

    /**
     * Clean environment.
     */
    public static function cleanEnvironment()
    {
        // Pass
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
        $headerSize = \curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseHeadersAsString = \substr($response, 0, $headerSize);
        $content = \substr($response, $headerSize);

        $responseCode = \curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $contentLength = \curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
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

        return $result;
    }
}
