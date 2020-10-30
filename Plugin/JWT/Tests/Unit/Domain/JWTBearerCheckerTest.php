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

namespace Apisearch\Plugin\JWT\Tests\Unit\Domain;

use Apisearch\Exception\ForbiddenException;
use Apisearch\Plugin\JWT\Domain\JWTBearerChecker;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

/**
 * Class JWTBearerCheckerTest.
 */
class JWTBearerCheckerTest extends TestCase
{
    const PRIVATE_KEY = '6F27583CEB7C75C68246784261456';
    const ALGORITHM = 'HS256';
    const TTL = 3600;

    /**
     * Test valid bearer.
     */
    public function testValidBearer()
    {
        $jwtBearerChecker = new JWTBearerChecker(self::PRIVATE_KEY, [self::ALGORITHM], self::TTL);
        $bearer = JWT::encode(['something'], self::PRIVATE_KEY, self::ALGORITHM);
        $authorization = "Bearer $bearer";
        $this->expectNotToPerformAssertions();
        $jwtBearerChecker->checkBearer($authorization);
    }

    /**
     * Test payload with future expired time.
     */
    public function testNotExpiredPayload()
    {
        $jwtBearerChecker = new JWTBearerChecker(self::PRIVATE_KEY, [self::ALGORITHM], self::TTL);
        $bearer = JWT::encode([
            'exp' => \time() + 3600,
        ], self::PRIVATE_KEY, self::ALGORITHM);
        $authorization = "Bearer $bearer";
        $this->expectNotToPerformAssertions();
        $jwtBearerChecker->checkBearer($authorization);
    }

    /**
     * Test payload with good IAT.
     */
    public function testExpiredPayloadWithGoodIAT()
    {
        $jwtBearerChecker = new JWTBearerChecker(self::PRIVATE_KEY, [self::ALGORITHM], self::TTL);
        $bearer = JWT::encode([
            'iat' => \time(),
        ], self::PRIVATE_KEY, self::ALGORITHM);
        $authorization = "Bearer $bearer";
        $this->expectNotToPerformAssertions();
        $jwtBearerChecker->checkBearer($authorization);
    }

    /**
     * Test invalid bearer.
     */
    public function testInvalidBearerFormat()
    {
        $jwtBearerChecker = new JWTBearerChecker(self::PRIVATE_KEY, [self::ALGORITHM], self::TTL);
        $bearer = JWT::encode(['something'], self::PRIVATE_KEY, self::ALGORITHM);
        $authorization = "Another $bearer";
        $this->expectException(ForbiddenException::class);
        $jwtBearerChecker->checkBearer($authorization);
    }

    /**
     * Test different private key.
     */
    public function testDifferentPrivateKey()
    {
        $jwtBearerChecker = new JWTBearerChecker(self::PRIVATE_KEY, [self::ALGORITHM], self::TTL);
        $bearer = JWT::encode(['something'], 'another', self::ALGORITHM);
        $authorization = "Bearer $bearer";
        $this->expectException(ForbiddenException::class);
        $jwtBearerChecker->checkBearer($authorization);
    }

    /**
     * Test different algorithm.
     */
    public function testDifferentAlgorithm()
    {
        $jwtBearerChecker = new JWTBearerChecker(self::PRIVATE_KEY, ['HS512'], self::TTL);
        $bearer = JWT::encode(['something'], 'another', self::ALGORITHM);
        $authorization = "Bearer $bearer";
        $this->expectException(ForbiddenException::class);
        $jwtBearerChecker->checkBearer($authorization);
    }

    /**
     * Test different algorithm.
     */
    public function testNotAllowedAlgorithm()
    {
        $jwtBearerChecker = new JWTBearerChecker(self::PRIVATE_KEY, ['Another'], self::TTL);
        $bearer = JWT::encode(['something'], self::PRIVATE_KEY, self::ALGORITHM);
        $authorization = "Bearer $bearer";
        $this->expectException(ForbiddenException::class);
        $jwtBearerChecker->checkBearer($authorization);
    }

    /**
     * Test payload with past expiration time.
     */
    public function testExpiredPayload()
    {
        $jwtBearerChecker = new JWTBearerChecker(self::PRIVATE_KEY, [self::ALGORITHM], self::TTL);
        $bearer = JWT::encode([
            'exp' => \time() - 3600,
        ], self::PRIVATE_KEY, self::ALGORITHM);
        $authorization = "Bearer $bearer";
        $this->expectException(ForbiddenException::class);
        $jwtBearerChecker->checkBearer($authorization);
    }

    /**
     * Test payload with expired IAT.
     */
    public function testExpiredPayloadWithIAT()
    {
        $jwtBearerChecker = new JWTBearerChecker(self::PRIVATE_KEY, [self::ALGORITHM], self::TTL);
        $bearer = JWT::encode([
            'iat' => \time() + 9000,
        ], self::PRIVATE_KEY, self::ALGORITHM);
        $authorization = "Bearer $bearer";
        $this->expectException(ForbiddenException::class);
        $jwtBearerChecker->checkBearer($authorization);
    }
}
