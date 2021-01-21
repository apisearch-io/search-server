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
 * Class HealthTest.
 */
class HealthTest extends HttpFunctionalTest
{
    /**
     * Test check health with different tokens.
     *
     * @param string $token
     * @param int    $responseCode
     *
     * @dataProvider dataCheckHealth
     *
     * @return void
     */
    public function testCheckHealth(
        string $token,
        int $responseCode
    ) {
        try {
            $result = $this->checkHealth($this->createTokenByIdAndAppId($token));
        } catch (TransportableException $exception) {
            $this->assertEquals(
                $responseCode,
                $exception->getTransportableHTTPError()
            );

            return;
        }

        $this->assertTrue($result['healthy']);
        $this->assertEquals([], $result['info']['plugins']);
        $this->assertEquals([], $result['status']);
        $this->assertGreaterThan(0, $result['process']['memory_used']);
        $this->assertGreaterThan(0, $result['process']['real_memory_used']);
    }

    /**
     * Data for check health testing.
     *
     * @return array
     */
    public function dataCheckHealth(): array
    {
        return [
            [$_ENV['APISEARCH_GOD_TOKEN'], 401],
            [$_ENV['APISEARCH_HEALTH_CHECK_TOKEN'], 200],
            [$_ENV['APISEARCH_PING_TOKEN'], 401],
            ['non-existing-key', 401],
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
        $token->setEndpoints(['health_check', 'apisearch_health_check']);
        $this->putToken($token);
        try {
            $this->checkHealth($token);
            $this->fail('Should throw token not found exception');
        } catch (TransportableException $exception) {
            $this->assertEquals(
                401,
                $exception->getTransportableHTTPError()
            );
        }
    }
}
