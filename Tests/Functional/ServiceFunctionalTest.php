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
use Apisearch\Server\Domain\Command\AddInteraction;
use Apisearch\Server\Domain\Command\CleanEnvironment;
use Apisearch\Server\Domain\Command\ConfigureEnvironment;
use Apisearch\Server\Domain\Command\ConfigureIndex;
use Apisearch\Server\Domain\Command\CreateIndex;
use Apisearch\Server\Domain\Command\DeleteIndex;
use Apisearch\Server\Domain\Command\DeleteItems;
use Apisearch\Server\Domain\Command\DeleteToken;
use Apisearch\Server\Domain\Command\DeleteTokens;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\Command\PutToken;
use Apisearch\Server\Domain\Command\ResetIndex;
use Apisearch\Server\Domain\Command\UpdateItems;
use Apisearch\Server\Domain\Query\CheckHealth;
use Apisearch\Server\Domain\Query\CheckIndex;
use Apisearch\Server\Domain\Query\GetCORSPermissions;
use Apisearch\Server\Domain\Query\GetIndices;
use Apisearch\Server\Domain\Query\GetTokens;
use Apisearch\Server\Domain\Query\GetUsage;
use Apisearch\Server\Domain\Query\Ping;
use Apisearch\Server\Domain\Query\Query;
use Apisearch\User\Interaction;
use Clue\React\Block;

/**
 * Class ServiceFunctionalTest.
 */
abstract class ServiceFunctionalTest extends ApisearchServerBundleFunctionalTest
{
    /**
     * Query using the bus.
     *
     * @param QueryModel $query
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     * @param array      $parameters
     *
     * @return Result
     */
    public function query(
        QueryModel $query,
        string $appId = null,
        string $index = null,
        Token $token = null,
        array $parameters = []
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
                $parameters
            ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);
        $indexUUID = IndexUUID::createById($index ?? self::$index);

        return self::askQuery(new GetCORSPermissions(
            RepositoryReference::create($appUUID, $indexUUID),
            $origin
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
            $config
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
     * @param string|null $appId
     * @param Token       $token
     *
     * @return array
     */
    public function getUsage(
        string $appId = null,
        Token $token = null
    ): array {
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        return self::askQuery(new GetUsage(
            RepositoryReference::create($appUUID),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                $appUUID
            )
        ));
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
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new AddInteraction(
            RepositoryReference::create($appUUID),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $interaction
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
        return self::askQuery(new CheckHealth());
    }

    /**
     * Configure environment.
     */
    public static function configureEnvironment()
    {
        self::executeCommand(new ConfigureEnvironment());
    }

    /**
     * Clean environment.
     */
    public static function cleanEnvironment()
    {
        self::executeCommand(new CleanEnvironment());
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
