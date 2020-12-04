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
use Apisearch\Model\AppUUID;
use Apisearch\Model\Changes;
use Apisearch\Model\Index;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query as QueryModel;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Command\ConfigureIndex;
use Apisearch\Server\Domain\Command\CreateIndex;
use Apisearch\Server\Domain\Command\DeleteIndex;
use Apisearch\Server\Domain\Command\DeleteItems;
use Apisearch\Server\Domain\Command\DeleteItemsByQuery;
use Apisearch\Server\Domain\Command\DeleteToken;
use Apisearch\Server\Domain\Command\DeleteTokens;
use Apisearch\Server\Domain\Command\ImportIndexByFeed;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\Command\PostInteraction;
use Apisearch\Server\Domain\Command\PutToken;
use Apisearch\Server\Domain\Command\ResetIndex;
use Apisearch\Server\Domain\Command\UpdateItems;
use Apisearch\Server\Domain\ImperativeEvent\FlushInteractions;
use Apisearch\Server\Domain\ImperativeEvent\FlushUsageLines;
use Apisearch\Server\Domain\Model\InteractionType;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Query\CheckHealth;
use Apisearch\Server\Domain\Query\CheckIndex;
use Apisearch\Server\Domain\Query\ExportIndex;
use Apisearch\Server\Domain\Query\GetCORSPermissions;
use Apisearch\Server\Domain\Query\GetIndices;
use Apisearch\Server\Domain\Query\GetInteractions;
use Apisearch\Server\Domain\Query\GetLogs;
use Apisearch\Server\Domain\Query\GetSearches;
use Apisearch\Server\Domain\Query\GetTokens;
use Apisearch\Server\Domain\Query\GetTopInteractions;
use Apisearch\Server\Domain\Query\GetTopSearches;
use Apisearch\Server\Domain\Query\GetUsage;
use Apisearch\Server\Domain\Query\Ping;
use Apisearch\Server\Domain\Query\Query;
use Apisearch\Server\Domain\Repository\LogRepository\LogFilter;
use Apisearch\Server\Domain\Repository\LogRepository\LogWithText;
use Clue\React\Block;
use DateTime;
use Ramsey\Uuid\UuidFactory;
use React\Promise\Deferred;

/**
 * Class ServiceFunctionalTest.
 */
abstract class ServiceFunctionalTest extends ApisearchServerBundleFunctionalTest
{
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        return self::askQuery(new Query(
                RepositoryReference::create(
                    $appUUID,
                    IndexUUID::createById($index ?? self::$index)
                ),
                $token ??
                    new Token(
                        TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                        $appUUID
                    ),
                $query,
                $origin ?? Origin::createEmpty(),
                $query->getUser() ? $query->getUser()->getId() : null,
                $parameters
            ));
    }

    /**
     * Preflight CORS query.
     *
     * @param Origin $origin
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);
        $indexUUID = IndexUUID::createById($index ?? self::$index);

        return self::askQuery(new GetCORSPermissions(
            RepositoryReference::create($appUUID, $indexUUID),
            $origin
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        $stream = self::askQuery(new ExportIndex(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $format
        ));

        $deferred = new Deferred();
        $rows = [];
        $stream->on('data', function (string $row) use (&$rows) {
            $rows[] = $row;
        });

        $stream->on('end', function () use (&$rows, $deferred) {
            $deferred->resolve($rows);
        });

        return $this->await($deferred->promise());
    }

    /**
     * Import index by feed.
     *
     * @param string      $feed
     * @param bool        $detached
     * @param bool        $deleteOldVersions
     * @param string|null $version
     * @param string      $appId
     * @param string      $index
     * @param Token       $token
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);
        self::executeCommand(new ImportIndexByFeed(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $deleteOldVersions,
            $version ?? (new UuidFactory())->uuid4()->toString(),
            $feed
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new DeleteItems(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $itemsUUID
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new DeleteItemsByQuery(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                $appUUID
            ),
            $query
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new IndexItems(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $items
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new UpdateItems(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $query,
            $changes
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);
        $indexUUID = IndexUUID::createById($index ?? self::$index);

        self::executeCommand(new ResetIndex(
            RepositoryReference::create(
                $appUUID,
                $indexUUID
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $indexUUID
        ));
    }

    /**
     * @param string|null $appId
     *
     * @return array|Index[]
     *
     * @param Token $token
     */
    public function getIndices(
        string $appId = null,
        Token $token = null
    ): array {
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        return self::askQuery(new GetIndices(
            RepositoryReference::create(
                $appUUID
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                )
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);
        $indexUUID = IndexUUID::createById($index ?? self::$index);

        self::executeCommand(new CreateIndex(
            RepositoryReference::create(
                $appUUID,
                $indexUUID
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $indexUUID,
            $config ?? Config::createFromArray([])
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);
        $indexUUID = IndexUUID::createById($index ?? self::$index);

        self::executeCommand(new ConfigureIndex(
            RepositoryReference::create(
                $appUUID,
                $indexUUID
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $indexUUID,
            $config,
            $forceReindex
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);
        $indexUUID = IndexUUID::createById($index ?? self::$index);

        return self::askQuery(new CheckIndex(
            RepositoryReference::create(
                $appUUID,
                $indexUUID
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $indexUUID
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);
        $indexUUID = IndexUUID::createById($index ?? self::$index);

        self::executeCommand(new DeleteIndex(
            RepositoryReference::create(
                $appUUID,
                $indexUUID
            ),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $indexUUID
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new PutToken(
            RepositoryReference::create($appUUID),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $newToken
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new DeleteToken(
            RepositoryReference::create($appUUID),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $tokenUUID
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        return self::askQuery(new GetTokens(
            RepositoryReference::create($appUUID),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                )
        ));
    }

    /**
     * Delete all tokens.
     *
     * @param string $appId
     * @param Token  $token
     */
    public static function deleteTokens(
        string $appId = null,
        Token $token = null
    ) {
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new DeleteTokens(
            RepositoryReference::create($appUUID),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                )
        ));
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
        $appId = $appId ?? self::$appId;
        $this->dispatchImperative(new FlushUsageLines());
        self::usleep(100000);

        return self::askQuery(new GetUsage(
            RepositoryReference::createFromComposed("{$appId}_{$indexId}"),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                AppUUID::createById($appId)
            ),
            $from ?? (new DateTime('first day of this month')),
            $to,
            $event,
            $perDay ?? false
        ));
    }

    /**
     * @param string|null   $appId
     * @param Token|null    $token
     * @param string|null   $indexId
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string[]      $types
     * @param int           $limit
     * @param int           $page
     *
     * @return array
     */
    public function getLogs(
        string $appId = null,
        ?Token $token = null,
        ?string $indexId = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        array $types = [],
        int $limit = 0,
        int $page = 0
    ): array {
        $appId = $appId ?? self::$appId;
        $repositoryReference = RepositoryReference::createFromComposed("{$appId}_{$indexId}");

        $logsWithText = self::askQuery(new GetLogs(
            $repositoryReference,
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                AppUUID::createById($appId)
            ),
            LogFilter::create($repositoryReference)
                ->from($from)
                ->to($to)
                ->fromTypes($types)
                ->paginate($limit, $page)
        ));

        return \array_map(function (LogWithText $logWithText) {
            return $logWithText->toArray();
        }, $logsWithText);
    }

    /**
     * @param string|null $userId
     * @param string      $itemId
     * @param int         $position
     * @param string|null $context
     * @param Origin      $origin
     * @param string      $appId
     * @param string      $indexId
     * @param Token       $token
     */
    public function click(
        ?string $userId,
        string $itemId,
        int $position,
        ?string $context,
        Origin $origin,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    ) {
        $appId = $appId ?? self::$appId;
        $indexId = $indexId ?? self::$index;

        self::executeCommand(new PostInteraction(
            RepositoryReference::createFromComposed("{$appId}_{$indexId}"),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                AppUUID::createById($appId)
            ),
            $userId,
            ItemUUID::createByComposedUUID($itemId),
            $position,
            $context,
            $origin,
            InteractionType::CLICK
        ));

        $this->dispatchImperative(new FlushInteractions());
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
        ?string $context = null,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    ) {
        $appId = $appId ?? self::$appId;
        $indexId = $indexId ?? '';

        return self::askQuery(new GetInteractions(
            RepositoryReference::createFromComposed("{$appId}_{$indexId}"),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                AppUUID::createById($appId)
            ),
            $from,
            $to,
            $perDay,
            $platform,
            $userId,
            $itemId,
            $type,
            $count,
            $context
        ));
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
        $appId = $appId ?? self::$appId;
        $indexId = $indexId ?? '';

        return self::askQuery(new GetTopInteractions(
            RepositoryReference::createFromComposed("{$appId}_{$indexId}"),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                AppUUID::createById($appId)
            ),
            $from,
            $to,
            $platform,
            $userId,
            InteractionType::CLICK,
            $n
        ));
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
        $appId = $appId ?? self::$appId;
        $indexId = $indexId ?? '';

        return self::askQuery(new GetSearches(
            RepositoryReference::createFromComposed("{$appId}_{$indexId}"),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                AppUUID::createById($appId)
            ),
            $from,
            $to,
            $perDay,
            $platform,
            $userId,
            $excludeWithResults,
            $excludeWithoutResults,
            $count
        ));
    }

    /**
     * @param int|null
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
        $appId = $appId ?? self::$appId;
        $indexId = $indexId ?? '';

        return self::askQuery(new GetTopSearches(
            RepositoryReference::createFromComposed("{$appId}_{$indexId}"),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                AppUUID::createById($appId)
            ),
            $from,
            $to,
            $platform,
            $userId,
            $excludeWithResults,
            $excludeWithoutResults,
            $n
        ));
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
        return self::askQuery(new Ping());
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
        $healthCheckData = self::askQuery(new CheckHealth());

        return Block\await($healthCheckData->getData(), self::getStatic('reactphp.event_loop'));
    }

    /**
     * Handle command asynchronously.
     *
     * @param mixed $command
     */
    protected static function executeCommand($command)
    {
        $promise = self::getStatic('drift.command_bus.test')->execute($command);

        Block\await($promise, self::getStatic('reactphp.event_loop'));
    }

    /**
     * Handle command asynchronously.
     *
     * @param mixed $command
     *
     * @return mixed
     */
    protected static function askQuery($query)
    {
        $promise = self::getStatic('drift.query_bus.test')->ask($query);

        return Block\await($promise, self::getStatic('reactphp.event_loop'));
    }
}
