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
use Apisearch\Server\Domain\Command\AddToken;
use Apisearch\Server\Domain\Command\CleanEnvironment;
use Apisearch\Server\Domain\Command\ConfigureEnvironment;
use Apisearch\Server\Domain\Command\ConfigureIndex;
use Apisearch\Server\Domain\Command\CreateIndex;
use Apisearch\Server\Domain\Command\DeleteIndex;
use Apisearch\Server\Domain\Command\DeleteItems;
use Apisearch\Server\Domain\Command\DeleteToken;
use Apisearch\Server\Domain\Command\DeleteTokens;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\Command\PauseConsumers;
use Apisearch\Server\Domain\Command\ResetIndex;
use Apisearch\Server\Domain\Command\ResumeConsumers;
use Apisearch\Server\Domain\Command\UpdateItems;
use Apisearch\Server\Domain\Query\CheckHealth;
use Apisearch\Server\Domain\Query\CheckIndex;
use Apisearch\Server\Domain\Query\GetIndices;
use Apisearch\Server\Domain\Query\GetTokens;
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
     * Save events.
     *
     * @return bool
     */
    protected static function tokensInRedis(): bool
    {
        return false;
    }

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

        return self::handleQueryAsynchronously(new Query(
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

        self::handleCommandAsynchronously(new DeleteItems(
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

        static::waitAfterWriteCommand();
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

        self::handleCommandAsynchronously(new IndexItems(
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

        static::waitAfterWriteCommand();
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

        self::handleCommandAsynchronously(new UpdateItems(
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

        static::waitAfterWriteCommand();
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

        self::handleCommandAsynchronously(new ResetIndex(
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

        static::waitAfterWriteCommand();
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

        return self::handleQueryAsynchronously(new GetIndices(
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

        self::handleCommandAsynchronously(new CreateIndex(
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

        static::waitAfterWriteCommand();
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

        self::handleCommandAsynchronously(new ConfigureIndex(
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

        static::waitAfterWriteCommand();
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

        return self::handleQueryAsynchronously(new CheckIndex(
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

        self::handleCommandAsynchronously(new DeleteIndex(
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

        static::waitAfterWriteCommand();
    }

    /**
     * Add token.
     *
     * @param Token  $newToken
     * @param string $appId
     * @param Token  $token
     */
    public static function addToken(
        Token $newToken,
        string $appId = null,
        Token $token = null
    ) {
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::handleCommandAsynchronously(new AddToken(
            RepositoryReference::create($appUUID),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $newToken
        ));

        static::waitAfterWriteCommand();
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

        self::handleCommandAsynchronously(new DeleteToken(
            RepositoryReference::create($appUUID),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $tokenUUID
        ));

        static::waitAfterWriteCommand();
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

        return self::handleQueryAsynchronously(new GetTokens(
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

        self::handleCommandAsynchronously(new DeleteTokens(
            RepositoryReference::create($appUUID),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                )
        ));

        static::waitAfterWriteCommand();
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

        self::handleCommandAsynchronously(new AddInteraction(
            RepositoryReference::create($appUUID),
            $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                ),
            $interaction
        ));

        static::waitAfterWriteCommand();
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
        return self::handleQueryAsynchronously(new Ping());
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
        return self::handleQueryAsynchronously(new CheckHealth());
    }

    /**
     * Configure environment.
     */
    public static function configureEnvironment()
    {
        self::handleCommandAsynchronously(new ConfigureEnvironment());

        static::waitAfterWriteCommand();
    }

    /**
     * Clean environment.
     */
    public static function cleanEnvironment()
    {
        self::handleCommandAsynchronously(new CleanEnvironment());

        static::waitAfterWriteCommand();
    }

    /**
     * Pause consumers.
     *
     * @param string[] $types
     */
    public function pauseConsumers(array $types)
    {
        self::handleCommandAsynchronously(new PauseConsumers($types));
    }

    /**
     * Resume consumers.
     *
     * @param string[] $types
     */
    public function resumeConsumers(array $types)
    {
        self::handleCommandAsynchronously(new ResumeConsumers($types));
    }

    /**
     * Handle command asynchronously.
     *
     * @param mixed $command
     */
    protected static function handleCommandAsynchronously($command)
    {
        $promise = self::getStatic('apisearch_server.command_bus')->handle($command);

        Block\await($promise, self::getStatic('reactphp.event_loop'));
    }

    /**
     * Handle command asynchronously.
     *
     * @param mixed $command
     *
     * @return mixed
     */
    protected static function handleQueryAsynchronously($command)
    {
        $promise = self::getStatic('apisearch_server.query_bus')->handle($command);

        return Block\await($promise, self::getStatic('reactphp.event_loop'));
    }
}
