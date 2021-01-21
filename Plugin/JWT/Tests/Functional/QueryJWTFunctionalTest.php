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

namespace Apisearch\Plugin\JWT\Tests\Functional;

use Apisearch\Exception\ForbiddenException;
use Apisearch\Query\Query;
use Firebase\JWT\JWT;

/**
 * Class QueryJWTFunctionalTest.
 */
class QueryJWTFunctionalTest extends JWTFunctionalTest
{
    /**
     * Get JWT configuration.
     *
     * @return array
     */
    protected static function getJWTConfiguration(): array
    {
        return [
            'private_key' => self::PRIVATE_KEY,
            'allowed_algorithms' => self::ALGORITHM,
            'ttl' => self::TTL,
            'filters' => [
                'role' => [
                    'admin' => [
                        'category' => '1',
                    ],
                    'user' => [
                        'category' => ['2'],
                    ],
                ],
                'category' => [
                    '*' => [
                        'category' => '$1',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test as admin.
     *
     * @return void
     */
    public function testAsAdmin(): void
    {
        $query = Query::createMatchAll();
        $jwtPayload = JWT::encode([
            'role' => 'admin',
        ], self::PRIVATE_KEY, self::ALGORITHM);

        $result = $this->query($query, null, null, null, [], null, [
            'Authorization' => "Bearer $jwtPayload",
        ]);

        $this->assertResults($result, ['{1', '2', '3', '4}', '!5']);
    }

    /**
     * Test as user.
     *
     * @return void
     */
    public function testAsUser(): void
    {
        $query = Query::createMatchAll();
        $jwtPayload = JWT::encode([
            'role' => 'user',
        ], self::PRIVATE_KEY, self::ALGORITHM);

        $result = $this->query($query, null, null, null, [], null, [
            'Authorization' => "Bearer $jwtPayload",
        ]);

        $this->assertResults($result, ['{1', '2', '3}', '!4', '!5']);
    }

    /**
     * Test as other.
     *
     * @return void
     */
    public function testAsOther(): void
    {
        $query = Query::createMatchAll();
        $jwtPayload = JWT::encode([
            'role' => 'other',
        ], self::PRIVATE_KEY, self::ALGORITHM);

        $result = $this->query($query, null, null, null, [], null, [
            'Authorization' => "Bearer $jwtPayload",
        ]);

        $this->assertResults($result, ['{1', '2', '3', '4', '5}']);
    }

    /**
     * Test match all.
     *
     * @return void
     */
    public function testMatchAll(): void
    {
        $query = Query::createMatchAll();
        $jwtPayload = JWT::encode([
            'category' => '1',
        ], self::PRIVATE_KEY, self::ALGORITHM);

        $result = $this->query($query, null, null, null, [], null, [
            'Authorization' => "Bearer $jwtPayload",
        ]);

        $this->assertResults($result, ['{1', '2', '3', '4}', '!5']);

        $query = Query::createMatchAll();
        $jwtPayload = JWT::encode([
            'category' => '2',
        ], self::PRIVATE_KEY, self::ALGORITHM);

        $result = $this->query($query, null, null, null, [], null, [
            'Authorization' => "Bearer $jwtPayload",
        ]);

        $this->assertResults($result, ['{1', '2', '3}', '!4', '!5']);
    }

    /**
     * Test not authenticated.
     *
     * @return void
     */
    public function testNotAuthenticated(): void
    {
        $query = Query::createMatchAll();
        $this->expectException(ForbiddenException::class);
        $this->query($query);
    }

    /**
     * Test with bad bearer.
     *
     * @return void
     */
    public function testWithBadBearer(): void
    {
        $query = Query::createMatchAll();
        $wrongBearer = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
        $this->expectException(ForbiddenException::class);
        $this->query($query, null, null, null, [], null, [
            "Authorization: Bearer $wrongBearer",
        ]);
    }

    /**
     * Test with bad encryption private key.
     *
     * @return void
     */
    public function testWithBadEncodingPrivateKey(): void
    {
        $query = Query::createMatchAll();
        $jwtPayload = JWT::encode([
            'role' => 'user',
        ], 'another_private_key', self::ALGORITHM);

        $this->expectException(ForbiddenException::class);
        $this->query($query, null, null, null, [], null, [
            'Authorization' => "Bearer $jwtPayload",
        ]);
    }

    /**
     * Test with bad encryption hash.
     *
     * @return void
     */
    public function testWithBadEncodingHash(): void
    {
        $query = Query::createMatchAll();
        $jwtPayload = JWT::encode([
            'role' => 'user',
        ], self::PRIVATE_KEY, 'HS512');

        $this->expectException(ForbiddenException::class);
        $this->query($query, null, null, null, [], null, [
            'Authorization' => "Bearer $jwtPayload",
        ]);
    }
}
