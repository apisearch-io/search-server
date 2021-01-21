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

namespace Apisearch\Server\Tests\Functional\Http;

use Apisearch\Exception\TransportableException;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class PingTest.
 */
class PingTest extends HttpFunctionalTest
{
    /**
     * Test ping with different tokens.
     *
     * @param string $token
     * @param int    $responseCode
     *
     * @dataProvider dataPing
     *
     * @return void
     */
    public function testPing(
        string $token,
        int $responseCode
    ): void {
        try {
            $this->ping($this->createTokenByIdAndAppId($token));
            $this->assertTrue(200 === $responseCode);
        } catch (TransportableException $exception) {
            $this->assertTrue(200 !== $responseCode);
        }
    }

    /**
     * Data for ping testing.
     *
     * @return array
     */
    public function dataPing(): array
    {
        return [
            [$_ENV['APISEARCH_GOD_TOKEN'], 401],
            [$_ENV['APISEARCH_HEALTH_CHECK_TOKEN'], 401],
            [$_ENV['APISEARCH_PING_TOKEN'], 200],
            ['1234', 401],
        ];
    }

    /**
     * Test that anyone can add health_check in token permissions.
     *
     * @return void
     */
    public function testHealthCheckCantBeAddedInToken(): void
    {
        $token = $this->createTokenByIdAndAppId('another_token');
        $token->setEndpoints(['ping', 'apisearch_ping']);
        $this->putToken($token);
        try {
            $this->ping($token);
            $this->fail('Should throw token not found exception');
        } catch (TransportableException $exception) {
            $this->assertEquals(
                401,
                $exception->getTransportableHTTPError()
            );
        }
    }
}
