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

namespace Apisearch\Server\Tests\Functional\Domain\Token;

use Apisearch\Exception\InvalidTokenException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\GetTokens;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class TokenTest.
 */
abstract class TokenTest extends HttpFunctionalTest
{
    /**
     * Is distributed token respository.
     */
    abstract public function isDistributedTokenRepository(): bool;

    /**
     * Test token creation.
     *
     * @return void
     */
    public function testTokenCreation(): void
    {
        $token = new Token(TokenUUID::createById('12345'), AppUUID::createById(self::$appId));
        $this->putToken($token);
        $this->assertTrue($this->checkIndex(
            null,
            null,
            new Token(TokenUUID::createById('12345'), AppUUID::createById(self::$appId))
        ));

        $this->deleteToken(TokenUUID::createById('12345'));
        $this->expectException(InvalidTokenException::class);
        $this->checkIndex(
            null,
            null,
            new Token(TokenUUID::createById('12345'), AppUUID::createById(self::$appId))
        );
    }

    /**
     * Test token without index permissions.
     *
     * @return void
     */
    public function testTokenWithoutIndexPermissions(): void
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId),
            [IndexUUID::createById(self::$anotherIndex)]
        );
        $this->putToken($token, self::$appId);

        $this->expectException(InvalidTokenException::class);
        $this->query(
            Query::createMatchAll(),
            self::$appId,
            self::$index,
            $token
        );
    }

    /**
     * Test token without endpoint permissions.
     *
     * @param array $routes
     *
     * @dataProvider dataTokenWithEndpointPermissionsFailing
     *
     * @return void
     */
    public function testTokenWithEndpointPermissionsFailing(array $routes): void
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );

        $token->setEndpoints($routes);
        $this->putToken($token, self::$appId);

        $this->expectException(InvalidTokenException::class);
        $this->query(
            Query::createMatchAll(),
            self::$appId,
            self::$index,
            $token
        );
    }

    /**
     * Data for testTokenWithEndpointPermissionsFailing.
     *
     * @return array
     */
    public function dataTokenWithEndpointPermissionsFailing(): array
    {
        return [
            [['check_health']],
            [['v2_query']],
            [['v2_query', 'check_health']],
            [['token_management']],
        ];
    }

    /**
     * Test token without endpoint permissions.
     *
     * @param array $routes
     *
     * @dataProvider dataTokenWithEndpointPermissionsAccepted
     *
     * @return void
     */
    public function testTokenWithEndpointPermissionsAccepted(array $routes): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );

        $token->setEndpoints($routes);
        $this->putToken($token, self::$appId);

        $this->query(
            Query::createMatchAll(),
            self::$appId,
            self::$index,
            $token
        );

        $this->getTokens(self::$appId, $token);
    }

    /**
     * Data for testTokenWithEndpointPermissionsAccepted.
     *
     * @return array
     */
    public function dataTokenWithEndpointPermissionsAccepted(): array
    {
        return [
            [[]],
            [['v1_query', 'v1_get_tokens']],
            [['v1_query', 'v1_get_tokens', 'v1_delete_items']],
            [['v1_query', 'v1_get_tokens', 'v1_delete_items', '']],

            // By complete url
            [['apisearch_v1_query', 'apisearch_v1_get_tokens']],

            // By using tags
            [['query', 'tokens']],
            [['v1_query', 'tokens']],
            [['query', 'v1_get_tokens']],
            [['v1_query', 'v1_get_tokens', 'query', 'tokens']],
            [['v1_query', 'v1_get_tokens', 'query', 'tokens', 'v1_delete_items', '']],
        ];
    }

    /**
     * Test different app id.
     *
     * @return void
     */
    public function testInvalidAppId(): void
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $this->putToken($token, self::$appId);
        $this->expectException(InvalidTokenException::class);
        $this->query(
            Query::createMatchAll(),
            self::$anotherAppId,
            self::$index,
            $token
        );
    }

    /**
     * Test get tokens.
     *
     * @return void
     */
    public function testGetTokens(): void
    {
        $tokenUUID = TokenUUID::createById('12345');
        $token = new Token(
            $tokenUUID,
            AppUUID::createById(self::$appId)
        );
        $this->deleteToken(TokenUUID::createById('12345'));
        $this->assertCount(3, $this->getTokens());
        $this->putToken($token);
        $this->assertCount(4, $this->getTokens());
        $this->deleteToken($tokenUUID);
        $this->assertCount(3, $this->getTokens());
        $this->putToken($token);
        $this->putToken($token);
        $this->putToken($token);
        $this->putToken(new Token(
            TokenUUID::createById('56789'),
            AppUUID::createById(self::$appId)
        ));
        $this->putToken(new Token(
            TokenUUID::createById('56789'),
            AppUUID::createById(self::$appId)
        ));
        $this->assertCount(5, $this->getTokens());
    }

    /**
     * Test delete tokens.
     *
     * @return void
     */
    public function testDeleteTokens(): void
    {
        $this->resetScenario();
        $this->putToken(new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        ));
        $this->putToken(new Token(
            TokenUUID::createById('67890'),
            AppUUID::createById(self::$appId)
        ));
        $this->putToken(new Token(
            TokenUUID::createById('aaaaa'),
            AppUUID::createById(self::$appId)
        ));
        $this->putToken(new Token(
            TokenUUID::createById('bbbbb'),
            AppUUID::createById(self::$appId)
        ));

        $this->assertCount(7, $this->getTokens());
        $this->deleteTokens();
        $this->assertCount(3, $this->getTokens());
    }

    /**
     * Test update token permissions.
     *
     * @return void
     */
    public function testUpdateTokenPermissions(): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(
            TokenUUID::createById('token-multiquery'),
            AppUUID::createById(static::$appId),
            [IndexUUID::createById(self::$anotherIndex)]
        );
        $this->putToken($token);

        try {
            $this->query(Query::createMatchAll(), static::$appId, static::$index, $token);
            $this->fail('Token without permissions. Exception of InvalidTokenException should have been cached here');
        } catch (InvalidTokenException $exception) {
            // Silent pass
        }

        $token = new Token(
            TokenUUID::createById('token-multiquery'),
            AppUUID::createById(static::$appId),
            [IndexUUID::createById(self::$index)]
        );
        $this->putToken($token);
        $this->query(Query::createMatchAll(), static::$appId, static::$index, $token);
    }

    /**
     * Permissions in multi query for valid token.
     *
     * @return void
     */
    public function testMultiqueryValidToken(): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(
            TokenUUID::createById('token-multiquery'),
            AppUUID::createById(static::$appId),
            [IndexUUID::createById(self::$index), IndexUUID::createById(self::$anotherIndex)]
        );

        $this->putToken($token);
        $this->query(Query::createMultiquery([
            'q1' => Query::createMatchAll(),
            'q2' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(self::$anotherIndex)),
        ]), static::$appId, self::$index, $token);

        $this->query(Query::createMultiquery([
            'q1' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(self::$index)),
            'q2' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(self::$anotherIndex)),
        ]), static::$appId, '*', $token);

        /*
         * Forcing an assertion. At this point, the test was good.
         */
    }

    /**
     * Permissions in multi query for invalid token.
     *
     * @param Query $query
     *
     * @dataProvider dataMultiqueryInvalidToken
     *
     * @return void
     */
    public function testMultiqueryInvalidToken(Query $query): void
    {
        $token = new Token(
            TokenUUID::createById('token-multiquery'),
            AppUUID::createById(static::$appId),
            [IndexUUID::createById(self::$index)]
        );

        $this->putToken($token);
        $this->expectException(InvalidTokenException::class);
        $this->query($query, static::$appId, '*', $token);
    }

    /**
     * Data for invalid multiquery queries.
     */
    public function dataMultiqueryInvalidToken(): array
    {
        return [
            [
                Query::createMultiquery([
                    'q1' => Query::createMatchAll(),
                    'q2' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(self::$anotherIndex)),
                ]),
            ],
            [
                Query::createMultiquery([
                    'q1' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(self::$index)),
                    'q2' => Query::createMatchAll()->forceIndexUUID(IndexUUID::createById(self::$anotherIndex)),
                ]),
            ],
        ];
    }

    /**
     * Test token persistence on new service creation.
     *
     * @return void
     */
    public function testNewServiceTokens()
    {
        if (!$this->isDistributedTokenRepository()) {
            $this->markTestSkipped('Skipped. Testing a non-distributed adapter');

            return;
        }

        $appUUID = AppUUID::createById(static::$appId);
        $tokenUUID = TokenUUID::createById('multiservice-token');
        $token = new Token(
            $tokenUUID,
            $appUUID
        );

        $this->putToken($token);

        $clusterKernel = static::createNewKernel();
        $this->assertCount(4, $this->getTokensFromKernel($clusterKernel));

        /**
         * Existing service.
         */
        $output = static::runCommand([
            'apisearch-server:print-tokens',
            'app-id' => $appUUID->composeUUID(),
        ]);
        $this->assertStringContainsString('multiservice-token', $output);

        /**
         * New service.
         */
        $process = static::runAsyncCommand([
            'apisearch-server:print-tokens',
            $appUUID->composeUUID(),
        ]);
        \sleep(1);
        $this->assertStringContainsString('multiservice-token', $process->getOutput());
    }

    /**
     * @param KernelInterface $kernel
     * @param string|null     $appId
     * @param Token|null      $token
     */
    private function getTokensFromKernel(
        KernelInterface $kernel,
        string $appId = null,
        Token $token = null
    ) {
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        return static::await($kernel
            ->getContainer()
            ->get('drift.query_bus.test')
            ->ask(new GetTokens(
                RepositoryReference::create($appUUID),
                $token ?? $this->getGodToken($appId)
            )));
    }
}
