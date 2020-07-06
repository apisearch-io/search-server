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
use Apisearch\Exception\ConnectionException;
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
use Apisearch\Server\Domain\Model\Origin;
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
     * @param Origin     $origin
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
        Origin $origin = null,
        array $headers = []
    ): Result {
        $origin = $origin ?? Origin::createEmpty();
        $route = '' === $index
            ? 'v1_query_all_indices'
            : 'v1_query';

        $headers[] = 'Origin: '.$origin->getHost();
        $headers[] = 'REMOTE_ADDR: '.$origin->getIp();
        $headers[] = 'User-Agent: '.$this->getUserAgentByPlatform($origin->getPlatform());

        $response = self::makeCurl(
            $route,
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
     * @param string $ip
     * @param string $appId
     * @param string $index
     *
     * @return string
     */
    public function getCORSPermissions(
        Origin $origin,
        string $appId = null,
        string $index = null
    ): string {
        $headers = [
            'Origin: '.$origin->getHost(),
            'REMOTE_ADDR: '.$origin->getIp(),
            'User-Agent: '.$this->getUserAgentByPlatform($origin->getPlatform()),
        ];

        if ('*' === $index) {
            $response = self::makeCurl(
                'v1_query_all_indices_preflight',
                [
                    'app_id' => $appId ?? static::$appId,
                ],
                null, [], [],
                $headers
            );
        } else {
            $response = self::makeCurl(
                'v1_query_preflight',
                [
                    'app_id' => $appId ?? static::$appId,
                    'index_id' => $index ?? static::$index,
                ],
                null, [], [],
                $headers
            );
        }

        self::$lastResponse = $response;

        return $response['headers']['access-control-allow-origin'] ?? '';
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
     * Import index.
     *
     * @param string $feed
     * @param bool   $detached
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    public function importIndex(
        string $feed,
        bool $detached = false,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        self::$lastResponse = $this->makeCurl(
            'v1_import_index',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            [],
            [
                'feed' => $feed,
                'detached' => $detached,
            ]
        );
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
     * @param QueryModel $query
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     */
    public function deleteItemsByQuery(
        QueryModel $query,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        self::$lastResponse = self::makeCurl(
            'v1_delete_items_by_query',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            $query->toArray()
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
     * @param bool   $forceReindex
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    public function configureIndex(
        Config $config,
        bool $forceReindex = false,
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
            $config->toArray(),
            [
                'force_reindex' => $forceReindex,
            ]
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
                'app_id' => $newToken->getAppUUID()->getId() ?? static::$appId,
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
                'token_id' => $tokenUUID->composeUUID(),
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
        string $appId = null,
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
     * @param string|null $userId
     * @param string      $itemId
     * @param int         $position
     * @param Origin      $origin
     * @param string      $appId
     * @param string      $indexId
     * @param Token       $token
     */
    public function click(
        ?string $userId,
        string $itemId,
        int $position,
        Origin $origin,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    ) {
        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $indexId ?? static::$index,
            'item_id' => $itemId,
        ];

        self::makeCurl(
            'v1_post_click',
            $routeParameters,
            $token,
            [],
            [
                'user_id' => $userId,
                'position' => $position,
            ],
            [
                'Origin:'.$origin->getHost(),
                'Remote_Addr:'.$origin->getIp(),
                'User_Agent:'.$this->getUserAgentByPlatform($origin->getPlatform()),
            ]
        );
    }

    /**
     * @param bool          $perDay
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param string|null   $itemId
     * @param string|null   $type
     * @param string|null   $count
     * @param string        $appId
     * @param string        $indexId
     * @param Token         $token
     *
     * @return int|int[]
     */
    public function getInteractions(
        bool $perDay,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        ?string $itemId = null,
        ?string $type = null,
        ?string $count = null,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    ) {
        $routeName = \is_null($indexId)
            ? ((false === $perDay)
                ? 'v1_get_interactions_all_indices'
                : 'v1_get_interactions_all_indices_per_day')
            : ((false === $perDay)
                ? 'v1_get_interactions'
                : 'v1_get_interactions_per_day');

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $indexId,
        ];

        $response = self::makeCurl(
            $routeName,
            $routeParameters,
            $token,
            [],
            [
                'from' => $from ? $from->format('Ymd') : null,
                'to' => $to ? $to->format('Ymd') : null,
                'user_id' => $userId,
                'platform' => $platform,
                'item_id' => $itemId,
                'type' => $type,
                'count' => $count,
            ]
        );
        self::$lastResponse = $response;

        return $response['body'];
    }

    /**
     * @param int|null      $n
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param string        $appId
     * @param string        $indexId
     * @param Token         $token
     *
     * @return int|int[]
     */
    public function getTopClicks(
        ?int $n = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    ) {
        $routeName = \is_null($indexId)
            ? 'v1_get_top_clicks_all_indices'
            : 'v1_get_top_clicks';

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $indexId,
        ];

        $response = self::makeCurl(
            $routeName,
            $routeParameters,
            $token,
            [],
            [
                'from' => $from ? $from->format('Ymd') : null,
                'to' => $to ? $to->format('Ymd') : null,
                'user_id' => $userId,
                'platform' => $platform,
                'n' => $n,
            ]
        );
        self::$lastResponse = $response;

        return $response['body'];
    }

    /**
     * @param bool          $perDay
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param bool          $excludeWithResults
     * @param bool          $excludeWithoutResults
     * @param string|null   $count
     * @param string        $appId
     * @param string        $indexId
     * @param Token         $token
     *
     * @return int|int[]
     */
    public function getSearches(
        bool $perDay,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        bool $excludeWithResults = false,
        bool $excludeWithoutResults = false,
        ?string $count = null,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    ) {
        $routeName = \is_null($indexId)
            ? ((false === $perDay)
                ? 'v1_get_searches_all_indices'
                : 'v1_get_searches_all_indices_per_day')
            : ((false === $perDay)
                ? 'v1_get_searches'
                : 'v1_get_searches_per_day');

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $indexId,
        ];

        $response = self::makeCurl(
            $routeName,
            $routeParameters,
            $token,
            [],
            [
                'from' => $from ? $from->format('Ymd') : null,
                'to' => $to ? $to->format('Ymd') : null,
                'user_id' => $userId,
                'platform' => $platform,
                'exclude_with_results' => $excludeWithResults,
                'exclude_without_results' => $excludeWithoutResults,
                'count' => $count,
            ]
        );
        self::$lastResponse = $response;

        return $response['body'];
    }

    /**
     * @param int|null      $n
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $platform
     * @param string|null   $userId
     * @param bool          $excludeWithResults
     * @param bool          $excludeWithoutResults
     * @param string        $appId
     * @param string        $indexId
     * @param Token         $token
     */
    public function getTopSearches(
        ?int $n = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $platform = null,
        ?string $userId = null,
        bool $excludeWithResults = false,
        bool $excludeWithoutResults = false,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    ) {
        $routeName = \is_null($indexId)
            ? 'v1_get_top_searches_all_indices'
            : 'v1_get_top_searches';

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $indexId,
        ];

        $response = self::makeCurl(
            $routeName,
            $routeParameters,
            $token,
            [],
            [
                'from' => $from ? $from->format('Ymd') : null,
                'to' => $to ? $to->format('Ymd') : null,
                'platform' => $platform,
                'user_id' => $userId,
                'exclude_with_results' => $excludeWithResults,
                'exclude_without_results' => $excludeWithoutResults,
                'n' => $n,
            ]
        );
        self::$lastResponse = $response;

        return $response['body'];
    }

    /**
     * @param int|null      $n
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param string        $appId
     * @param string        $indexId
     * @param Token         $token
     *
     * @return int|int[]
     */
    public function getMetrics(
        ?int $n = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    ) {
        $routeName = \is_null($indexId)
            ? 'v1_get_metrics_all_indices'
            : 'v1_get_metrics';

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $indexId,
        ];

        $response = self::makeCurl(
            $routeName,
            $routeParameters,
            $token,
            [],
            [
                'from' => $from ? $from->format('Ymd') : null,
                'to' => $to ? $to->format('Ymd') : null,
                'user_id' => $userId,
                'platform' => $platform,
                'n' => $n,
            ]
        );
        self::$lastResponse = $response;

        return $response['body'];
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
        $response = self::makeCurl(
            'check_health',
            [
                'optimize' => true,
            ],
            $token
        );
        self::$lastResponse = $response;

        return $response['body'];
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

    /**
     * @param string $platform
     *
     * @return string
     */
    private function getUserAgentByPlatform(string $platform): string
    {
        return Origin::PHONE == $platform
            ? 'Mozilla/5.0 (Linux; Android 6.0.1; RedMi Note 5 Build/RB3N5C; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/68.0.3440.91 Mobile Safari/537.36'
            : (
            Origin::TABLET == $platform
                ? 'Mozilla/5.0 (iPad; CPU OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148'
                : (
                    Origin::DESKTOP == $platform
                        ? 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0'
                        : ''
                )
            );
    }
}
