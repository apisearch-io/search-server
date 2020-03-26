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
     */
    public function testTokenCreation()
    {
        $token = new Token(TokenUUID::createById('12345'), AppUUID::createById(self::$appId));
        $this->putToken($token);
        $this->assertTrue($this->checkIndex(
            null,
            null,
            new Token(TokenUUID::createById('12345'), AppUUID::createById(self::$appId))
        ));

        $this->deleteToken(TokenUUID::createById('12345'));
        $this->assertFalse($this->checkIndex(
            null,
            null,
            new Token(TokenUUID::createById('12345'), AppUUID::createById(self::$appId))
        ));
    }

    /**
     * Test token without index permissions.
     *
     * @expectedException \Apisearch\Exception\InvalidTokenException
     */
    public function testTokenWithoutIndexPermissions()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId),
            [IndexUUID::createById(self::$anotherIndex)]
        );
        $this->putToken($token, self::$appId);

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
     * @expectedException \Apisearch\Exception\InvalidTokenException
     * @dataProvider dataTokenWithEndpointPermissionsFailing
     */
    public function testTokenWithEndpointPermissionsFailing(array $routes)
    {
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
    }

    /**
     * Data for testTokenWithEndpointPermissionsFailing.
     *
     * @return []
     */
    public function dataTokenWithEndpointPermissionsFailing()
    {
        return [
            [['check_health']],
            [['v2_query']],
            [['v2_query', 'check_health']],
        ];
    }

    /**
     * Test token without endpoint permissions.
     *
     * @dataProvider dataTokenWithEndpointPermissionsAccepted
     */
    public function testTokenWithEndpointPermissionsAccepted(array $routes)
    {
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
        $this->assertTrue(true);
    }

    /**
     * Data for testTokenWithEndpointPermissionsAccepted.
     *
     * @return []
     */
    public function dataTokenWithEndpointPermissionsAccepted()
    {
        return [
            [[]],
            [['v1_query']],
            [['v1_query', 'v1_delete_items']],
            [['v1_query', 'v1_delete_items', '']],
        ];
    }

    /**
     * Test different app id.
     *
     * @expectedException \Apisearch\Exception\InvalidTokenException
     */
    public function testInvalidAppId()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $this->putToken($token, self::$appId);
        $this->query(
            Query::createMatchAll(),
            self::$anotherAppId,
            self::$index,
            $token
        );
    }

    /**
     * Test get tokens.
     */
    public function testGetTokens()
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
     */
    public function testDeleteTokens()
    {
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
     * Permissions in multiquery for valid token.
     */
    public function testMultiqueryValidToken()
    {
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
        $this->assertTrue(true);
    }

    /**
     * Permissions in multiquery for invalid token.
     *
     * @param Query $query
     *
     * @dataProvider dataMultiqueryInvalidToken
     *
     * @expectedException \Apisearch\Exception\InvalidTokenException
     */
    public function testMultiqueryInvalidToken(Query $query)
    {
        $token = new Token(
            TokenUUID::createById('token-multiquery'),
            AppUUID::createById(static::$appId),
            [IndexUUID::createById(self::$index)]
        );

        $this->putToken($token);
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

        $clusterKernel = static::getKernel();
        $clusterKernel->boot();
        $clusterContainer = $clusterKernel->getContainer();
        static::await(
            $clusterKernel->preload(),
            $clusterContainer->get('reactphp.event_loop')
        );

        $this->assertCount(4, $this->getTokensFromKernel($clusterKernel));

        /**
         * Existing service.
         */
        $output = static::runCommand([
            'apisearch-server:print-token',
            'app-id' => $appUUID->composeUUID(),
        ]);
        $this->assertContains('multiservice-token', $output);

        /**
         * New service.
         */
        $process = static::runAsyncCommand([
            'apisearch-server:print-token',
            $appUUID->composeUUID(),
        ]);
        sleep(1);
        $this->assertContains('multiservice-token', $process->getOutput());
    }

    /**
     * Get tokens from a given token.
     *
     * @param KernelInterface $kernel
     * @param string          $appId
     * @param Token           $token
     *
     * @return Token[]
     */
    private static function getTokensFromKernel(
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
                $token ??
                new Token(
                    TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                    $appUUID
                )
            )));
    }
}
