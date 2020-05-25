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

use Apisearch\App\AppRepository;
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
use Apisearch\Repository\Repository;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\User\Interaction;
use Apisearch\User\UserRepository;
use Exception;

/**
 * Class HttpFunctionalTest.
 */
abstract class HttpFunctionalTest extends ApisearchServerBundleFunctionalTest
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
        return self::configureRepository($appId, $index, $token)
            ->query($query, $parameters);
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
        string $origin,
        string $ip,
        string $appId = null,
        string $index = null
    ): string {
        $this->markTestSkipped('Method not allowed. Skipping');
    }

    /**
     * Export index.
     *
     * @param bool   $closeImmediately
     * @param string $appId
     * @param string $index
     * @param Token  $token
     *
     * @return Item[]
     */
    public function exportIndex(
        bool $closeImmediately = false,
        string $appId = null,
        string $index = null,
        Token $token = null
    ): array {
        $this->markTestSkipped('Method not allowed. Skipping');
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
        $repository = self::configureRepository($appId, $index, $token);
        foreach ($itemsUUID as $itemUUID) {
            $repository->deleteItem($itemUUID);
        }
        $repository->flush();
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
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        $repository = self::configureRepository($appId, $index, $token);
        foreach ($items as $item) {
            $repository->addItem($item);
        }
        $repository->flush();
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
        self::configureRepository($appId, $index, $token)
            ->updateItems($query, $changes);
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
        self::configureAppRepository($appId, $token)
            ->resetIndex(
                IndexUUID::createById($index ?? static::$index)
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
        return self::configureAppRepository($appId, $token)
            ->getIndices();
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
        self::configureAppRepository($appId, $token)
            ->createIndex(
                IndexUUID::createById($index ?? static::$index),
                $config ?? Config::createFromArray([])
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
        self::configureAppRepository($appId, $token)
            ->configureIndex(
                IndexUUID::createById($index ?? static::$index),
                $config
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
        return self::configureAppRepository($appId, $token)
            ->checkIndex(
                IndexUUID::createById($index ?? static::$index)
            );
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
        self::configureAppRepository($appId, $token)
            ->deleteIndex(
                IndexUUID::createById($index ?? static::$index)
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
        self::configureAppRepository($appId, $token)
            ->putToken($newToken);
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
        self::configureAppRepository($appId, $token)
            ->deleteToken($tokenUUID);
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
        return self::configureAppRepository($appId, $token)
            ->getTokens();
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
        return self::configureAppRepository($appId, $token)
            ->deleteTokens();
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
        throw new \Exception('Function getUsage not usable in HttpFunctionalTest class');
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
        self::configureUserRepository($appId, $token)
            ->addInteraction($interaction);
    }

    /**
     * Ping.
     *
     * @param Token $token
     *
     * @return bool
     *
     * @throws Exception
     */
    public function ping(Token $token = null): bool
    {
        $this->markTestSkipped('Method not allowed. Skipping');
    }

    /**
     * Check health.
     *
     * @param Token $token
     *
     * @return array
     *
     * @throws Exception
     */
    public function checkHealth(Token $token = null): array
    {
        $this->markTestSkipped('Method not allowed. Skipping');
    }

    /**
     * Configure environment.
     *
     * @throws Exception
     */
    public static function configureEnvironment()
    {
        // Nothing to do here
    }

    /**
     * Clean environment.
     *
     * @throws Exception
     */
    public static function cleanEnvironment()
    {
        // Nothing to do here
    }

    /**
     * Configure repository.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     *
     * @return Repository
     */
    private static function configureRepository(
        string $appId = null,
        string $index = null,
        Token $token = null
    ): Repository {
        $index = $index ?? self::$index;
        $realIndex = empty($index) ? self::$index : $index;

        return self::configureAbstractRepository(
            \rtrim('apisearch.repository_'.static::getRepositoryName().'.'.$realIndex, '.'),
            $appId,
            $index,
            $token
        );
    }

    /**
     * Configure app repository.
     *
     * @param string $appId
     * @param Token  $token
     *
     * @return AppRepository
     */
    private static function configureAppRepository(
        string $appId = null,
        Token $token = null
    ): AppRepository {
        return self::configureAbstractRepository(
            'apisearch.app_repository_'.static::getRepositoryName(),
            $appId,
            '*',
            $token
        );
    }

    /**
     * Configure user repository.
     *
     * @param string $appId
     * @param Token  $token
     *
     * @return UserRepository
     */
    private static function configureUserRepository(
        string $appId = null,
        Token $token = null
    ): UserRepository {
        return self::configureAbstractRepository(
            'apisearch.user_repository_'.static::getRepositoryName(),
            $appId,
            '*',
            $token
        );
    }

    /**
     * Configure abstract repository.
     *
     * @param string $repositoryName
     * @param string $appId
     * @param string $index
     * @param Token  $token
     *
     * @return mixed
     */
    private static function configureAbstractRepository(
        string $repositoryName,
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        $repository = self::getStatic($repositoryName);
        $repository->setCredentials(
            RepositoryReference::create(
                AppUUID::createById($appId ?? self::$appId),
                IndexUUID::createById($index ?? self::$index)
            ),
            $token
                ? $token->getTokenUUID()
                : TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token'))
        );

        return $repository;
    }

    /**
     * Get repository name.
     *
     * @return string
     */
    protected static function getRepositoryName(): string
    {
        return 'search_http';
    }
}
