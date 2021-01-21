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
use Exception;
use RingCentral\Psr7\Response as PsrResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

/**
 * Class HttpFunctionalTest.
 */
abstract class HttpFunctionalTest extends ApisearchServerBundleFunctionalTest
{
    use HttpResponsesToException;
    protected static array $lastResponse = [];

    /**
     * Query using the bus.
     *
     * @param QueryModel  $query
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     * @param array       $parameters
     * @param Origin|null $origin
     * @param array       $headers
     *
     * @return Result
     *
     * @throws Exception
     */
    public function query(
        QueryModel $query,
        ?string $appId = null,
        ?string $index = null,
        ?Token $token = null,
        array $parameters = [],
        ?Origin $origin = null,
        array $headers = []
    ): Result {
        $origin = $origin ?? Origin::createEmpty();
        $route = '' === $index
            ? 'v1_query_all_indices'
            : 'v1_query';

        $parameters['user_id'] = $query->getUser() ? $query->getUser()->getId() : null;
        $headers['Origin'] = $origin->getHost();
        $headers['Remote_Addr'] = $origin->getIp();
        $headers['User_Agent'] = $this->getUserAgentByPlatform($origin->getPlatform());

        $response = static::request(
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

        return Result::createFromArray($response['body']);
    }

    /**
     * @param Origin      $origin
     * @param string|null $appId
     * @param string|null $index
     *
     * @return string
     *
     * @throws Exception
     */
    public function getCORSPermissions(
        Origin $origin,
        string $appId = null,
        string $index = null
    ): string {
        $headers = [
            'Origin' => $origin->getHost(),
            'Remote_Addr' => $origin->getIp(),
            'User_Agent' => $this->getUserAgentByPlatform($origin->getPlatform()),
        ];

        if ('*' === $index) {
            $response = static::request(
                'v1_query_all_indices_preflight',
                [
                    'app_id' => $appId ?? static::$appId,
                ],
                null, [], [],
                $headers
            );
        } else {
            $response = static::request(
                'v1_query_preflight',
                [
                    'app_id' => $appId ?? static::$appId,
                    'index_id' => $index ?? static::$index,
                ],
                null, [], [],
                $headers
            );
        }

        return $response['headers']['access-control-allow-origin']
            ? $response['headers']['access-control-allow-origin'][0]
            : '';
    }

    /**
     * Export index.

     *
     * @param string      $format
     * @param bool        $closeImmediately
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
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
        return [];
    }

    /**
     * Import index by feed.
     *
     * @param string      $feed
     * @param bool        $detached
     * @param bool        $deleteOldVersions
     * @param string|null $version
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public function importIndexByFeed(
        string $feed,
        bool $detached = false,
        bool $deleteOldVersions = false,
        ?string $version = null,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        static::request(
            'v1_import_index_by_feed',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $index ?? static::$index,
            ],
            $token,
            [],
            [
                'feed' => $feed,
                'detached' => $detached,
                'delete_old_versions' => $deleteOldVersions,
                'version' => $version,
            ]
        );
    }

    /**
     * Delete using the bus.
     *
     * @param ItemUUID[]  $itemsUUID
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public function deleteItems(
        array $itemsUUID,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        static::request(
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
     * @param QueryModel  $query
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public function deleteItemsByQuery(
        QueryModel $query,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        static::request(
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
     * @param Item[]      $items
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public static function indexItems(
        array $items,
        ?string $appId = null,
        ?string $index = null,
        ?Token $token = null
    ) {
        static::request(
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
     * @param QueryModel  $query
     * @param Changes     $changes
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public function updateItems(
        QueryModel $query,
        Changes $changes,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        static::request(
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
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public function resetIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        static::request(
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
     * @param Token |null $token
     *
     * @return Index[]
     *
     * @throws Exception
     */
    public function getIndices(
        string $appId = null,
        Token $token = null
    ): array {
        $response = static::request(
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

        return $indices;
    }

    /**
     * Create index using the bus.
     *
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     * @param Config|null $config
     *
     * @return void
     *
     * @throws Exception
     */
    public static function createIndex(
        string $appId = null,
        string $index = null,
        Token $token = null,
        Config $config = null
    ) {
        static::request(
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
     * @param Config      $config
     * @param bool        $forceReindex
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public function configureIndex(
        Config $config,
        bool $forceReindex = false,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        static::request(
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
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return bool
     *
     * @throws Exception
     */
    public function checkIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    ): bool {
        try {
            $response = static::request(
                'v1_check_index',
                [
                    'app_id' => $appId ?? static::$appId,
                    'index_id' => $index ?? static::$index,
                ],
                $token,
                []
            );
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
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public static function deleteIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        static::request(
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
     * @param Token       $newToken
     * @param string|null $appId
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public static function putToken(
        Token $newToken,
        string $appId = null,
        Token $token = null
    ) {
        $newTokenAsArray = $newToken->toArray();
        unset($newTokenAsArray['uuid']);

        static::request(
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
     * @param TokenUUID   $tokenUUID
     * @param string|null $appId
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public static function deleteToken(
        TokenUUID $tokenUUID,
        string $appId = null,
        Token $token = null
    ) {
        static::request(
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
     * @param string|null $appId
     * @param Token|null  $token
     *
     * @return Token[]
     *
     * @throws Exception
     */
    public static function getTokens(
        string $appId = null,
        Token $token = null
    ) {
        $response = static::request(
            'v1_get_tokens',
            [
                'app_id' => $appId ?? static::$appId,
            ],
            $token
        );

        return \array_map(function (array $tokenAsArray) {
            return Token::createFromArray($tokenAsArray);
        }, $response['body']);
    }

    /**
     * Delete token.
     *
     * @param string|null $appId
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public static function deleteTokens(
        string $appId = null,
        Token $token = null
    ) {
        static::request(
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
     * @param string|null   $index
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $event
     * @param bool|null     $perDay
     *
     * @return array
     *
     * @throws Exception
     */
    public function getUsage(
        string $appId = null,
        ?Token $token = null,
        ?string $index = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $event = null,
        ?bool $perDay = false
    ): array {
        $routeParameters = ['app_id' => $appId ?? static::$appId];
        if ($index) {
            $routeParameters['index_id'] = $index;
        }

        $response = static::request(
            'v1_get_'.($index ? 'index_' : '').'usage'.($perDay ? '_per_day' : ''),
            $routeParameters,
            $token,
            [],
            \array_filter([
                'from' => (\is_null($from) ? false : $from->format('Ymd')),
                'to' => (\is_null($to) ? false : $to->format('Ymd')),
                'event' => $event ?? false,
            ])
        );

        return $response['body']['data'];
    }

    /**
     * @param string|null   $appId
     * @param Token|null    $token
     * @param string|null   $index
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string[]      $types
     * @param int           $limit
     * @param int           $page
     *
     * @return array
     *
     * @throws Exception
     */
    public function getLogs(
        string $appId = null,
        ?Token $token = null,
        ?string $index = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        array $types = [],
        int $limit = 0,
        int $page = 0
    ): array {
        $routeParameters = ['app_id' => $appId ?? static::$appId];
        if ($index) {
            $routeParameters['index_id'] = $index;
        }

        $response = static::request(
            'v1_get_'.($index ? 'index_' : '').'logs',
            $routeParameters,
            $token,
            [],
            \array_filter([
                'from' => (\is_null($from) ? false : $from->format('Ymd')),
                'to' => (\is_null($to) ? false : $to->format('Ymd')),
                'types' => $types,
                'limit' => $limit,
                'page' => $page,
            ])
        );

        return $response['body']['data'];
    }

    /**
     * Add interaction.
     *
     * @param string|null $userId
     * @param string      $itemId
     * @param int         $position
     * @param string|null $context
     * @param Origin      $origin
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     *
     * @throws Exception
     */
    public function click(
        ?string $userId,
        string $itemId,
        int $position,
        ?string $context,
        Origin $origin,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $index ?? static::$index,
            'item_id' => $itemId,
        ];

        static::request(
            'v1_post_click',
            $routeParameters,
            $token,
            [],
            [
                'user_id' => $userId,
                'position' => $position,
                'context' => $context,
            ],
            [
                'Origin' => $origin->getHost(),
                'Remote_Addr' => $origin->getIp(),
                'User_Agent' => $this->getUserAgentByPlatform($origin->getPlatform()),
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
     * @param string|null   $context
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return int|int[]
     *
     * @throws Exception
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
        ?string $context = null,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        $routeName = \is_null($index)
            ? ((false === $perDay)
                ? 'v1_get_interactions_all_indices'
                : 'v1_get_interactions_all_indices_per_day')
            : ((false === $perDay)
                ? 'v1_get_interactions'
                : 'v1_get_interactions_per_day');

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $index,
        ];

        $response = static::request(
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
                'context' => $context,
            ]
        );

        return $response['body']['data'];
    }

    /**
     * @param int|null      $n
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return int|int[]
     *
     * @throws Exception
     */
    public function getTopClicks(
        ?int $n = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        $routeName = \is_null($index)
            ? 'v1_get_top_clicks_all_indices'
            : 'v1_get_top_clicks';

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $index,
        ];

        $response = static::request(
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

        return $response['body']['data'];
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
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return int|int[]
     *
     * @throws Exception
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
        string $index = null,
        Token $token = null
    ) {
        $routeName = \is_null($index)
            ? ((false === $perDay)
                ? 'v1_get_searches_all_indices'
                : 'v1_get_searches_all_indices_per_day')
            : ((false === $perDay)
                ? 'v1_get_searches'
                : 'v1_get_searches_per_day');

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $index,
        ];

        $response = static::request(
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

        return $response['body']['data'];
    }

    /**
     * @param int|null      $n
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $platform
     * @param string|null   $userId
     * @param bool          $excludeWithResults
     * @param bool          $excludeWithoutResults
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return array
     *
     * @throws Exception
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
        string $index = null,
        Token $token = null
    ): array {
        $routeName = \is_null($index)
            ? 'v1_get_top_searches_all_indices'
            : 'v1_get_top_searches';

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $index,
        ];

        $response = static::request(
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

        return $response['body']['data'];
    }

    /**
     * @param int|null      $n
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param string|null   $context
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return int|int[]
     *
     * @throws Exception
     */
    public function getMetrics(
        ?int $n = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        ?string $context = null,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        $routeName = \is_null($index)
            ? 'v1_get_metrics_all_indices'
            : 'v1_get_metrics';

        $routeParameters = [
            'app_id' => $appId ?? static::$appId,
            'index_id' => $index,
        ];

        $response = static::request(
            $routeName,
            $routeParameters,
            $token,
            [],
            [
                'from' => $from ? $from->format('Ymd') : null,
                'to' => $to ? $to->format('Ymd') : null,
                'user_id' => $userId,
                'platform' => $platform,
                'context' => $context,
                'n' => $n,
            ]
        );

        return $response['body'];
    }

    /**
     * @param Token|null $token
     *
     * @return bool
     *
     * @throws Exception
     */
    public function ping(Token $token = null): bool
    {
        $response = static::request(
            'ping', [],
            $token
        );

        return 200 === $response['code'];
    }

    /**
     * Check health.
     *
     * @param Token|null $token
     *
     * @return array
     *
     * @throws Exception
     */
    public function checkHealth(Token $token = null): array
    {
        $response = static::request(
            'check_health',
            [
                'optimize' => true,
            ],
            $token
        );

        return $response['body'];
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
     *
     * @throws Exception
     */
    protected static function request(
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

        $method = $route instanceof Route
            ? $route->getMethods()[0]
            : 'GET';

        $queryParameters['token'] = $token
                ? $token->getTokenUUID()->composeUUID()
                : self::getParameterStatic('apisearch_server.god_token');

        $headerKeys = \array_map(function (string $headerKey) {
            return 'HTTP_'.\strtoupper($headerKey);
        }, \array_keys($headers));
        $headers = \array_combine(\array_values($headerKeys), \array_values($headers));

        if (!isset($headers['HTTP_REFERER'])) {
            $headers['HTTP_REFERER'] = 'http://localhost';
        }

        $body = \is_string($body)
            ? $body
            : \json_encode($body);

        if (!empty($body)) {
            $headers['CONTENT_TYPE'] = 'application/json';
            $headers['CONTENT_LENGTH'] = \strlen($body);
        }

        $queryParameters = \array_filter($queryParameters, function ($item) {
            return !empty($item);
        });

        $request = new Request(
            $queryParameters, // query
            [], // requests
            [], // attributes
            [], // cookies
            [], // files
            $headers, // server
            $body  // content
        );

        $request->setMethod($method);
        $request->server->set('REQUEST_URI', $routePath);

        $responsePromise = self::$kernel->handleAsync($request);
        $response = self::await($responsePromise);

        if ($response instanceof PsrResponse) {
            $content = $response->getBody()->getContents();
            $result = [
                'code' => $response->getStatusCode(),
                'body' => \json_decode($content, true) ?? $content,
                'headers' => $response->getHeaders(),
                'length' => \strlen($content),
            ];
        } elseif ($response instanceof Response) {
            $content = $response->getContent();
            $result = [
                'code' => $response->getStatusCode(),
                'body' => \json_decode($content, true) ?? $content,
                'headers' => $response->headers->all(),
                'length' => \strlen($content),
            ];
        } else {
            throw new Exception('Invalid response type');
        }

        if (\is_string($result['body'])) {
            $result['body'] = ['message' => $result['body']];
        }

        self::$lastResponse = $result;

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
